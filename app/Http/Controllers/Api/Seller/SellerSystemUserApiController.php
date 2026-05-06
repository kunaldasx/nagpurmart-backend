<?php

namespace App\Http\Controllers\Api\Seller;

use App\Enums\GuardNameEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\SystemUser\StoreSystemUserRequest;
use App\Http\Requests\SystemUser\UpdateSystemUserRequest;
use App\Models\Seller;
use App\Models\SellerUser;
use App\Models\User;
use App\Types\Api\ApiResponseType;
use App\Models\Setting;
use App\Services\SubscriptionUsageService;
use App\Traits\SubscriptionLimitGuard;
use App\Enums\Subscription\SubscriptionPlanKeyEnum;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

#[Group('Seller System Users')]
class SellerSystemUserApiController extends Controller
{
    use AuthorizesRequests, SubscriptionLimitGuard;

    /**
     * List of all the system users
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $user = auth()->user();
        $seller = $user?->seller();
        if (!$seller) {
            return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found'), null, 404);
        }

        $perPage = (int) $request->input('per_page', 15);
        $q = $request->input('search');

        $query = User::query()
            ->join('seller_user', 'users.id', '=', 'seller_user.user_id')
            ->select('users.*')
            ->where('seller_user.seller_id', $seller->id)
            ->orderByDesc('users.id');
        if (!empty($q)) {
            $query->where(function ($sub) use ($q) {
                $sub->where('users.name', 'like', "%$q%")
                    ->orWhere('users.email', 'like', "%$q%")
                    ->orWhere('users.mobile', 'like', "%$q%");
            });
        }

        $paginator = $query->paginate($perPage);
        return ApiResponseType::sendJsonResponse(true, __('labels.users_fetched_successfully'), [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'data' => $paginator->items(),
        ]);
    }

    /**
     * Retrieve a specific system user by ID
     *
     * @param int $id
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function show(int $id): JsonResponse
    {
        $user = User::with('roles')->find($id);
        if (!$user) {
            return ApiResponseType::sendJsonResponse(false, __('labels.user_not_found'), null, 404);
        }
        try {
            $this->authorize('view', $user);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.permission_denied'), null, 403);
        }
        return ApiResponseType::sendJsonResponse(true, __('labels.user_retrieved'), ['user' => $user]);
    }

    /**
     * Create a new system user under the authenticated seller
     *
     * @param StoreSystemUserRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function store(StoreSystemUserRequest $request): JsonResponse
    {
        try {
            $this->authorize('create', User::class);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.permission_denied'), null, 403);
        }

        $validated = $request->validated();

        $authUser = auth()->user();
        $seller = Seller::where('user_id', $authUser->id)->first();
        if (!$seller) {
            return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found'), null, 404);
        }

        // Pre-check subscription usage for system_user_limit (multivendor only)
        if ($error = $this->ensureCanUseOrError($seller->id, SubscriptionPlanKeyEnum::SYSTEM_USER_LIMIT())) {
            return $error;
        }

        // create user
        $newUser = new User();
        $newUser->name = $validated['name'];
        $newUser->email = $validated['email'];
        $newUser->mobile = $validated['mobile'] ?? null;
        $newUser->password = bcrypt($validated['password']);
        $newUser->access_panel = GuardNameEnum::SELLER();
        $newUser->save();

        // Restrict assignable roles to this seller's team and seller guard
        $rolesToAssign = $validated['roles'] ?? [];
        if (!empty($rolesToAssign)) {
            $validNames = Role::query()
                ->whereIn('name', $rolesToAssign)
                ->where('guard_name', GuardNameEnum::SELLER())
                ->where('team_id', $seller->id)
                ->pluck('name')
                ->toArray();
            if (count($validNames) !== count($rolesToAssign)) {
                return ApiResponseType::sendJsonResponse(false, __('labels.role_not_found'), null, 422);
            }
            $newUser->assignRole($validNames);
        }

        // create pivot
        SellerUser::create([
            'user_id' => $newUser->id,
            'seller_id' => $seller->id,
        ]);

        // Update usage after create (multivendor only)
        $this->recordUsageIfMultivendor($seller->id, SubscriptionPlanKeyEnum::SYSTEM_USER_LIMIT());

        return ApiResponseType::sendJsonResponse(true, __('labels.user_created'), ['user' => $newUser], 201);
    }

    /**
     * Update an existing system user
     *
     * @param UpdateSystemUserRequest $request
     * @param int $id
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function update(UpdateSystemUserRequest $request, int $id): JsonResponse
    {
        $user = User::find($id);
        if (!$user) {
            return ApiResponseType::sendJsonResponse(false, __('labels.user_not_found'), null, 404);
        }

        try {
            $this->authorize('update', $user);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.permission_denied'), null, 403);
        }

        $validated = $request->validated();
        $user->name = $validated['name'] ?? $user->name;
        $user->mobile = $validated['mobile'] ?? $user->mobile;
        if (!empty($validated['password'])) {
            $user->password = bcrypt($validated['password']);
        }
        $user->save();

        // Sync roles if provided, ensuring seller scoping
        if (isset($validated['roles'])) {
            $authUser = auth()->user();
            $seller = $authUser?->seller();
            if (!$seller) {
                return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found'), null, 404);
            }
            $validNames = Role::query()
                ->whereIn('name', $validated['roles'])
                ->where('guard_name', GuardNameEnum::SELLER())
                ->where('team_id', $seller->id)
                ->pluck('name')
                ->toArray();
            if (count($validNames) !== count($validated['roles'])) {
                return ApiResponseType::sendJsonResponse(false, __('labels.role_not_found'), null, 422);
            }
            $user->syncRoles($validNames);
        }

        return ApiResponseType::sendJsonResponse(true, __('labels.user_updated'), ['user' => $user]);
    }

    /**
     * Delete a system user and detach relations
     *
     * @param int $id
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function destroy(int $id): JsonResponse
    {
        $user = User::find($id);
        if (!$user) {
            return ApiResponseType::sendJsonResponse(false, __('labels.user_not_found'), null, 404);
        }

        try {
            $this->authorize('delete', $user);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.admin_user_cannot_be_deleted'), null, 403);
        }

        // Capture seller id from pivot before delete for usage reduction
        $pivot = SellerUser::where('user_id', $user->id)->first();
        $sellerId = $pivot?->seller_id;
        SellerUser::where('user_id', $user->id)->delete();
        $user->syncRoles([]);
        $user->delete();

        // Reduce usage after delete (multivendor only if seller association exists)
        if ($sellerId) {
            $this->reduceUsageIfMultivendor((int)$sellerId, SubscriptionPlanKeyEnum::SYSTEM_USER_LIMIT());
        }

        return ApiResponseType::sendJsonResponse(true, __('labels.user_deleted'));
    }
}
