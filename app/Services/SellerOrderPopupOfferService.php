<?php

namespace App\Services;

use App\Enums\DefaultSystemRolesEnum;
use App\Enums\Order\OrderItemStatusEnum;
use App\Models\Seller;
use App\Models\SellerOrder;
use App\Models\SellerOrderPopupOffer;
use App\Models\SellerOrderPopupPresence;
use App\Models\Store;
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

        $systemUsers = $this->eligibleSystemUsers($seller);
        $sellerOwner = $this->eligibleSellerOwner($seller);
        if ($systemUsers->isEmpty() && !$sellerOwner) {
            return;
        }

        $orders = SellerOrder::query()
            ->where('seller_id', $sellerId)
            ->whereDoesntHave('popupOffers', fn ($query) => $query->where('status', '!=', 'notification_failed'))
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

            $nearbySystemUsers = $systemUsers
                ->filter(fn (array $candidate) => $this->userIsNearStores($candidate['presence'], $stores))
                ->sortBy(fn (array $candidate) => $candidate['presence']->last_offer_at?->getTimestamp() ?? 0)
                ->values();

            $assigned = false;
            foreach ($nearbySystemUsers as $candidate) {
                $offer = $this->reserveOffer($sellerOrder, $candidate['user'], $candidate['presence']);
                if (!$offer) {
                    continue;
                }

                if (!$this->sendOfferNotification($sellerOrder, $candidate['user'])) {
                    $offer->update(['status' => 'notification_failed']);
                    continue;
                }
                $systemUsers = $systemUsers->reject(
                    fn (array $user) => (int) $user['user']->id === (int) $candidate['user']->id
                )->values();
                if ($sellerOwner && (int) $sellerOwner['user']->id === (int) $candidate['user']->id) {
                    $sellerOwner = null;
                }
                $assigned = true;
                break;
            }

            if (!$assigned && $sellerOwner) {
                $offer = $this->reserveOffer($sellerOrder, $sellerOwner['user'], $sellerOwner['presence']);
                if ($offer) {
                    if ($this->sendOfferNotification($sellerOrder, $sellerOwner['user'])) {
                        $sellerOwner = null;
                    } else {
                        $offer->update(['status' => 'notification_failed']);
                    }
                }
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

    public function popupUserDashboard(Seller $seller): array
    {
        $now = now();
        $stores = Store::query()
            ->where('seller_id', $seller->id)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get(['id', 'name', 'latitude', 'longitude']);
        $activeOffers = SellerOrderPopupOffer::query()
            ->whereIn('status', SellerOrderPopupOffer::ACTIVE_STATUSES)
            ->with(['sellerOrder.order:id,order_number,order_mode'])
            ->get()
            ->keyBy('user_id');

        $users = SellerUser::query()
            ->where('seller_id', $seller->id)
            ->with(['user.roles', 'user.fcmTokens', 'user.sellerOrderPopupPresence'])
            ->get()
            ->map(function (SellerUser $membership) use ($seller, $stores, $activeOffers, $now) {
                $user = $membership->user;
                if (!$user) {
                    return null;
                }

                $presence = $user->sellerOrderPopupPresence
                    ->first(fn (SellerOrderPopupPresence $row) => (int) $row->seller_id === (int) $seller->id);
                $lastSeen = $presence?->last_seen_at;
                $locationFresh = $lastSeen?->greaterThanOrEqualTo($now->copy()->subMinutes(self::LOCATION_FRESHNESS_MINUTES)) ?? false;
                $isActive = $membership->status === 'active'
                    && $presence?->active
                    && $locationFresh;
                $permissionGranted = $user->can('order.popup');
                $loginApproved = $membership->login_approval_status === 'approved'
                    && $membership->login_approved_until?->isFuture();
                $offer = $activeOffers->get($user->id);
                $nearestStoreDistanceKm = null;
                $nearestStore = null;

                if ($presence?->latitude !== null && $presence?->longitude !== null) {
                    foreach ($stores as $store) {
                        $distance = DeliveryZoneService::calculateDistance(
                            (float) $presence->latitude,
                            (float) $presence->longitude,
                            (float) $store->latitude,
                            (float) $store->longitude
                        );
                        if ($nearestStoreDistanceKm === null || $distance < $nearestStoreDistanceKm) {
                            $nearestStoreDistanceKm = $distance;
                            $nearestStore = $store;
                        }
                    }
                }

                $withinRadius = $nearestStoreDistanceKm !== null && $nearestStoreDistanceKm <= self::RADIUS_KM;
                $eligible = (bool) $user->status
                    && $membership->status === 'active'
                    && $loginApproved
                    && $permissionGranted
                    && $isActive
                    && $withinRadius
                    && !$offer;
                $reason = match (true) {
                    !(bool) $user->status => 'account_inactive',
                    $membership->status !== 'active' => 'system_user_inactive',
                    !$loginApproved => 'login_not_approved_or_expired',
                    !$permissionGranted => 'order_popup_permission_missing',
                    !$presence?->active => 'popup_presence_inactive',
                    !$locationFresh => 'location_stale',
                    $nearestStoreDistanceKm === null => 'location_or_store_unavailable',
                    !$withinRadius => 'outside_200m_radius',
                    (bool) $offer => 'order_in_progress',
                    default => null,
                };

                return [
                    'user_id' => (int) $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'mobile' => $user->mobile,
                    'roles' => $user->roles->pluck('name')->values(),
                    'system_user_status' => $membership->status,
                    'login_approval_status' => $membership->login_approval_status,
                    'login_approved_until' => $membership->login_approved_until?->toIso8601String(),
                    'has_order_popup_permission' => $permissionGranted,
                    'popup_presence_active' => (bool) $presence?->active,
                    'location_fresh' => $locationFresh,
                    'last_seen_at' => $lastSeen?->toIso8601String(),
                    'nearest_store' => $nearestStore ? [
                        'id' => (int) $nearestStore->id,
                        'name' => $nearestStore->name,
                        'distance_m' => (int) round($nearestStoreDistanceKm * 1000),
                    ] : null,
                    'has_fcm_token' => $user->fcmTokens->isNotEmpty(),
                    'is_active' => $isActive,
                    'is_eligible' => $eligible,
                    'ineligible_reason' => $reason,
                    'current_offer' => $offer ? [
                        'seller_order_id' => (int) $offer->seller_order_id,
                        'order_number' => $offer->sellerOrder?->order?->order_number,
                        'order_mode' => $offer->sellerOrder?->order?->order_mode,
                        'status' => $offer->status,
                        'assigned_at' => $offer->assigned_at?->toIso8601String(),
                    ] : null,
                ];
            })
            ->filter()
            ->values();

        return [
            'summary' => [
                'total_system_users' => $users->count(),
                'active_users' => $users->where('is_active', true)->count(),
                'eligible_users' => $users->where('is_eligible', true)->count(),
                'inactive_users' => $users->where('is_active', false)->count(),
                'busy_users' => $users->filter(fn (array $user) => $user['current_offer'] !== null)->count(),
            ],
            'users' => $users->all(),
            'location_freshness_minutes' => self::LOCATION_FRESHNESS_MINUTES,
            'pickup_radius_meters' => (int) (self::RADIUS_KM * 1000),
        ];
    }

    private function eligibleSystemUsers(Seller $seller): Collection
    {
        $now = now();
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

        return $members
            ->filter()
            ->unique('id')
            ->filter(function (User $user) use ($seller, $now, $busyUserIds) {
                if ((int) $user->id === (int) $seller->user_id) {
                    return false;
                }

                $canViewPopup = $user->can('order.popup');
                $presence = $user->sellerOrderPopupPresence
                    ->first(fn (SellerOrderPopupPresence $row) => (int) $row->seller_id === (int) $seller->id);

                return $user->status
                    && $canViewPopup
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

    private function eligibleSellerOwner(Seller $seller): ?array
    {
        $owner = $seller->user;
        if (!$owner || !(bool) $owner->status) {
            return null;
        }
        $presence = SellerOrderPopupPresence::query()
            ->where('seller_id', $seller->id)
            ->where('user_id', $owner->id)
            ->where('active', true)
            ->where('last_seen_at', '>=', now()->subMinutes(self::LOCATION_FRESHNESS_MINUTES))
            ->first();

        if (!$presence || SellerOrderPopupOffer::query()
            ->where('user_id', $owner->id)
            ->whereIn('status', SellerOrderPopupOffer::ACTIVE_STATUSES)
            ->exists()) {
            return null;
        }

        return ['user' => $owner, 'presence' => $presence];
    }

    private function reserveOffer(SellerOrder $sellerOrder, User $user, SellerOrderPopupPresence $presence): ?SellerOrderPopupOffer
    {
        return DB::transaction(function () use ($sellerOrder, $user, $presence) {
            $lockedOrder = SellerOrder::query()->whereKey($sellerOrder->id)->lockForUpdate()->first();
            $lockedPresence = SellerOrderPopupPresence::query()->whereKey($presence->id)->lockForUpdate()->first();
            $existingOffer = $lockedOrder
                ? SellerOrderPopupOffer::query()
                    ->where('seller_order_id', $lockedOrder->id)
                    ->lockForUpdate()
                    ->first()
                : null;
            if (!$lockedOrder || !$lockedPresence
                || ($existingOffer && $existingOffer->status !== 'notification_failed')) {
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

            if ($existingOffer) {
                $existingOffer->update([
                    'user_id' => $user->id,
                    'status' => 'pending',
                    'assigned_at' => $now,
                    'accepted_at' => null,
                    'completed_at' => null,
                ]);
                $offer = $existingOffer;
            } else {
                $offer = SellerOrderPopupOffer::query()->create([
                    'seller_id' => $lockedOrder->seller_id,
                    'seller_order_id' => $lockedOrder->id,
                    'user_id' => $user->id,
                    'status' => 'pending',
                    'assigned_at' => $now,
                ]);
            }
            $lockedPresence->update(['last_offer_at' => $now]);

            return $offer;
        });
    }

    private function sendOfferNotification(SellerOrder $sellerOrder, User $user): bool
    {
        if ($user->fcmTokens->isEmpty()) {
            Log::warning('Seller order popup offer reserved without an FCM token.', [
                'seller_order_id' => $sellerOrder->id,
                'seller_id' => $sellerOrder->seller_id,
                'user_id' => $user->id,
            ]);
            return false;
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

        $sent = false;
        foreach ($user->fcmTokens as $token) {
            $result = $this->firebase->sendHighPriorityNotification($token->fcm_token, $title, $body, $payload);
            if ($result['success']) {
                $sent = true;
            } else {
                Log::error('Seller order popup push failed after offer reservation.', [
                    'seller_order_id' => $sellerOrder->id,
                    'seller_id' => $sellerOrder->seller_id,
                    'user_id' => $user->id,
                    'error' => $result['error'] ?? 'unknown',
                ]);
            }
        }

        return $sent;
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
