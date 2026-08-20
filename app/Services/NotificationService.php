<?php

namespace App\Services;

use App\Enums\GuardNameEnum;
use App\Enums\NotificationTypeEnum;
use App\Models\CustomerBroadcastNotification;
use App\Models\Notification;
use App\Models\User;
use App\Notifications\CustomerBroadcastNotification as CustomerBroadcastNotificationNotification;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Facades\Storage;

class NotificationService
{
    /**
     * Create a new notification.
     *
     * All relational meta (user_id, store_id, order_id, sent_to, title,
     * message, metadata) is stored inside the `data` JSON column.
     * The morph columns `notifiable_type` / `notifiable_id` are set when
     * a user_id is provided — matching Laravel's standard Database channel.
     */
    public function createNotification(array $data): Notification
    {
        try {
            DB::beginTransaction();

            $userId = $data['user_id'] ?? null;
            $type = $data['type'] ?? NotificationTypeEnum::GENERAL;

            $payload = [
                'title' => $data['title'] ?? null,
                'message' => $data['message'] ?? null,
                'metadata' => $data['metadata'] ?? null,
                'sent_to' => $data['sent_to'] ?? 'admin',
                'store_id' => $data['store_id'] ?? null,
                'order_id' => $data['order_id'] ?? null,
                // Keep user_id in data for legacy read queries, but the
                // authoritative owner reference is the morph columns below.
                'user_id' => $userId,
            ];

            $attributes = [
                'type' => $type instanceof NotificationTypeEnum ? $type->value : (string)$type,
                'data' => $payload,
                'notifiable_type' => User::class ,
                // Fall back to a sentinel value of 0 when there is no specific
                // user (e.g. admin-wide or seller-wide notifications).
                'notifiable_id' => $userId ?? 0,
            ];

            /** @var Notification $notification */
            $notification = Notification::query()->create($attributes);

            DB::commit();

            return $notification;

        }
        catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get paginated notifications for a specific user.
     *
     * Uses only the standard morph columns — no dual OR on `data->user_id`.
     */
    public function getUserNotifications(int $userId, int $perPage = 15): array
    {
        $notifications = Notification::where('notifiable_type', User::class)
            ->where('notifiable_id', $userId)
            ->with('notifiable')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return [
            'notifications' => $notifications->items(),
            'unread_count' => $this->getUnreadCount($userId),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        ];
    }

    /**
     * Get paginated notifications filtered by `sent_to` value (admin / seller / customer).
     */
    public function getNotificationsBySentTo(string $sentTo, int $perPage = 15): array
    {
        $notifications = Notification::sentTo($sentTo)
            ->with('notifiable')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return [
            'notifications' => $notifications->items(),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        ];
    }

    /**
     * Get unread notifications count for a user.
     */
    public function getUnreadCount(int $userId): int
    {
        return Notification::where('notifiable_type', User::class)
            ->where('notifiable_id', $userId)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(string|int $notificationId): bool
    {
        $notification = Notification::findOrFail($notificationId);

        return $notification->markAsRead();
    }

    /**
     * Mark all notifications as read for a specific user (seller panel).
     */
    public function markAllAsRead(int $userId): bool
    {
        try {
            DB::beginTransaction();

            Notification::where('notifiable_type', User::class)
                ->where('notifiable_id', $userId)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            DB::commit();

            return true;

        }
        catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Mark all admin-targeted notifications as read (admin panel).
     */
    public function markAllAsReadAdmin(): bool
    {
        try {
            DB::beginTransaction();

            Notification::sentTo('admin')
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            DB::commit();

            return true;

        }
        catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete a notification.
     */
    public function deleteNotification(string|int $notificationId): bool
    {
        $notification = Notification::findOrFail($notificationId);

        return (bool)$notification->delete();
    }

    /**
     * Get paginated notifications filtered by type.
     */
    public function getNotificationsByType(NotificationTypeEnum $type, int $perPage = 15): array
    {
        $notifications = Notification::ofType($type)
            ->with('notifiable')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return [
            'notifications' => $notifications->items(),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        ];
    }

    /**
     * Send the same notification to multiple users.
     *
     * Each notification is created individually so every record gets its own
     * `notifiable_id`, keeping ownership unambiguous.
     *
     * @param  int[]  $userIds
     * @return \Illuminate\Support\Collection<int, Notification>
     */
    public function sendBulkNotifications(array $userIds, array $notificationData): \Illuminate\Support\Collection
    {
        try {
            DB::beginTransaction();

            $notifications = collect();

            foreach ($userIds as $userId) {
                $notification = $this->createNotification(
                    array_merge($notificationData, ['user_id' => $userId])
                );
                $notifications->push($notification);
            }

            DB::commit();

            return $notifications;

        }
        catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function createCustomerBroadcastNotification(array $data): CustomerBroadcastNotification
    {
        $metadata = is_array($data['metadata'] ?? null) ? $data['metadata'] : [];
        $targetCategories = $this->normalizeTargetCategories($data['target_categories'] ?? $metadata['target_categories'] ?? null);
        $imageUrl = $data['image_url'] ?? null;

        if (! empty($data['image_file']) && $data['image_file'] instanceof UploadedFile) {
            $path = Storage::disk('public')->putFile('customer-broadcasts', $data['image_file']);
            $imageUrl = Storage::disk('public')->url($path);
        }

        $expiresAt = $data['expires_at'] ?? null;
        if (is_string($expiresAt) && trim($expiresAt) !== '') {
            $expiresAt = \Carbon\Carbon::parse($expiresAt);
        }

        return CustomerBroadcastNotification::create([
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'image_url' => $imageUrl,
            'action_url' => $data['action_url'] ?? null,
            'deep_link' => $data['deep_link'] ?? null,
            'target_categories' => $targetCategories,
            'expires_at' => $expiresAt,
            'priority' => (int) ($data['priority'] ?? 0),
            'is_active' => filter_var($data['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'status' => 'draft',
            'sent_count' => 0,
            'recipient_count' => 0,
            'metadata' => array_merge($metadata, [
                'target_categories' => $targetCategories,
                'deep_link' => $data['deep_link'] ?? null,
                'priority' => (int) ($data['priority'] ?? 0),
                'is_active' => filter_var($data['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
            ]),
            'created_by' => $data['created_by'] ?? null,
        ]);
    }

    public function updateCustomerBroadcastNotification(CustomerBroadcastNotification $broadcast, array $data): CustomerBroadcastNotification
    {
        $metadata = is_array($data['metadata'] ?? null) ? $data['metadata'] : ($broadcast->metadata ?? []);
        $targetCategories = $this->normalizeTargetCategories($data['target_categories'] ?? $broadcast->target_categories);
        $imageUrl = $broadcast->image_url;

        if (! empty($data['image_file']) && $data['image_file'] instanceof UploadedFile) {
            $path = Storage::disk('public')->putFile('customer-broadcasts', $data['image_file']);
            $imageUrl = Storage::disk('public')->url($path);
        } elseif (array_key_exists('image_url', $data)) {
            $imageUrl = $data['image_url'];
        }

        $attributes = [
            'title' => $data['title'] ?? $broadcast->title,
            'description' => $data['description'] ?? $broadcast->description,
            'image_url' => $imageUrl,
            'action_url' => $data['action_url'] ?? $broadcast->action_url,
            'deep_link' => $data['deep_link'] ?? $broadcast->deep_link,
            'target_categories' => $targetCategories,
            'expires_at' => $data['expires_at'] ?? $broadcast->expires_at,
            'priority' => (int) ($data['priority'] ?? $broadcast->priority),
            'is_active' => filter_var($data['is_active'] ?? $broadcast->is_active, FILTER_VALIDATE_BOOLEAN),
            'metadata' => array_merge($metadata, ['target_categories' => $targetCategories]),
            'status' => 'draft',
            'sent_at' => null,
            'sent_count' => 0,
            'recipient_count' => 0,
        ];

        $broadcast->update($attributes);

        return $broadcast->fresh();
    }

    public function deleteCustomerBroadcastNotification(CustomerBroadcastNotification $broadcast): bool
    {
        return (bool) $broadcast->delete();
    }

    public function sendCustomerBroadcastNotification(CustomerBroadcastNotification $broadcast): CustomerBroadcastNotification
    {
        $broadcast->update(['status' => 'sending']);

        if (! $broadcast->is_active) {
            $broadcast->update([
                'status' => 'inactive',
                'sent_at' => null,
                'recipient_count' => 0,
                'sent_count' => 0,
            ]);

            return $broadcast;
        }

        if ($broadcast->expires_at && now()->greaterThanOrEqualTo($broadcast->expires_at)) {
            $broadcast->update([
                'status' => 'expired',
                'sent_at' => null,
                'recipient_count' => 0,
                'sent_count' => 0,
            ]);

            return $broadcast;
        }

        $customerUsers = User::query()
            ->where(function ($query) {
                $query->whereNull('access_panel')
                    ->orWhere('access_panel', GuardNameEnum::WEB());
            })
            ->where('status', true)
            ->get();

        if ($customerUsers->isEmpty()) {
            $broadcast->update([
                'status' => 'sent',
                'sent_at' => now(),
                'recipient_count' => 0,
                'sent_count' => 0,
            ]);

            return $broadcast;
        }

        $metadata = array_merge($broadcast->metadata ?? [], [
            'broadcast_id' => $broadcast->id,
            'image_url' => $broadcast->image_url,
            'action_url' => $broadcast->action_url,
            'deep_link' => $broadcast->deep_link,
            'target_categories' => $broadcast->target_categories ?? [],
            'expires_at' => $broadcast->expires_at?->toIso8601String(),
            'priority' => $broadcast->priority,
            'is_active' => $broadcast->is_active,
        ]);

        try {
            foreach ($customerUsers as $customer) {
                NotificationFacade::sendNow($customer, new CustomerBroadcastNotificationNotification(
                    title: $broadcast->title,
                    description: $broadcast->description,
                    imageUrl: $broadcast->image_url,
                    actionUrl: $broadcast->action_url,
                    deepLink: $broadcast->deep_link,
                    priority: $broadcast->priority,
                    isActive: $broadcast->is_active,
                    metadata: $metadata
                ));
            }
        } catch (\Throwable $exception) {
            $broadcast->update(['status' => 'failed']);
            throw $exception;
        }

        $broadcast->update([
            'status' => 'sent',
            'sent_at' => now(),
            'recipient_count' => $customerUsers->count(),
            'sent_count' => $customerUsers->count(),
        ]);

        return $broadcast;
    }

    public function getActiveCustomerCampaigns(int $perPage = 15): array
    {
        $campaigns = CustomerBroadcastNotification::query()
            ->where('is_active', true)
            ->where('status', 'sent')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('priority')
            ->orderByDesc('sent_at')
            ->paginate($perPage);

        return [
            'items' => $campaigns->items(),
            'pagination' => [
                'current_page' => $campaigns->currentPage(),
                'last_page' => $campaigns->lastPage(),
                'per_page' => $campaigns->perPage(),
                'total' => $campaigns->total(),
            ],
        ];
    }

    protected function normalizeTargetCategories(mixed $categories): array
    {
        if (is_array($categories)) {
            return array_values(array_filter(array_map(fn ($category) => trim((string) $category), $categories)));
        }

        if (is_string($categories)) {
            return array_values(array_filter(array_map(fn ($category) => trim($category), explode(',', $categories))));
        }

        return [];
    }

    public function getHeaderNotifications(int $userId, string $sentTo = null): array
    {
        $query = Notification::query()
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $userId);

        // Optional: filter by panel (admin/seller)
        if (!empty($sentTo)) {
            $query->where('sent_to', $sentTo);
        }

        // Unread count
        $unreadCount = (clone $query)
            ->where('read_at', null)
            ->count();

        // Latest notifications for header
        $notifications = $query
            ->latest()
            ->limit(10)
            ->get();

        return [
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ];
    }
}
