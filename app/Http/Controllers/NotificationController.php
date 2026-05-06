<?php

namespace App\Http\Controllers;

use App\Enums\AdminPermissionEnum;
use App\Enums\DefaultSystemRolesEnum;
use App\Enums\NotificationTypeEnum;
use App\Enums\SellerPermissionEnum;
use App\Enums\SettingTypeEnum;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Models\Setting;
use App\Models\User;
use App\Services\FirebaseService;
use App\Services\NotificationService;
use App\Traits\ChecksPermissions;
use App\Traits\PanelAware;
use App\Types\Api\ApiResponseType;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use WpOrg\Requests\Auth;

class NotificationController extends Controller
{
    use AuthorizesRequests, ChecksPermissions, PanelAware;

    protected bool $editPermission = false;
    protected bool $deletePermission = false;
    protected bool $createPermission = false;
    protected int $sellerId = 0;
    protected $user;
    protected int $sellerUserId = 0;
    protected FirebaseService $firebase;

    public function __construct(protected NotificationService $notificationService, FirebaseService $firebase)
    {
        $this->firebase = $firebase;

        $user = $this->user = auth()->user();
        if ($user) {
            $seller = $user->seller();
            $this->sellerId = $seller ? $seller->id : 0;
            $this->sellerUserId = $seller?->user_id ?? 0;

            $enum = $this->getPanel() === 'seller'
                ? SellerPermissionEnum::class
                : AdminPermissionEnum::class;

            $isSeller = $user->hasRole(DefaultSystemRolesEnum::SELLER())
                || ($this->getPanel() === 'seller' && session('impersonating_as_seller', false));

            $this->editPermission = $this->hasPermission($enum::NOTIFICATION_EDIT()) || $isSeller;
            $this->deletePermission = $this->hasPermission($enum::NOTIFICATION_DELETE()) || $isSeller;
            $this->createPermission = $this->hasPermission($enum::NOTIFICATION_CREATE()) || $isSeller;
        }
    }

    // -------------------------------------------------------------------------
    // Index (Blade view)
    // -------------------------------------------------------------------------

    /**
     * Display a listing of notifications.
     */
    public function index(): View
    {
        $this->authorize('viewAny', Notification::class);

        $columns = [
            ['data' => 'id', 'name' => 'id', 'title' => __('labels.id')],
            ['data' => 'title', 'name' => 'title', 'title' => __('labels.title')],
            ['data' => 'message', 'name' => 'message', 'title' => __('labels.message')],
            ['data' => 'is_read', 'name' => 'is_read', 'title' => __('labels.status')],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => __('labels.created_at')],
            [
                'data' => 'action',
                'name' => 'action',
                'title' => __('labels.action'),
                'orderable' => false,
                'searchable' => false,
            ],
        ];

        $editPermission = $this->editPermission;
        $deletePermission = $this->deletePermission;
        $createPermission = $this->createPermission;

