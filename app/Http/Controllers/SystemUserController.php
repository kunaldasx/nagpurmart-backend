<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AdminPermissionEnum;
use App\Enums\DefaultSystemRolesEnum;
use App\Enums\SellerPermissionEnum;
use App\Enums\SettingTypeEnum;
use App\Http\Requests\SystemUser\StoreSystemUserRequest;
use App\Http\Requests\SystemUser\UpdateSystemUserRequest;
use App\Models\Seller;
use App\Models\SellerUser;
use App\Models\Setting;
use App\Models\User;
use App\Services\SettingService;
use App\Services\SubscriptionUsageService;
use App\Traits\SubscriptionLimitGuard;
use App\Traits\ChecksPermissions;
use App\Traits\PanelAware;
use App\Types\Api\ApiResponseType;
use App\Enums\Subscription\SubscriptionPlanKeyEnum;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class SystemUserController extends Controller
{
    use PanelAware, AuthorizesRequests, ChecksPermissions, SubscriptionLimitGuard;

    protected bool $editPermission = false;
    protected bool $deletePermission = false;
    protected bool $createPermission = false;

    protected SettingService $settingService;

    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
        $enum = $this->getPanel() === 'seller' ? SellerPermissionEnum::class : AdminPermissionEnum::class;
        $user = auth()->user();
        $this->editPermission = $this->hasPermission($enum::SYSTEM_USER_EDIT()) || $user->hasRole(DefaultSystemRolesEnum::SELLER());
        $this->deletePermission = $this->hasPermission($enum::SYSTEM_USER_DELETE()) || $user->hasRole(DefaultSystemRolesEnum::SELLER());
        $this->createPermission = $this->hasPermission($enum::SYSTEM_USER_CREATE()) || $user->hasRole(DefaultSystemRolesEnum::SELLER());
    }

    private function isDemoModeEnabled(): bool
    {
        $resource = $this->settingService->getSettingByVariable(SettingTypeEnum::SYSTEM());
        $settings = $resource ? ($resource->toArray(request())['value'] ?? []) : [];
        return (bool)($settings['demoMode'] ?? false);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $this->authorize('viewAny', User::class);
        if ($this->getPanel() == 'seller') {
            $query = Role::query();
            $user = auth()->user();
            $seller = $user->seller();
            if ($seller) {
                $query->where('guard_name', 'seller')->where('team_id', $seller->id);
            }
            $query->whereNotIn('name', [DefaultSystemRolesEnum::SUPER_ADMIN(), DefaultSystemRolesEnum::CUSTOMER(), DefaultSystemRolesEnum::SELLER()]);
            $roles = $query->get();
        } else {
            $roles = Role::where('guard_name', 'admin')
                ->get();
        };
        $columns = [
            ['data' => 'id', 'name' => 'id', 'title' => __('labels.id')],
            ['data' => 'name', 'name' => 'name', 'title' => __('labels.name')],
            ['data' => 'email', 'name' => 'email', 'title' => __('labels.email')],
            ['data' => 'mobile', 'name' => 'mobile', 'title' => __('labels.mobile')],
            ['data' => 'role', 'name' => 'role', 'title' => __('labels.role')],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => __('labels.created_at')],
            ['data' => 'action', 'name' => 'action', 'title' => __('labels.action'), 'orderable' => false, 'searchable' => false],
        ];
        $editPermission = $this->editPermission;
        $createPermission = $this->createPermission;
        $systemUserCreateLimitReached = false;
        $systemUserCreateLimitMessage = null;

        if (
            $this->getPanel() === 'seller'
            && $createPermission
            && Setting::isSystemVendorTypeMultiple()
            && Setting::isSubscriptionEnabled()
        ) {
            $limitKey = SubscriptionPlanKeyEnum::SYSTEM_USER_LIMIT();
            $usageService = app(SubscriptionUsageService::class);
            $limit = $usageService->getLimit((int)$seller->id, $limitKey);
            $used = $usageService->getUsage((int)$seller->id, $limitKey);

            if ($limit >= 0 && $used >= $limit) {
                $systemUserCreateLimitReached = true;
                $systemUserCreateLimitMessage = __('labels.subscription_limit_exceeded', [
                    'key' => Str::ucfirst(Str::replace('_', ' ', $limitKey)),
                    'limit' => $limit,
                    'used' => $used,
                    'remaining' => max(0, $limit - $used),
                ]);
            }
        }

        return view($this->panelView('system_users.index'), compact(
            'roles',
            'columns',
            'editPermission',
            'createPermission',
            'systemUserCreateLimitReached',
            'systemUserCreateLimitMessage'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSystemUserRequest $request): JsonResponse
    {
        try {
            $this->authorize('create', User::class);
        } catch (AuthorizationException $e) {
            return ApiResponseType::sendJsonResponse(false, 'labels.permission_denied', [], 403);
        }

        $validated = $request->validated();
        $validated['access_panel'] = $this->getPanel();
        $user = auth()->user();

        // Check if the access panel is 'seller' and ensure the seller exists.
        $seller = $user->seller();
        if ($this->getPanel() == 'seller') {
            if (!$seller) {
                return ApiResponseType::sendJsonResponse(false, 'labels.seller_not_found', [], 404);
            }
            $validated['seller_id'] = $seller->id;

            // Pre-check subscription usage for system_user_limit before creating (multivendor only)
            if ($error = $this->ensureCanUseOrError($validated['seller_id'], SubscriptionPlanKeyEnum::SYSTEM_USER_LIMIT())) {
                return $error;
            }
        }

        // Store the user in the User model
        $newUser = new User();
        $newUser->name = $validated['name'];
        $newUser->email = $validated['email'];
        $newUser->mobile = $validated['mobile'];
        $newUser->password = bcrypt($validated['password']); // Encrypt the password
        $newUser->access_panel = $validated['access_panel'];
        $newUser->save();

        // Assign the Spatie role to the user, ensuring seller can only assign roles from their own team
        $rolesToAssign = $validated['roles'];
        if ($this->getPanel() == 'seller') {
            if (!$seller) {
                return ApiResponseType::sendJsonResponse(false, 'labels.seller_not_found', [], 404);
            }
            $validNames = Role::query()
                ->whereIn('name', $rolesToAssign)
                ->where('guard_name', 'seller')
                ->where('team_id', $seller->id)
                ->pluck('name')
                ->toArray();
            if (count($validNames) !== count($rolesToAssign)) {
                return ApiResponseType::sendJsonResponse(false, 'labels.role_not_found', [], 422);
            }
            $rolesToAssign = $validNames;
        }
        $newUser->assignRole($rolesToAssign);

        // Store the seller_id and created user_id in the pivot table
        if (isset($validated['seller_id'])) {
            SellerUser::create([
                'user_id' => $newUser->id,
                'seller_id' => $validated['seller_id'],
            ]);
        }

        // Update usage after successful creation (seller panel, multivendor)
        if ($this->getPanel() == 'seller' && isset($validated['seller_id'])) {
            $this->recordUsageIfMultivendor($validated['seller_id'], SubscriptionPlanKeyEnum::SYSTEM_USER_LIMIT());
        }

        return ApiResponseType::sendJsonResponse(true, 'labels.user_created', ['user' => $newUser], 201);
    }

    /**
     * Display the specified user.
     */
    public function show($id): JsonResponse
    {
        $user = User::with(['roles'])->find($id);
        if (!$user) {
            return ApiResponseType::sendJsonResponse(false, 'labels.user_not_found', [], 404);
        }
        try {
            $this->authorize('view', $user);
        } catch (AuthorizationException $e) {
            return ApiResponseType::sendJsonResponse(false, 'labels.permission_denied', [], 403);
        }
        return ApiResponseType::sendJsonResponse(true, 'labels.user_retrieved', ['user' => $user], 200);
    }


    /**
     * Update the specified user in storage.
     */
    public function update(UpdateSystemUserRequest $request, $id): JsonResponse
    {
        $user = User::find($id);
        if (!$user) {
            return ApiResponseType::sendJsonResponse(false, 'labels.user_not_found', [], 404);
        }

        try {
            $this->authorize('update', $user);
        } catch (AuthorizationException $e) {
            return ApiResponseType::sendJsonResponse(false, 'labels.permission_denied', [], 403);
        }

        $validated = $request->validated();
        // Update user attributes
        $user->name = $validated['name'] ?? $user->name;
        $user->mobile = $validated['mobile'] ?? $user->mobile;
        if (isset($validated['password'])) {
            $user->password = bcrypt($validated['password']); // Encrypt password if provided
        }
        $user->save();

        $authUser = auth()->user();
        $seller = $authUser->seller();

        // Update Spatie role
        if (isset($validated['roles'])) {
            $rolesToAssign = $validated['roles'];
            if ($this->getPanel() == 'seller') {
                if (!$seller) {
                    return ApiResponseType::sendJsonResponse(false, 'labels.seller_not_found', [], 404);
                }
                $validNames = Role::query()
                    ->whereIn('name', $rolesToAssign)
                    ->where('guard_name', 'seller')
                    ->where('team_id', $seller->id)
                    ->pluck('name')
                    ->toArray();
                if (count($validNames) !== count($rolesToAssign)) {
                    return ApiResponseType::sendJsonResponse(false, 'labels.role_not_found', [], 422);
                }
                $rolesToAssign = $validNames;
            }
            $user->syncRoles($rolesToAssign); // Sync the role
        }
        return ApiResponseType::sendJsonResponse(true, 'labels.user_updated', ['user' => $user], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $user = User::find($id);
        if (!$user) {
            return ApiResponseType::sendJsonResponse(false, 'labels.user_not_found', [], 404);
        }

        try {
            $this->authorize('delete', $user);
        } catch (AuthorizationException $e) {
            return ApiResponseType::sendJsonResponse(false, 'labels.admin_user_cannot_be_deleted', [], 403);
        }

        // Get seller_id before deleting pivot for reducing usage later
        $pivot = SellerUser::where('user_id', $user->id)->first();
        $sellerId = $pivot?->seller_id;
        // Delete associated pivot record
        SellerUser::where('user_id', $user->id)->delete();

        $user->syncRoles([]);
        // Delete the user
        $user->delete();

        // Reduce subscription usage for system_user_limit only when multivendor and seller association exists
        if ($sellerId) {
            $this->reduceUsageIfMultivendor((int)$sellerId, SubscriptionPlanKeyEnum::SYSTEM_USER_LIMIT());
        }
        return ApiResponseType::sendJsonResponse(true, 'labels.user_deleted', []);
    }

    /**
     * Get system users for DataTable.
     */
    public function getSystemUsers(Request $request): JsonResponse
    {
        $draw = $request->get('draw');
        $start = $request->get('start');
        $length = $request->get('length');
        $searchValue = $request->get('search')['value'] ?? '';

        $orderColumnIndex = $request->get('order')[0]['column'] ?? 0;
        $orderDirection = $request->get('order')[0]['dir'] ?? 'asc';

        $columns = ['id', 'name', 'email', 'mobile', 'created_at'];
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';
        if ($this->getPanel() == 'seller') {
            $user = auth()->user();
            $seller = $user->seller();
            if (!$seller) {
                return ApiResponseType::sendJsonResponse(false, 'labels.seller_not_found', [], 404);
            }
            $query = User::query()
                ->join('seller_user', 'users.id', '=', 'seller_user.user_id') // Join pivot table
                ->select('users.*', 'seller_user.seller_id')
                ->where('seller_user.seller_id', '=', $seller->id)
                ->whereDoesntHave('roles', function ($q) {
                    $q->where('name', DefaultSystemRolesEnum::SUPER_ADMIN());
                });
        } else {
            $query = User::query()
                ->where('access_panel', 'admin')
                ->whereDoesntHave('roles', function ($q) {
                    $q->where('name', DefaultSystemRolesEnum::SUPER_ADMIN())->orWhere('name', DefaultSystemRolesEnum::CUSTOMER())->orWhere('name', DefaultSystemRolesEnum::SELLER());
                });
        }


        // Query to fetch only users connected with sellers
        $totalRecords = $query->count();

        // Search filter
        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('name', 'like', "%{$searchValue}%")
                    ->orWhere('email', 'like', "%{$searchValue}%");
            });
        }

        $filteredRecords = $query->count();

        $demo = $this->isDemoModeEnabled();
        $data = $query
            ->orderBy($orderColumn, $orderDirection)
            ->skip($start)
            ->take($length)
            ->get()
            ->map(function ($user) use ($demo) {
                $email = $user->email ?? '';
                $mobile = $user->mobile ?? '';
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $demo ? Str::mask($email, '****', 3, 4) : $email,
                    'mobile' => $demo ? Str::mask($mobile, '****', 3, 4) : $mobile,
                    'role' => view('partials.roles', ['roles' => $user->getRoleNames()])->render(),
                    'created_at' => $user->created_at->format('Y-m-d'),
                    'action' => view('partials.actions', [
                        'modelName' => 'system-user',
                        'id' => $user->id,
                        'title' => $user->name,
                        'mode' => 'model_view',
                        'editPermission' => $this->editPermission,
                        'deletePermission' => $this->deletePermission
                    ])->render(),
                ];
            })
            ->toArray();

        return response()->json([
            'draw' => intval($draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }
}
