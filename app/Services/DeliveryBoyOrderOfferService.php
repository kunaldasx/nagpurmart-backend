<?php

namespace App\Services;

use App\Enums\ActiveInactiveStatusEnum;
use App\Enums\Order\OrderItemStatusEnum;
use App\Jobs\ExpireDeliveryBoyOrderOffer;
use App\Models\DeliveryBoy;
use App\Models\DeliveryBoyOrderOffer;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeliveryBoyOrderOfferService
{
    public function __construct(private FirebaseService $firebase)
    {
    }

    public function offerNextRider(int $orderId): void
    {
        $offerData = DB::transaction(function () use ($orderId) {
            $order = Order::query()->whereKey($orderId)->lockForUpdate()->first();
            if (!$order || $order->delivery_boy_id !== null) {
                return null;
            }

            $pendingOffer = DeliveryBoyOrderOffer::query()
                ->where('order_id', $order->id)
                ->where('status', 'pending')
                ->where('expires_at', '>', now())
                ->first();
            if ($pendingOffer) {
                return null;
            }

            $readySellerOrder = $order->sellerOrders()
                ->whereHas('bag')
                ->whereHas('items.orderItem', fn ($query) => $query->where('status', OrderItemStatusEnum::PREPARING()))
                ->with(['items.orderItem.store'])
                ->first();
            if (!$readySellerOrder) {
                return null;
            }

            $previousRiderIds = DeliveryBoyOrderOffer::query()
                ->where('order_id', $order->id)
                ->pluck('delivery_boy_id');
            $rider = DeliveryBoy::query()
                ->where('delivery_zone_id', $order->delivery_zone_id)
                ->where('status', ActiveInactiveStatusEnum::ACTIVE())
                ->whereNotIn('id', $previousRiderIds)
                ->whereHas('user.fcmTokens')
                ->orderBy('id')
                ->first();

            if (!$rider) {
                Log::alert('Rider order offer has no active eligible riders; dispatcher attention required.', [
                    'order_id' => $order->id,
                    'delivery_zone_id' => $order->delivery_zone_id,
                ]);
                return null;
            }

            $timeout = max(1, (int) config('delivery_boy.order_offer_timeout_seconds', 30));
            $expiresAt = now()->addSeconds($timeout);
            $offer = DeliveryBoyOrderOffer::create([
                'order_id' => $order->id,
                'delivery_boy_id' => $rider->id,
                'status' => 'pending',
                'expires_at' => $expiresAt,
            ]);

            $storeIds = $order->items()->whereNot('status', OrderItemStatusEnum::REJECTED())->pluck('store_id')->unique()->all();
            $route = DeliveryZoneService::calculateDeliveryRoute(
                (float) $order->shipping_latitude,
                (float) $order->shipping_longitude,
                $storeIds,
                $order
            );
            $pickupItem = $readySellerOrder->items->first(
                fn ($item) => $item->orderItem?->status === OrderItemStatusEnum::PREPARING()
            );

            return [
                'offer_id' => $offer->id,
                'expires_at' => $expiresAt,
                'tokens' => $rider->user->fcmTokens()->pluck('fcm_token')->filter()->all(),
                'payload' => [
                    'type' => 'new_order',
                    'order_id' => (string) $order->id,
                    'expires_at' => $expiresAt->copy()->utc()->toISOString(),
                    'pickup' => (string) ($pickupItem?->orderItem?->store?->name ?? 'Store'),
                    'drop' => (string) ($order->shipping_landmark ?: $order->shipping_city ?: ''),
                    'distance_km' => (string) ($route['total_distance'] ?? 0),
                    'amount' => (string) ($order->final_total ?? $order->total_payable ?? 0),
                ],
            ];
        });

        if (!$offerData) {
            return;
        }

        foreach ($offerData['tokens'] as $token) {
            $this->firebase->sendDataMessage($token, $offerData['payload']);
        }

        ExpireDeliveryBoyOrderOffer::dispatch($offerData['offer_id'])->delay($offerData['expires_at']);
    }

    public function expire(int $offerId): void
    {
        $expired = DB::transaction(function () use ($offerId) {
            $offer = DeliveryBoyOrderOffer::query()->whereKey($offerId)->lockForUpdate()->first();
            if (!$offer || $offer->status !== 'pending' || $offer->expires_at->isFuture()) {
                return null;
            }

            $order = Order::query()->whereKey($offer->order_id)->first();
            $reason = !$order || in_array($order->status, ['cancelled', 'canceled'], true)
                ? 'cancelled'
                : ($order->delivery_boy_id !== null ? 'reassigned' : 'expired');
            $offer->update(['status' => $reason, 'responded_at' => now()]);

            return [
                'order_id' => $offer->order_id,
                'rider' => $offer->deliveryBoy()->with('user.fcmTokens')->first(),
                'reason' => $reason,
                'should_reoffer' => $reason === 'expired',
            ];
        });

        if (!$expired) {
            return;
        }

        foreach ($expired['rider']?->user?->fcmTokens ?? [] as $token) {
            $this->firebase->sendDataMessage($token->fcm_token, [
                'type' => 'order_alert_cancel',
                'order_id' => (string) $expired['order_id'],
                'reason' => $expired['reason'],
            ]);
        }

        if ($expired['should_reoffer']) {
            $this->offerNextRider((int) $expired['order_id']);
        }
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