        return view(
            $this->panelView('notifications.index'),
            compact('columns', 'editPermission', 'deletePermission', 'createPermission')
        );
    }

    // -------------------------------------------------------------------------
    // CRUD
    // -------------------------------------------------------------------------

    /**
     * Store a newly created notification.
     *
     * All relational meta (user_id, store_id, order_id, sent_to, title,
     * message, metadata) lives inside the `data` JSON column — matching
     * Laravel's default notifications table schema.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $this->authorize('create', Notification::class);

            $validated = $request->validate([
                'user_id' => 'nullable|exists:users,id',
                'store_id' => 'nullable|exists:stores,id',
                'order_id' => 'nullable|exists:orders,id',
                'type' => ['required', new Enum(NotificationTypeEnum::class)],
                'sent_to' => 'required|string|in:admin,customer,seller',
                'title' => 'required|string|max:255',
                'message' => 'required|string',
                'metadata' => 'nullable|array',
            ]);

            $notification = $this->notificationService->createNotification($validated);

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: __('labels.notification_created_successfully'),
                data: new NotificationResource($notification),
                status: 201
            );
        } catch (ValidationException $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.validation_failed'),
                data: $e->errors()
            );
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.permission_denied'),
                data: []
            );
        }
    }

    /**
     * Display the specified notification.
     *
     * Eager-loads `notifiable` (the User morph) instead of the old
     * `user` / `store` / `order` direct relations — store and order are
     * looked up via the `data` JSON values when needed by the Resource.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $notification = Notification::with('notifiable')->findOrFail($id);

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: __('labels.notification_retrieved_successfully'),
                data: new NotificationResource($notification)
            );
        } catch (ModelNotFoundException) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.notification_not_found'),
                data: []
            );
        }
    }

    /**
     * Update the specified notification.
     *
     * Mutable fields (title, message, sent_to, metadata) are merged back
     * into the `data` JSON column. `type` maps to the `type` string column.
     * `is_read` toggles `read_at`.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $notification = Notification::findOrFail($id);
            $this->authorize('update', $notification);

            $validated = $request->validate([
                'title' => 'sometimes|required|string|max:255',
                'message' => 'sometimes|required|string',
                'type' => ['sometimes', 'required', new Enum(NotificationTypeEnum::class)],
                'sent_to' => 'sometimes|required|string|in:admin,customer,seller',
                'is_read' => 'sometimes|boolean',
                'metadata' => 'nullable|array',
            ]);

            // Merge changed keys into the existing `data` JSON blob.
            $data = $notification->data ?? [];

            foreach (['title', 'message', 'sent_to', 'metadata'] as $key) {
                if (array_key_exists($key, $validated)) {
                    $data[$key] = $validated[$key];
                }
            }

            $updates = ['data' => $data];

            if (array_key_exists('type', $validated)) {
                $updates['type'] = $validated['type'] instanceof NotificationTypeEnum
                    ? $validated['type']->value
                    : (string)$validated['type'];
            }

            if (array_key_exists('is_read', $validated)) {
                $updates['read_at'] = $validated['is_read'] ? now() : null;
            }

            $notification->update($updates);

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: __('labels.notification_updated_successfully'),
                data: new NotificationResource($notification)
            );
        } catch (ValidationException $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.validation_failed'),
                data: $e->errors()
            );
        } catch (ModelNotFoundException) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.notification_not_found'),
                data: []
            );
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.permission_denied'),
                data: []
            );
        }
    }

    /**
     * Remove the specified notification.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $notification = Notification::findOrFail($id);
            $this->authorize('delete', $notification);

            $this->notificationService->deleteNotification($id);

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: __('labels.notification_deleted_successfully'),
                data: []
            );
        } catch (ModelNotFoundException) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.notification_not_found'),
                data: []
            );
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.permission_denied'),
                data: []
            );
        }
    }

    // -------------------------------------------------------------------------
    // User-facing helpers
    // -------------------------------------------------------------------------

    /**
     * Get paginated notifications for the authenticated user.
     *
     * Filters by `notifiable_id = auth()->id()` — standard Laravel behaviour.
     */
    public function getUserNotifications(Request $request): JsonResponse
    {
        try {
            $this->authorize('viewAny', Notification::class);

            $perPage = $request->integer('per_page', 15);
            $userId = auth()->id();

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
        } catch (\Exception) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.error_retrieving_notifications'),
                data: []
            );
        }
    }

    /**
     * Get unread notifications count for the authenticated user.
     */
    public function getUnreadCount(): JsonResponse
    {
        try {
            $count = $this->notificationService->getUnreadCount(auth()->id());

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: __('labels.unread_count_retrieved_successfully'),
                data: ['unread_count' => $count]
            );
        } catch (\Exception) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.error_retrieving_unread_count'),
                data: []
            );
        }
    }

    // -------------------------------------------------------------------------
    // Read / Unread
    // -------------------------------------------------------------------------

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(string $id): JsonResponse
    {
        try {
            $notification = Notification::findOrFail($id);
            $this->authorize('markAsRead', $notification);

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
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.permission_denied'),
                data: []
            );
        }
    }

    /**
     * Mark a single notification as unread.
     */
    public function markAsUnread(string $id): JsonResponse
    {
        try {
            $notification = Notification::findOrFail($id);
            $this->authorize('update', $notification);

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
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.permission_denied'),
                data: []
            );
        }
    }

    /**
     * Mark all notifications as read.
     *
     * Uses the same base scope as the notifications table for the active panel,
     * so unread counts and bulk-read actions stay consistent with what is shown.
     */
    public function markAllAsRead(): JsonResponse
    {
        try {
            $this->authorize('readAll', Notification::class);

            $query = $this->panelNotificationQuery();
            $count = (clone $query)
                ->whereNull('read_at')
                ->count();

            if ($count === 0) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: __('labels.no_unread_notifications'),
                    data: []
                );
            }

            $query->whereNull('read_at')
                ->update(['read_at' => now()]);

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: __('labels.all_notifications_marked_as_read'),
                data: []
            );
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.permission_denied'),
                data: []
            );
        } catch (\Exception) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.error_marking_notifications_as_read'),
                data: []
            );
        }
    }

    // -------------------------------------------------------------------------
    // DataTables
    // -------------------------------------------------------------------------

    /**
     * Return notifications formatted for DataTables (server-side).
     *
     * Column mapping:
     *   id, type, created_at  → real columns
     *   title, message, sent_to → JSON_EXTRACT on `data`
     *   is_read               → derived from `read_at IS NULL`
     */
    public function getNotifications(Request $request): JsonResponse
    {
        try {
            $this->authorize('viewAny', Notification::class);

            $draw = (int)$request->get('draw', 1);
            $start = (int)$request->get('start', 0);
            $length = (int)$request->get('length', 15);
            $search = $request->get('search')['value'] ?? '';

            $orderColumnIndex = (int)($request->get('order')[0]['column'] ?? 0);
            $orderDirection = $request->get('order')[0]['dir'] === 'asc' ? 'asc' : 'desc';

            // Columns must match the `columns` array sent by DataTables JS.
            $columnMap = [
                0 => 'id',
                1 => 'title',
                2 => 'message',
                3 => 'type',
                4 => 'is_read',
                5 => 'created_at',
            ];
            $orderColumn = $columnMap[$orderColumnIndex] ?? 'created_at';

            // ------------------------------------------------------------------
            // Base query — scoped by panel
            // ------------------------------------------------------------------
            if (!in_array($this->getPanel(), ['admin', 'seller'], true)) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: __('labels.invalid_panel'),
                    data: []
                );
            }

            $query = $this->panelNotificationQuery();

            $totalRecords = $query->count();

            // ------------------------------------------------------------------
            // Search
            // ------------------------------------------------------------------
            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('data->title', 'like', "%{$search}%")
                        ->orWhere('data->message', 'like', "%{$search}%")
                        ->orWhere('type', 'like', "%{$search}%")
                        ->orWhere('data->sent_to', 'like', "%{$search}%");
                });
            }

            $filteredRecords = $query->count();

            // ------------------------------------------------------------------
            // Ordering
            // ------------------------------------------------------------------
            match ($orderColumn) {
                'title',
                'message',
                'sent_to' => $query->orderByRaw(
                    "JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.{$orderColumn}')) {$orderDirection}"
                ),
                'is_read' => $query->orderByRaw(
                    "(`read_at` IS NULL) {$orderDirection}"
                ),
                default => $query->orderBy($orderColumn, $orderDirection),
            };

            // ------------------------------------------------------------------
            // Paginate & load notifiable (User morph) eagerly
            // ------------------------------------------------------------------
            $notifications = $query
                ->with('notifiable')
                ->skip($start)
                ->take($length)
                ->get();

            $data = $notifications->map(fn(Notification $n) => [
                'id' => $n->id,
                'title' => $n->title . '<p class="mt-2 mb-0 fs-5">' . $n->id .'</p>',
                'message' => $n->message,
                'is_read' => $n->is_read
                    ? '<span class="badge delivered">' . __('labels.read') . '</span>'
                    : '<span class="badge inactive">' . __('labels.unread') . '</span>',
                'created_at' => $n->created_at->format('Y-m-d H:i:s'),
                'action' => $this->getActionButtons($n),
            ])->values()->all();
