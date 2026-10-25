<?php

namespace App\Services;

use App\Enums\ActiveInactiveStatusEnum;
use App\Enums\DeliveryBoy\DeliveryBoyVerificationStatusEnum;
use App\Enums\Order\OrderItemStatusEnum;
use App\Models\DeliveryBoy;
use App\Models\DeliveryBoyOrderOffer;
use App\Models\Order;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeliveryBoyOrderOfferService
{
    public function __construct(private FirebaseService $firebase)
    {
    }

    public function offerNextRider(int $orderId): void
    {
        $this->dispatchReadyOrders(null, $orderId);
    }

    public function dispatchReadyOrders(?int $zoneId = null, ?int $onlyOrderId = null): void
    {
        $orders = Order::query()
            ->whereNull('delivery_boy_id')
            ->when($zoneId, fn ($query) => $query->where('delivery_zone_id', $zoneId))
            ->when($onlyOrderId, fn ($query) => $query->whereKey($onlyOrderId))
            ->whereHas('sellerOrders', fn ($query) => $query
                ->whereHas('bag')
                ->whereHas('items.orderItem', fn ($items) => $items->where('status', OrderItemStatusEnum::PREPARING())))
            ->whereDoesntHave('deliveryBoyOrderOffers', fn ($query) => $query->where('status', 'pending'))
            ->with(['sellerOrders' => fn ($query) => $query
                ->whereHas('bag')
                ->whereHas('items.orderItem', fn ($items) => $items->where('status', OrderItemStatusEnum::PREPARING()))
                ->with(['bag', 'items.orderItem.store'])])
            ->inRandomOrder()
            ->get();

        foreach ($orders as $order) {
            $sellerOrder = $order->sellerOrders->first();
            $preparedItem = $sellerOrder?->items->first(fn ($item) => $item->orderItem?->status === OrderItemStatusEnum::PREPARING());
            $store = $preparedItem?->orderItem?->store;
            if (!$store || $store->latitude === null || $store->longitude === null) {
                Log::warning('Rider order dispatch skipped because pickup coordinates are missing.', ['order_id' => $order->id]);
                continue;
            }

            $riders = DeliveryBoy::query()
                ->where('delivery_zone_id', $order->delivery_zone_id)
                ->where('status', ActiveInactiveStatusEnum::ACTIVE())
                ->where('verification_status', DeliveryBoyVerificationStatusEnum::VERIFIED())
                ->whereHas('user.fcmTokens')
                ->whereHas('location', fn ($query) => $query->where('recorded_at', '>=', now()->subMinutes(2)))
                ->whereDoesntHave('assignments', fn ($query) => $query->whereIn('status', ['assigned', 'in_progress']))
                ->whereDoesntHave('orderOffers', fn ($query) => $query->where('status', 'pending'))
                ->with(['location', 'user.fcmTokens'])
                ->get()
                ->filter(fn ($rider) => $rider->location && DeliveryZoneService::calculateDistance(
                    (float) $rider->location->latitude,
                    (float) $rider->location->longitude,
                    (float) $store->latitude,
                    (float) $store->longitude
                ) <= 0.2)
                ->shuffle();

            $assigned = false;
            foreach ($riders as $rider) {
                $offerData = DB::transaction(function () use ($order, $rider, $store) {
                    $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->first();
                    $lockedRider = DeliveryBoy::query()->whereKey($rider->id)->lockForUpdate()->first();
                    if (!$lockedOrder || $lockedOrder->delivery_boy_id !== null || !$lockedRider
                        || $lockedRider->status !== ActiveInactiveStatusEnum::ACTIVE()
                        || $lockedRider->verification_status !== DeliveryBoyVerificationStatusEnum::VERIFIED()) {
                        return null;
                    }

                    $location = $lockedRider->location()
                        ->where('recorded_at', '>=', now()->subMinutes(2))
                        ->first();
                    if (!$location || DeliveryZoneService::calculateDistance(
                        (float) $location->latitude,
                        (float) $location->longitude,
                        (float) $store->latitude,
                        (float) $store->longitude
                    ) > 0.2) {
                        return null;
                    }

                    if (DeliveryBoyOrderOffer::query()->where('order_id', $lockedOrder->id)->where('status', 'pending')->exists()
                        || DeliveryBoyOrderOffer::query()->where('delivery_boy_id', $lockedRider->id)->where('status', 'pending')->exists()
                        || $lockedRider->assignments()->whereIn('status', ['assigned', 'in_progress'])->exists()) {
                        return null;
                    }

                    $offer = DeliveryBoyOrderOffer::create([
                        'order_id' => $lockedOrder->id,
                        'delivery_boy_id' => $lockedRider->id,
                        'status' => 'pending',
                    ]);
                    $lockedOrder->update(['delivery_boy_id' => $lockedRider->id]);

                    $storeIds = $lockedOrder->items()->whereNot('status', OrderItemStatusEnum::REJECTED())->pluck('store_id')->unique()->all();
                    $route = DeliveryZoneService::calculateDeliveryRoute(
                        (float) $lockedOrder->shipping_latitude,
                        (float) $lockedOrder->shipping_longitude,
                        $storeIds,
                        $lockedOrder
                    );

                    return [
                        'offer_id' => $offer->id,
                        'tokens' => $lockedRider->user->fcmTokens()->pluck('fcm_token')->filter()->all(),
                        'payload' => [
                            'type' => 'new_order',
                            'order_id' => (string) $lockedOrder->id,
                            'pickup' => (string) $store->name,
                            'drop' => (string) ($lockedOrder->shipping_landmark ?: $lockedOrder->shipping_city ?: ''),
                            'distance_km' => (string) ($route['total_distance'] ?? 0),
                            'amount' => (string) ($lockedOrder->final_total ?? $lockedOrder->total_payable ?? 0),
                        ],
                    ];
                });

                if (!$offerData) {
                    continue;
                }

                $acceptedByFirebase = 0;
                foreach ($offerData['tokens'] as $token) {
                    $result = $this->firebase->sendDataMessage($token, $offerData['payload']);
                    if (!$result['success']) {
                        Log::error('Rider order push failed after dispatch reservation.', [
                            'order_id' => $order->id,
                            'delivery_boy_id' => $rider->id,
                            'error' => $result['error'] ?? 'unknown',
                        ]);
                    } else {
                        $acceptedByFirebase++;
                    }
                }
                Log::info('Rider order push dispatch completed.', [
                    'order_id' => $order->id,
                    'delivery_boy_id' => $rider->id,
                    'token_count' => count($offerData['tokens']),
                    'firebase_accepted_count' => $acceptedByFirebase,
                ]);
                $assigned = true;
                break;
            }

            if (!$assigned) {
                Log::notice('No eligible nearby rider for prepared order.', [
                    'order_id' => $order->id,
                    'delivery_zone_id' => $order->delivery_zone_id,
                ]);
            }
        }
    }

    public function riderAvailabilityForSeller(int $sellerId): array
    {
        $stores = Store::query()
            ->where('seller_id', $sellerId)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with('zones:id,name')
            ->get(['id', 'name', 'latitude', 'longitude']);

        $storesByZone = [];
        foreach ($stores as $store) {
            foreach ($store->zones as $zone) {
                $storesByZone[$zone->id][] = $store;
            }
        }

        $orderZoneIds = Order::query()
            ->whereNotNull('delivery_zone_id')
            ->whereHas('sellerOrders', fn ($query) => $query->where('seller_id', $sellerId))
            ->distinct()
            ->pluck('delivery_zone_id')
            ->all();
        $zoneIds = collect(array_keys($storesByZone))
            ->merge($orderZoneIds)
            ->unique()
            ->values()
            ->all();
        $riders = empty($zoneIds)
            ? collect()
            : DeliveryBoy::query()
                ->whereIn('delivery_zone_id', $zoneIds)
                ->with([
                    'user:id,name',
                    'user.fcmTokens:id,user_id,device_type',
                    'deliveryZone:id,name',
                    'location',
                    'orderOffers' => fn ($query) => $query->where('status', 'pending'),
                    'assignments' => fn ($query) => $query->whereIn('status', ['assigned', 'in_progress']),
                ])
                ->get();

        $now = now();
        $riderRows = $riders->map(function (DeliveryBoy $rider) use ($storesByZone, $now) {
            $active = $rider->status === ActiveInactiveStatusEnum::ACTIVE();
            $verified = $rider->verification_status === DeliveryBoyVerificationStatusEnum::VERIFIED();
            $hasFcmToken = $rider->user?->fcmTokens?->isNotEmpty() ?? false;
            $hasPendingOffer = $rider->orderOffers->isNotEmpty();
            $hasActiveAssignment = $rider->assignments->isNotEmpty();
            $lastSeenAt = $rider->location?->getRawOriginal('recorded_at');
            $lastSeen = $lastSeenAt ? Carbon::parse($lastSeenAt) : null;
            $locationFresh = $lastSeen?->greaterThanOrEqualTo($now->copy()->subMinutes(2)) ?? false;
            $nearestDistanceKm = null;

            if ($locationFresh) {
                foreach ($storesByZone[$rider->delivery_zone_id] ?? [] as $store) {
                    $distance = DeliveryZoneService::calculateDistance(
                        (float) $rider->location->latitude,
                        (float) $rider->location->longitude,
                        (float) $store->latitude,
                        (float) $store->longitude
                    );
                    $nearestDistanceKm = $nearestDistanceKm === null ? $distance : min($nearestDistanceKm, $distance);
                }
            }

            $withinRadius = $nearestDistanceKm !== null && $nearestDistanceKm <= 0.2;
            $available = $active && $verified && $hasFcmToken && $locationFresh && $withinRadius
                && !$hasPendingOffer && !$hasActiveAssignment;
            $unavailableReason = match (true) {
                !$active => 'inactive',
                !$verified => 'not_verified',
                !$hasFcmToken => 'no_fcm_token',
                !$locationFresh => 'location_stale',
                !$withinRadius => 'outside_pickup_radius',
                $hasPendingOffer => 'pending_offer',
                $hasActiveAssignment => 'active_delivery',
                default => null,
            };

            return [
                'id' => $rider->id,
                'name' => $rider->full_name ?: $rider->user?->name,
                'status' => $active ? 'active' : 'inactive',
                'verification_status' => $rider->verification_status?->value,
                'delivery_zone' => $rider->deliveryZone?->name,
                'is_available_in_radius' => $available,
                'nearest_pickup_distance_m' => $nearestDistanceKm === null ? null : (int) round($nearestDistanceKm * 1000),
                'location_reported_at' => $lastSeen?->toIso8601String(),
                'location_age_seconds' => $lastSeen ? max(0, $lastSeen->diffInSeconds($now)) : null,
                'unavailable_reason' => $unavailableReason,
            ];
        })->values();

        return [
            'summary' => [
                'active_riders' => $riderRows->where('status', 'active')->count(),
                'available_riders_in_radius' => $riderRows->where('is_available_in_radius', true)->count(),
                'inactive_riders' => $riderRows->where('status', 'inactive')->count(),
            ],
            'riders' => $riderRows,
        ];
    }

    public function expire(int $offerId): void
    {
        // Keep previously queued delayed jobs harmless during deployment.
    }

    public function cancelPendingForOrder(int $orderId, string $reason): void
    {
        if (!in_array($reason, ['reassigned', 'cancelled'], true)) {
            return;
        }

        $pendingOffers = DeliveryBoyOrderOffer::query()
            ->where('order_id', $orderId)
            ->where('status', 'pending')
            ->with('deliveryBoy.user.fcmTokens')
            ->get();

        DeliveryBoyOrderOffer::query()
            ->whereIn('id', $pendingOffers->pluck('id'))
            ->update(['status' => $reason, 'responded_at' => now()]);

        foreach ($pendingOffers as $offer) {
            foreach ($offer->deliveryBoy?->user?->fcmTokens ?? [] as $token) {
                $this->firebase->sendDataMessage($token->fcm_token, [
                    'type' => 'order_alert_cancel',
                    'order_id' => (string) $orderId,
                    'reason' => $reason,
                ]);
            }
        }
    }
}