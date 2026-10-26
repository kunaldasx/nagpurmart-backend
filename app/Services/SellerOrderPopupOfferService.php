<?php

namespace App\Services;

use App\Enums\DefaultSystemRolesEnum;
use App\Enums\Order\OrderItemStatusEnum;
use App\Models\Seller;
use App\Models\SellerOrder;
use App\Models\SellerOrderPopupOffer;
use App\Models\SellerOrderPopupPresence;
use App\Models\SellerUser;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SellerOrderPopupOfferService
{
    private const RADIUS_KM = 0.2;
    private const LOCATION_FRESHNESS_MINUTES = 2;

    public function __construct(private FirebaseService $firebase)
    {
    }

    public function updatePresence(User $user, Seller $seller, bool $active, ?float $latitude, ?float $longitude): SellerOrderPopupPresence
    {
        $presence = SellerOrderPopupPresence::query()->firstOrNew([
            'seller_id' => $seller->id,
            'user_id' => $user->id,
        ]);
        $presence->active = $active;
        $presence->last_seen_at = now();

        if ($active) {
            $presence->latitude = $latitude;
            $presence->longitude = $longitude;
        }

        $presence->save();

        if ($active) {
            $this->dispatchPendingOrders((int) $seller->id);
        }

        return $presence->fresh();
    }

    public function dispatchPendingOrders(int $sellerId): void
    {
        $seller = Seller::query()->with('user')->find($sellerId);
        if (!$seller) {
            return;
        }

        $eligibleUsers = $this->eligibleUsers($seller);
        if ($eligibleUsers->isEmpty()) {
            return;
        }

        $orders = SellerOrder::query()
            ->where('seller_id', $sellerId)
            ->whereDoesntHave('popupOffers')
            ->whereHas('items.orderItem', fn ($query) => $query->whereIn('status', [
                OrderItemStatusEnum::AWAITING_STORE_RESPONSE(),
                OrderItemStatusEnum::ACCEPTED(),
            ]))
            ->with([
                'order.deliveryTimeSlot',
                'items.orderItem.store',
            ])
            ->orderBy('created_at')
            ->get();

        $now = now();
        foreach ($orders as $sellerOrder) {
            $order = $sellerOrder->order;
            if (!$order) {
                continue;
            }
            if ($order->order_mode === 'wholesale' && !$this->isWholesalePopupWindowOpen($order, $now)) {
                continue;
            }

            $stores = $sellerOrder->items
                ->map(fn ($item) => $item->orderItem?->store)
                ->filter(fn ($store) => $store && $store->latitude !== null && $store->longitude !== null)
                ->unique('id')
                ->values();
            if ($stores->isEmpty()) {
                continue;
            }

            $nearbyUsers = $eligibleUsers
                ->filter(fn (array $candidate) => $this->userIsNearStores($candidate['presence'], $stores))
                ->sortBy(fn (array $candidate) => $candidate['presence']->last_offer_at?->getTimestamp() ?? 0)
                ->values();

            foreach ($nearbyUsers as $candidate) {
                $offer = $this->reserveOffer($sellerOrder, $candidate['user'], $candidate['presence']);
                if (!$offer) {
                    continue;
                }

                $this->sendOfferNotification($sellerOrder, $candidate['user']);
                $eligibleUsers = $eligibleUsers->reject(
                    fn (array $user) => (int) $user['user']->id === (int) $candidate['user']->id
                )->values();
                break;
            }
        }
    }

    public function canUserProcessOrder(int $sellerOrderId, int $userId): bool
    {
        $offer = SellerOrderPopupOffer::query()->where('seller_order_id', $sellerOrderId)->first();

        return !$offer || (
            (int) $offer->user_id === $userId
            && in_array($offer->status, SellerOrderPopupOffer::ACTIVE_STATUSES, true)
        );
    }

    public function markAccepted(int $sellerOrderId, int $userId): void
    {
        SellerOrderPopupOffer::query()
            ->where('seller_order_id', $sellerOrderId)
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->update(['status' => 'accepted', 'accepted_at' => now()]);
    }

    public function markCompleted(int $sellerOrderId, int $sellerId, int $userId): void
    {
        SellerOrderPopupOffer::query()
            ->where('seller_order_id', $sellerOrderId)
            ->where('seller_id', $sellerId)
            ->where('user_id', $userId)
            ->whereIn('status', SellerOrderPopupOffer::ACTIVE_STATUSES)
            ->update(['status' => 'completed', 'completed_at' => now()]);

        $this->dispatchPendingOrders($sellerId);
    }

    private function eligibleUsers(Seller $seller): Collection
    {
        $now = now();
        $owner = $seller->user;
        $members = SellerUser::query()
            ->where('seller_id', $seller->id)
            ->where('status', 'active')
            ->where('login_approval_status', 'approved')
            ->where('login_approved_until', '>', $now)
            ->whereHas('user', fn ($query) => $query->where('status', true))
            ->with(['user.fcmTokens', 'user.sellerOrderPopupPresence'])
            ->get()
            ->pluck('user');
        $busyUserIds = SellerOrderPopupOffer::query()
            ->whereIn('status', SellerOrderPopupOffer::ACTIVE_STATUSES)
            ->pluck('user_id')
            ->map(fn ($userId) => (int) $userId)
            ->all();

        return collect([$owner])
            ->merge($members)
            ->filter()
            ->unique('id')
            ->filter(function (User $user) use ($seller, $now) {
                $isSellerOwner = (int) $seller->user_id === (int) $user->id;
                $canViewPopup = $user->hasRole(DefaultSystemRolesEnum::SELLER()) || $user->can('order.popup');
                $presence = $user->sellerOrderPopupPresence
                    ->first(fn (SellerOrderPopupPresence $row) => (int) $row->seller_id === (int) $seller->id);

                return $user->status
                    && ($isSellerOwner || $canViewPopup)
                    && $presence?->active
                    && $presence->last_seen_at?->greaterThanOrEqualTo($now->copy()->subMinutes(self::LOCATION_FRESHNESS_MINUTES))
                    && $presence->latitude !== null
                    && $presence->longitude !== null
                    && !in_array((int) $user->id, $busyUserIds, true);
            })
            ->map(fn (User $user) => [
                'user' => $user,
                'presence' => $user->sellerOrderPopupPresence
                    ->first(fn (SellerOrderPopupPresence $row) => (int) $row->seller_id === (int) $seller->id),
            ])
            ->values();
    }

    private function reserveOffer(SellerOrder $sellerOrder, User $user, SellerOrderPopupPresence $presence): ?SellerOrderPopupOffer
    {
        return DB::transaction(function () use ($sellerOrder, $user, $presence) {
            $lockedOrder = SellerOrder::query()->whereKey($sellerOrder->id)->lockForUpdate()->first();
            $lockedPresence = SellerOrderPopupPresence::query()->whereKey($presence->id)->lockForUpdate()->first();
            if (!$lockedOrder || !$lockedPresence || SellerOrderPopupOffer::query()->where('seller_order_id', $lockedOrder->id)->exists()) {
                return null;
            }

            $now = now();
            if (!$lockedPresence->active
                || !$lockedPresence->last_seen_at?->greaterThanOrEqualTo($now->copy()->subMinutes(self::LOCATION_FRESHNESS_MINUTES))
                || SellerOrderPopupOffer::query()
                    ->where('user_id', $user->id)
                    ->whereIn('status', SellerOrderPopupOffer::ACTIVE_STATUSES)
                    ->exists()) {
                return null;
            }

            $offer = SellerOrderPopupOffer::query()->create([
                'seller_id' => $lockedOrder->seller_id,
                'seller_order_id' => $lockedOrder->id,
                'user_id' => $user->id,
                'status' => 'pending',
                'assigned_at' => $now,
            ]);
            $lockedPresence->update(['last_offer_at' => $now]);

            return $offer;
        });
    }

    private function sendOfferNotification(SellerOrder $sellerOrder, User $user): void
    {
        if ($user->fcmTokens->isEmpty()) {
            Log::warning('Seller order popup offer reserved without an FCM token.', [
                'seller_order_id' => $sellerOrder->id,
                'seller_id' => $sellerOrder->seller_id,
                'user_id' => $user->id,
            ]);
            return;
        }

        $order = $sellerOrder->order;
        $payload = [
            'type' => 'seller_new_order',
            'seller_order_id' => (string) $sellerOrder->id,
            'order_id' => (string) $sellerOrder->order_id,
            'order_number' => (string) ($order?->order_number ?? $sellerOrder->order_id),
            'order_mode' => (string) ($order?->order_mode ?? 'regular'),
        ];
        $title = 'New order received';
        $body = 'Order #' . ($order?->order_number ?? $sellerOrder->order_id) . ' is waiting for your review.';

        foreach ($user->fcmTokens as $token) {
            $result = $this->firebase->sendHighPriorityNotification($token->fcm_token, $title, $body, $payload);
            if (!$result['success']) {
                Log::error('Seller order popup push failed after offer reservation.', [
                    'seller_order_id' => $sellerOrder->id,
                    'seller_id' => $sellerOrder->seller_id,
                    'user_id' => $user->id,
                    'error' => $result['error'] ?? 'unknown',
                ]);
            }
        }
    }

    private function userIsNearStores(SellerOrderPopupPresence $presence, Collection $stores): bool
    {
        foreach ($stores as $store) {
            if (DeliveryZoneService::calculateDistance(
                (float) $presence->latitude,
                (float) $presence->longitude,
                (float) $store->latitude,
                (float) $store->longitude
            ) <= self::RADIUS_KM) {
                return true;
            }
        }

        return false;
    }

    private function isWholesalePopupWindowOpen(object $order, $now): bool
    {
        if (!$order->delivery_date || !$order->deliveryTimeSlot?->end_time) {
            return false;
        }

        $slotEnd = \Carbon\Carbon::parse($order->delivery_date->format('Y-m-d') . ' ' . $order->deliveryTimeSlot->end_time);

        return $slotEnd->betweenIncluded($now, $now->copy()->addMinutes(30));
    }
}
