<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Services\NotificationService;
use App\Types\Api\ApiResponseType;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationApiController extends Controller
{
    public function __construct(protected NotificationService $notificationService)
    {
        // Sanctum auth middleware is applied at the route level in existing structure
    }

    /**
     * List notifications for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->integer('per_page', 15);
            $userId = (int) auth()->id();

            $result = $this->notificationService->getUserNotifications($userId, $perPage);

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: __('labels.notifications_retrieved_successfully'),
                data: [
                    'notifications' => NotificationResource::collection($result['notifications']),
                    'unread_count' => $result['unread_count'],
                    'pagination' => $result['pagination'],
                ]
            );
        } catch (\Throwable) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.error_retrieving_notifications'),
                data: []
            );
        }
    }

    /**
     * Unread count for authenticated actor.
     */
    public function unreadCount(): JsonResponse
    {
        try {
            $count = $this->notificationService->getUnreadCount((int) auth()->id());

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: __('labels.unread_count_retrieved_successfully'),
                data: ['unread_count' => $count]
            );
        } catch (\Throwable) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.error_retrieving_unread_count'),
                data: []
            );
        }
    }

    /**
     * Mark a notification as read (belongs to the authenticated actor).
     */
    public function markAsRead(string $id): JsonResponse
    {
        try {
            /** @var Notification $notification */
            $notification = Notification::findOrFail($id);

            // Basic ownership check to ensure the auth user owns this notification
            if ((int) $notification->notifiable_id !== (int) auth()->id()) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: __('labels.permission_denied'),
                    data: []
                );
            }

            $this->notificationService->markAsRead($id);

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: __('labels.notification_marked_as_read'),
                data: []
            );
        } catch (ModelNotFoundException) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.notification_not_found'),
                data: []
            );
        } catch (\Throwable) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.something_went_wrong'),
                data: []
            );
        }
    }

    /**
     * Mark a notification as unread.
     */
    public function markAsUnread(string $id): JsonResponse
    {
        try {
            /** @var Notification $notification */
            $notification = Notification::findOrFail($id);

            if ((int) $notification->notifiable_id !== (int) auth()->id()) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: __('labels.permission_denied'),
                    data: []
                );
            }

            $notification->update(['read_at' => null]);

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: __('labels.notification_marked_as_unread'),
                data: []
            );
        } catch (ModelNotFoundException) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.notification_not_found'),
                data: []
            );
        } catch (\Throwable) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.something_went_wrong'),
                data: []
            );
        }
    }

    /**
     * Mark all notifications as read for authenticated actor.
     */
    public function markAllAsRead(): JsonResponse
    {
        try {
            $this->notificationService->markAllAsRead((int) auth()->id());

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: __('labels.all_notifications_marked_as_read'),
                data: []
            );
        } catch (\Throwable) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.something_went_wrong'),
                data: []
            );
        }
    }
}
