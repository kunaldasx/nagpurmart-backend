<?php

namespace App\Services;

use App\Enums\ActiveInactiveStatusEnum;
use App\Enums\DeliveryBoy\DeliveryBoyVerificationStatusEnum;
use App\Enums\Order\OrderItemStatusEnum;
use App\Enums\Order\OrderStatusEnum;
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
        $this->dispatchReadyOrders(null, null, $orderId);
    }

    public function dispatchReadyOrders(?int $sellerId = null, ?int $zoneId = null, ?int $onlyOrderId = null): void
    {
        $orders = Order::query()
            ->whereNull('delivery_boy_id')
            ->whereNotIn('status', [OrderStatusEnum::CANCELLED(), OrderStatusEnum::DELIVERED(), OrderStatusEnum::FAILED(), OrderStatusEnum::REJECTED_BY_SELLER()])
            ->when($zoneId, fn ($query) => $query->where('delivery_zone_id', $zoneId))
            ->when($onlyOrderId, fn ($query) => $query->whereKey($onlyOrderId))
            ->where(fn ($query) => $query->whereNull('delivery_assignment_type')->orWhere('delivery_assignment_type', '!=', 'seller_self_delivery'))
            ->whereHas('sellerOrders', fn ($query) => $query
                ->when($sellerId, fn ($sellerQuery) => $sellerQuery->where('seller_id', $sellerId))
                ->whereHas('bag')
                ->whereHas('items.orderItem', fn ($itemQuery) => $itemQuery->where('status', OrderItemStatusEnum::PREPARING())))
            ->whereDoesntHave('deliveryBoyOrderOffers', fn ($query) => $query->where('status', 'pending'))
            ->with(['sellerOrders' => fn ($query) => $query
                ->when($sellerId, fn ($sellerQuery) => $sellerQuery->where('seller_id', $sellerId))
                ->whereHas('bag')
                ->whereHas('items.orderItem', fn ($itemQuery) => $itemQuery->where('status', OrderItemStatusEnum::PREPARING()))
                ->with(['items.orderItem.store'])])
            ->inRandomOrder()
            ->get();

        $ridersByZone = [];
        foreach ($orders as $order) {
            $sellerOrder = $order->sellerOrders->first();
            $pickupItem = $sellerOrder?->items->first(fn ($item) => $item->orderItem?->status === OrderItemStatusEnum::PREPARING());
            $store = $pickupItem?->orderItem?->store;
            if (!$store || $store->latitude === null || $store->longitude === null) {
                continue;
            }

            $zoneId = (int) $order->delivery_zone_id;
            $ridersByZone[$zoneId] ??= $this->eligibleActiveRiders($zoneId);
            $riders = $ridersByZone[$zoneId]
                ->filter(fn ($rider) => DeliveryZoneService::calculateDistance(
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
                        || $lockedRider->verification_status !== DeliveryBoyVerificationStatusEnum::VERIFIED()
                        || in_array($lockedOrder->status, [OrderStatusEnum::CANCELLED(), OrderStatusEnum::DELIVERED(), OrderStatusEnum::FAILED(), OrderStatusEnum::REJECTED_BY_SELLER()], true)) {
                        return null;
                    }

                    $location = $lockedRider->location()
                        ->where('recorded_at', '>=', now()->subMinutes(2))
                        ->first();
                    if (!$location
                        || DeliveryZoneService::calculateDistance(
                            (float) $location->latitude,
                            (float) $location->longitude,
                            (float) $store->latitude,
                            (float) $store->longitude
                        ) > 0.2) {
                        return null;
                    }

                    $hasPending = DeliveryBoyOrderOffer::query()->where('order_id', $lockedOrder->id)->where('status', 'pending')->exists();
                    $riderHasPending = DeliveryBoyOrderOffer::query()->where('delivery_boy_id', $lockedRider->id)->where('status', 'pending')->exists();
                    $riderHasAssignment = $lockedRider->assignments()->whereIn('status', ['assigned', 'in_progress'])->exists();
                    if ($hasPending || $riderHasPending || $riderHasAssignment) {
                        return null;
                    }

                    $offer = DeliveryBoyOrderOffer::create([
                        'order_id' => $lockedOrder->id,
                        'delivery_boy_id' => $lockedRider->id,
                        'status' => 'pending',
                    ]);
                    $lockedOrder->update([
                        'delivery_boy_id' => $lockedRider->id,
                        'delivery_assignment_type' => 'rider',
                    ]);

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
                $ridersByZone[$zoneId] = $ridersByZone[$zoneId]
                    ->reject(fn ($candidate) => $candidate->id === $rider->id)
                    ->values();
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
            $recordedAt = $rider->location?->getRawOriginal('recorded_at');
            $lastSeen = $recordedAt ? Carbon::parse($recordedAt) : null;
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

    public function sellerDispatchDashboard(int $sellerId): array
    {
        $orders = Order::query()
            ->whereNotIn('status', [OrderStatusEnum::CANCELLED(), OrderStatusEnum::DELIVERED(), OrderStatusEnum::FAILED(), OrderStatusEnum::REJECTED_BY_SELLER()])
            ->whereHas('sellerOrders', fn ($query) => $query
                ->where('seller_id', $sellerId)
                ->whereHas('bag')
                ->whereHas('items.orderItem', fn ($itemQuery) => $itemQuery->where('status', OrderItemStatusEnum::PREPARING())))
            ->with([
                'deliveryBoy',
                'deliveryBoyOrderOffers' => fn ($query) => $query->where('status', 'pending')->with('deliveryBoy'),
                'sellerOrders' => fn ($query) => $query
                    ->where('seller_id', $sellerId)
                    ->whereHas('bag')
                    ->whereHas('items.orderItem', fn ($itemQuery) => $itemQuery->where('status', OrderItemStatusEnum::PREPARING()))
                    ->with(['bag', 'items.orderItem.store']),
            ])
            ->orderBy('created_at')
            ->get();

        $ridersByZone = [];
        $rows = $orders->map(function (Order $order) use (&$ridersByZone) {
            $sellerOrder = $order->sellerOrders->first();
            $preparedItem = $sellerOrder?->items->first(fn ($item) => $item->orderItem?->status === OrderItemStatusEnum::PREPARING());
            $store = $preparedItem?->orderItem?->store;
            $zoneId = (int) $order->delivery_zone_id;
            $ridersByZone[$zoneId] ??= $this->eligibleActiveRiders($zoneId);
            $nearbyRiders = $store
                ? $ridersByZone[$zoneId]->filter(fn ($rider) => DeliveryZoneService::calculateDistance(
                    (float) $rider->location->latitude,
                    (float) $rider->location->longitude,
                    (float) $store->latitude,
                    (float) $store->longitude
                ) <= 0.2)
                : collect();
            $pendingOffer = $order->deliveryBoyOrderOffers->first();

            return [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->delivery_assignment_type === 'seller_self_delivery'
                    ? 'seller_self_delivery'
                    : ($pendingOffer ? 'awaiting_rider_acceptance' : ($order->delivery_boy_id ? 'rider_assigned' : ($nearbyRiders->isEmpty() ? 'no_nearby_riders' : 'waiting_for_dispatch'))),
                'delivery_boy_id' => $order->delivery_boy_id,
                'delivery_boy_name' => $order->deliveryBoy?->full_name,
                'pickup' => $store?->name,
                'pickup_store_id' => $store?->id,
                'pickup_latitude' => $store?->latitude,
                'pickup_longitude' => $store?->longitude,
                'nearby_available_riders' => $nearbyRiders->count(),
                'bag_barcode' => $sellerOrder?->bag?->barcode,
                'created_at' => $order->created_at?->toIso8601String(),
            ];
        })->values();

        return [
            'summary' => [
                'ready_orders' => $rows->whereNotIn('status', ['seller_self_delivery', 'rider_assigned'])->count(),
                'no_nearby_riders' => $rows->where('status', 'no_nearby_riders')->count(),
                'assigned_orders' => $rows->whereIn('status', ['awaiting_rider_acceptance', 'rider_assigned'])->count(),
            ],
            'orders' => $rows,
        ];
    }

    public function selfDeliver(int $orderId, int $sellerId): array
    {
        $result = DB::transaction(function () use ($orderId, $sellerId) {
            $pendingOffers = DeliveryBoyOrderOffer::query()
                ->where('order_id', $orderId)
                ->where('status', 'pending')
                ->with('deliveryBoy.user.fcmTokens')
                ->lockForUpdate()
                ->get();

            $order = Order::query()
                ->whereKey($orderId)
                ->whereNotIn('status', [OrderStatusEnum::CANCELLED(), OrderStatusEnum::DELIVERED(), OrderStatusEnum::FAILED(), OrderStatusEnum::REJECTED_BY_SELLER()])
                ->whereHas('sellerOrders', fn ($query) => $query->where('seller_id', $sellerId))
                ->lockForUpdate()
                ->first();

            if (!$order) {
                return ['success' => false, 'status' => 404, 'message' => 'Order not found for this seller.'];
            }

            $isReadyForDispatch = $order->sellerOrders()
                ->where('seller_id', $sellerId)
                ->whereHas('bag')
                ->whereHas('items.orderItem', fn ($query) => $query->where('status', OrderItemStatusEnum::PREPARING()))
                ->exists();
            if (!$isReadyForDispatch) {
                return ['success' => false, 'status' => 422, 'message' => 'Only prepared orders with an assigned bag can be taken for self-delivery.'];
            }

            if ($order->delivery_assignment_type === 'seller_self_delivery') {
                return ['success' => false, 'status' => 409, 'message' => 'This order is already marked for seller self-delivery.'];
            }

            $acceptedAssignment = $order->deliveryBoyAssignments()
                ->whereIn('status', ['assigned', 'in_progress'])
                ->exists();
            if ($acceptedAssignment) {
                return ['success' => false, 'status' => 409, 'message' => 'The rider has already accepted this order. Contact dispatch to reassign it.'];
            }

            $tokens = $pendingOffers->flatMap(fn ($offer) => $offer->deliveryBoy?->user?->fcmTokens ?? [])
                ->pluck('fcm_token')
                ->filter()
                ->unique()
                ->values()
                ->all();
            DeliveryBoyOrderOffer::query()
                ->whereIn('id', $pendingOffers->pluck('id'))
                ->update(['status' => 'reassigned', 'responded_at' => now()]);

            $order->update([
                'delivery_boy_id' => null,
                'delivery_assignment_type' => 'seller_self_delivery',
            ]);

            return ['success' => true, 'status' => 200, 'message' => 'Order marked for seller self-delivery.', 'order_id' => $orderId, 'tokens' => $tokens];
        });

        if ($result['success']) {
            foreach ($result['tokens'] ?? [] as $token) {
                $this->firebase->sendDataMessage($token, [
                    'type' => 'order_alert_cancel',
                    'order_id' => (string) $orderId,
                    'reason' => 'reassigned',
                ]);
            }
            unset($result['tokens']);
        }

        return $result;
    }

    private function eligibleActiveRiders(int $zoneId)
    {
        return DeliveryBoy::query()
            ->where('delivery_zone_id', $zoneId)
            ->where('status', ActiveInactiveStatusEnum::ACTIVE())
            ->where('verification_status', DeliveryBoyVerificationStatusEnum::VERIFIED())
            ->whereHas('user.fcmTokens')
            ->whereHas('location', fn ($query) => $query->where('recorded_at', '>=', now()->subMinutes(2)))
            ->whereDoesntHave('assignments', fn ($query) => $query->whereIn('status', ['assigned', 'in_progress']))
            ->whereDoesntHave('orderOffers', fn ($query) => $query->where('status', 'pending'))
            ->with(['location', 'user.fcmTokens'])
            ->get()
            ->filter(fn ($rider) => $rider->location !== null)
            ->values();
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