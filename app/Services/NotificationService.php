<?php

namespace App\Services;

use App\Enums\NotificationTypeEnum;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Exception;

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