//                dd($data);

            return response()->json([
                'draw' => $draw,
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'draw' => (int)$request->get('draw', 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage(),
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // Firebase helpers (test endpoints)
    // -------------------------------------------------------------------------

    public function test(Request $request): JsonResponse
    {
        $token = $request->input('token');

        if (!$token) {
            return response()->json(['error' => 'Token is required'], 400);
        }

        try {
            $response = $this->firebase->sendNotification(
                token: $token,
                title: 'Test Notification 🚀',
                body: 'This is a test message from Laravel + Firebase.',
                data: ['type' => 'test', 'click_action' => 'click action']
            );

            return response()->json(['success' => true, 'response' => $response]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function sendBulk(Request $request): JsonResponse
    {
        $tokens = $request->input('tokens');

        if (empty($tokens) || !is_array($tokens)) {
            return response()->json(['error' => 'tokens array is required'], 400);
        }

        try {
            $result = $this->firebase->sendBulkNotification(
                tokens: $tokens,
                title: '🚀 Bulk Notification Test',
                body: 'This is a test message with automatic token cleanup.',
                data: ['type' => 'bulk_test']
            );

            return response()->json(['success' => true, 'summary' => $result]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Render action buttons for a DataTables row.
     */
    private function getActionButtons(Notification $notification): string
    {
        $actions = '';

        if ($this->editPermission) {
            if (!$notification->is_read) {
                $actions .= '<button class="btn btn-outline-success mark-read-btn me-2 p-1"'
                    . ' data-id="' . $notification->id . '"'
                    . ' title="' . __('labels.mark_as_read') . '">'
                    . '<i class="ti ti-check"></i></button>';
            } else {
                $actions .= '<button class="btn btn-outline-warning mark-unread-btn me-2 p-1"'
                    . ' data-id="' . $notification->id . '"'
                    . ' title="' . __('labels.mark_as_unread') . '">'
                    . '<i class="ti ti-x"></i></button>';
            }
        }

        $actions .= '<button class="btn btn-outline-primary view-notification-btn me-2 p-1"'
            . ' data-id="' . $notification->id . '"'
            . ' title="' . __('labels.view') . '">'
            . '<i class="ti ti-eye"></i></button>';

        if ($this->deletePermission) {
            $actions .= '<button class="btn btn-outline-danger delete-notification-btn p-1"'
                . ' data-id="' . $notification->id . '"'
                . ' title="' . __('labels.delete') . '">'
                . '<i class="ti ti-trash"></i></button>';
        }

        return $actions;
    }

    /**
     * Base notification query for the current panel.
     */
    private function panelNotificationQuery(): Builder
    {
        $userId = $this->getPanel() === 'seller'
            ? $this->sellerUserId
            : ($this->user->id ?? 0);

        return Notification::query()
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $userId);
    }

    public function testEmail(Request $request): JsonResponse
    {
        $systemSettings = Setting::find(SettingTypeEnum::SYSTEM())?->value ?? [];
        $appName = $systemSettings['appName'] ?? config('mail.from.name', config('app.name'));
        $mailFromAddress = config('mail.from.address');

        try {
            $validated = $request->validate([
                'email' => 'required|email',
            ]);

            $notification = new class($appName) extends BaseNotification {
                public function __construct(
                    private readonly string $appName
                ) {
                }

                public function via(object $notifiable): array
                {
                    return ['mail'];
                }

                public function toMail(object $notifiable): MailMessage
                {
                    return (new MailMessage)
                        ->subject('Order Placed Successfully')
                        ->greeting('Hello Test User,')
                        ->line("Your order 11 has been placed successfully.")
                        ->line('Order Total: 199.99')
                        ->line('Status: Pending')
                        ->action('View Order', url('/'))
                        ->line("Thank you for choosing {$this->appName}.");
                }
            };

            $validated['email'] = "user@gmaill.com";
            \Illuminate\Support\Facades\Notification::route('mail', $validated['email'])->notify($notification);

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: 'Test email sent successfully.',
                data: [
                    'email' => $validated['email'],
                    'from_name' => $appName,
                    'from_address' => $mailFromAddress,
                    'template' => 'laravel_mail',
                ]
            );
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'Unable to send test email.',
                data: [
                    'email' => $validated['email'],
                    'error' => $e->getMessage(),
                ],
                status: 422
            );
        }
    }
}
