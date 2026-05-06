<?php

namespace App\Models;

use App\Enums\NotificationTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Notifications\DatabaseNotification as BaseDatabaseNotification;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;

/**
 * @method static create(array $data)
 * @method static find($id)
 * @method static where(string $column, mixed $value)
 */

/**
 * Backward-compatible wrapper around Laravel's DatabaseNotification.
 *
 * This keeps the existing App\Models\Notification API mostly intact while
 * persisting notifications in the default `notifications` table using the
 * built-in Database channel columns: id, type, notifiable_type, notifiable_id,
 * data (json), read_at, timestamps.
 */
class Notification extends BaseDatabaseNotification
{
    use HasFactory, HasUuids;

    protected $table = 'notifications';

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'read_at' => 'datetime',
        'data' => 'array',
    ];

    /**
     * Get the user that owns the notification.
     */
    public function user(): BelongsTo
    {
        // Legacy helper: maps to notifiable when it's a User
        return $this->belongsTo(User::class, 'notifiable_id')
            ->where('notifiable_type', User::class);
    }

    /**
     * Get the store associated with the notification.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    /**
     * Get the order associated with the notification.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Scope to get unread notifications.
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope to get read notifications.
     */
    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Scope to filter by notification type.
     */
    public function scopeOfType($query, NotificationTypeEnum $type)
    {
        // Stored as string in `type` column
        return $query->where('type', $type->value ?? (string)$type);
    }

    /**
     * Scope to filter by sent_to.
     */
    public function scopeSentTo($query, string $sentTo)
    {
        // `sent_to` is stored inside JSON `data`
        return $query->where('data->sent_to', $sentTo);
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(): bool
    {
        return $this->forceFill(['read_at' => now()])->save();
    }

    /**
     * Mark notification as unread.
     */
    public function markAsUnread(): bool
    {
        return $this->forceFill(['read_at' => null])->save();
    }

    // -----------------------
    // Legacy attribute helpers
    // -----------------------

    public function getIsReadAttribute(): bool
    {
        return !is_null($this->read_at);
    }

    public function getTitleAttribute(): ?string
    {
        return Arr::get($this->data ?? [], 'title');
    }

    public function getMessageAttribute(): ?string
    {
        return Arr::get($this->data ?? [], 'message');
    }

    public function getMetadataAttribute(): ?array
    {
        return Arr::get($this->data ?? [], 'metadata');
    }

    public function getSentToAttribute(): ?string
    {
        return Arr::get($this->data ?? [], 'sent_to');
    }

    public function getStoreIdAttribute(): ?int
    {
        return Arr::get($this->data ?? [], 'store_id');
    }

    public function getOrderIdAttribute(): ?int
    {
        return Arr::get($this->data ?? [], 'order_id');
    }

    public function getUserIdAttribute(): ?int
    {
        // Prefer explicit user_id in data; fallback to notifiable_id when type is User
        return Arr::get($this->data ?? [], 'user_id', $this->notifiable_type === User::class ? (int)$this->notifiable_id : null);
    }

    /**
     * Resolve a panel-aware destination URL for this notification.
     */
    public function getRedirectUrlForPanel(string $panel): string
    {
        $panel = in_array($panel, ['admin', 'seller'], true) ? $panel : 'admin';
        $type = $this->data['type'] ?? "";
        $metadata = $this->metadata ?? [];
        $sellerOrderId = $this->data['metadata']['seller_order_id'] ?? $this->order_id;
            $orderId = ($panel == "admin") ? $this->order_id : $sellerOrderId;

        $productId = Arr::get($metadata, 'product_id');
        $withdrawalRequestId = Arr::get($metadata, 'withdrawal_request_id');
        $status = strtolower((string)Arr::get($metadata, 'new_status', Arr::get($metadata, 'status', '')));

        return match ($type) {
            NotificationTypeEnum::ORDER(),
            NotificationTypeEnum::NEW_ORDER(),
            NotificationTypeEnum::ORDER_UPDATE(),
            NotificationTypeEnum::PAYMENT(),
            NotificationTypeEnum::DELIVERY(),
            NotificationTypeEnum::ORDER_READY_FOR_PICKUP() => $this->resolveOrderUrl($panel, $orderId),

            NotificationTypeEnum::RETURN_ORDER(),
            NotificationTypeEnum::RETURN_ORDER_UPDATE(),
            NotificationTypeEnum::RETURN_ORDER_AVAILABLE() => $this->resolveReturnUrl($panel, $orderId),

            NotificationTypeEnum::WITHDRAWAL_REQUEST(),
            NotificationTypeEnum::WITHDRAWAL_PROCESS() => $this->resolveWithdrawalUrl($panel, $withdrawalRequestId, $status),

            NotificationTypeEnum::SETTLEMENT_CREATE() => $this->resolveNamedRoute($panel . '.commissions.index'),
            NotificationTypeEnum::SETTLEMENT_PROCESS() => $this->resolveSettlementUrl($panel),
            NotificationTypeEnum::WALLET_TRANSACTION() => $this->resolveWalletUrl($panel),
            NotificationTypeEnum::PRODUCT() => $this->resolveProductUrl($panel, $productId),

            default => $this->resolveNamedRoute($panel . '.notifications.index'),
        };
    }

    private function resolveOrderUrl(string $panel, mixed $orderId): string
    {
        if (!empty($orderId) && Route::has($panel . '.orders.show')) {
            return route($panel . '.orders.show', ['id' => $orderId]);
        }

        return $this->resolveNamedRoute($panel . '.orders.index', $panel . '.notifications.index');
    }

    private function resolveReturnUrl(string $panel, mixed $orderId): string
    {
        if ($panel === 'seller' && Route::has('seller.returns.index')) {
            return route('seller.returns.index');
        }

        return $this->resolveOrderUrl($panel, $orderId);
    }

    private function resolveWithdrawalUrl(string $panel, mixed $withdrawalRequestId, string $status): string
    {
        if ($panel === 'admin' && !empty($withdrawalRequestId) && Route::has('admin.seller-withdrawals.show')) {
            return route('admin.seller-withdrawals.show', ['id' => $withdrawalRequestId]);
        }

        if ($panel === 'seller') {
            $historyStatuses = ['approved', 'rejected', 'processed', 'completed'];

            if (in_array($status, $historyStatuses, true) && Route::has('seller.withdrawals.history')) {
                return route('seller.withdrawals.history');
            }

            return $this->resolveNamedRoute('seller.withdrawals.index', 'seller.notifications.index');
        }

        return $this->resolveNamedRoute($panel . '.notifications.index');
    }

    private function resolveSettlementUrl(string $panel): string
    {
        if ($panel === 'seller' && Route::has('seller.commissions.history')) {
            return route('seller.commissions.history');
        }

        return $this->resolveNamedRoute($panel . '.commissions.history', $panel . '.commissions.index', $panel . '.notifications.index');
    }

    private function resolveWalletUrl(string $panel): string
    {
        if ($panel === 'seller') {
            return $this->resolveNamedRoute('seller.wallet.transactions', 'seller.wallet.index', 'seller.notifications.index');
        }

        return $this->resolveNamedRoute('admin.wallet.transactions', 'admin.notifications.index');
    }

    private function resolveProductUrl(string $panel, mixed $productId): string
    {
        if (!empty($productId) && Route::has($panel . '.products.show')) {
            return route($panel . '.products.show', ['id' => $productId]);
        }

        return $this->resolveNamedRoute($panel . '.products.index', $panel . '.notifications.index');
    }

    private function resolveNamedRoute(string ...$routeNames): string
    {
        foreach ($routeNames as $routeName) {
            if (Route::has($routeName)) {
                return route($routeName);
            }
        }

        return '/';
    }
}
