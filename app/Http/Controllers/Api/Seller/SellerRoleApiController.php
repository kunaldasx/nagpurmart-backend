<?php

namespace App\Http\Controllers\Api\Seller;

use App\Enums\DefaultSystemRolesEnum;
use App\Enums\GuardNameEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Roles\StoreRoleRequest;
use App\Http\Requests\Roles\UpdateRoleRequest;
use App\Types\Api\ApiResponseType;
use App\Models\Setting;
use App\Services\SubscriptionUsageService;
use App\Traits\SubscriptionLimitGuard;
use App\Enums\Subscription\SubscriptionPlanKeyEnum;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

#[Group('Seller Roles')]
class SellerRoleApiController extends Controller
{
    use AuthorizesRequests, SubscriptionLimitGuard;

    /**
     * List seller-scoped roles with pagination
     *
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    #[QueryParameter('page', description: 'Page number for pagination.', type: 'int', default: 1, example: 1)]
    #[QueryParameter('per_page', description: 'Number of stores per page', type: 'int', default: 15, example: 15)]
    #[QueryParameter('search', description: 'Search the role', type: 'string', example: 'Downtown')]
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Role::class);

        $perPage = (int)$request->input('per_page', 15);
        $q = $request->input('search', '');

        $user = auth()->user();
        $seller = $user?->seller();
        if (!$seller) {
            return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found'), null, 404);
        }

        $roles = Role::query()
            ->where('guard_name', GuardNameEnum::SELLER())
            ->where('team_id', $seller->id)
            ->when($q, function ($query) use ($q) {
                $query->where('name', 'like', "%$q%");
            })
            ->whereNotIn('name', [
                DefaultSystemRolesEnum::SUPER_ADMIN(),
                DefaultSystemRolesEnum::CUSTOMER(),
                DefaultSystemRolesEnum::SELLER(),
            ])
            ->orderByDesc('id')
            ->paginate((int)$request->input('per_page', $perPage));

        return ApiResponseType::sendJsonResponse(success: true,
            message: __('labels.roles_fetched_successfully'),
            data: ['current_page' => $roles->currentPage(),
                'last_page' => $roles->lastPage(),
                'per_page' => $roles->perPage(),
                'total' => $roles->total(),
                'data' => $roles->items(),]
        );
    }

    /**
     * Retrieve a seller role by its ID
     *
     * @param int $id
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function show(int $id): JsonResponse
    {
        try {
            $role = Role::findOrFail($id);
            $this->authorize('view', $role);
            return ApiResponseType::sendJsonResponse(true, __('labels.role_retrieved_successfully'), $role);
        } catch (ModelNotFoundException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.role_not_found'), null, 404);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.permission_denied'), null, 403);
        }
    }

    /**
     * Create a new seller-scoped role
     *
     * @param StoreRoleRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        try {
            $this->authorize('create', Role::class);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.permission_denied'), null, 403);
        }

        $validated = $request->validated();
        if (in_array($validated['name'], ['Super Admin', 'customer', 'seller'])) {
            return ApiResponseType::sendJsonResponse(false, __('labels.cannot_create_role_with_this_name'), null, 422);
        }

        $user = auth()->user();
        $seller = $user?->seller();
        if (!$seller) {
            return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found'), null, 404);
        }

        $validated['guard_name'] = GuardNameEnum::SELLER();
        $validated['team_id'] = $seller->id;

        // Pre-check subscription usage for role_limit (multivendor only)
        if ($error = $this->ensureCanUseOrError($validated['team_id'], SubscriptionPlanKeyEnum::ROLE_LIMIT())) {
            return $error;
        }

        $role = Role::create($validated);
        // Update usage after create (multivendor only)
        $this->recordUsageIfMultivendor($validated['team_id'], SubscriptionPlanKeyEnum::ROLE_LIMIT());
        return ApiResponseType::sendJsonResponse(true, __('labels.role_created_successfully'), $role, 201);
    }

    /**
     * Update an existing seller-scoped role
     *
     * @param UpdateRoleRequest $request
     * @param int $id
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function update(UpdateRoleRequest $request, int $id): JsonResponse
    {
        try {
            $role = Role::findOrFail($id);
            $this->authorize('update', $role);
        } catch (ModelNotFoundException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.role_not_found'), null, 404);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.permission_denied'), null, 403);
        }

        if (in_array($role->name, ['Super Admin', 'customer', 'seller'])) {
            return ApiResponseType::sendJsonResponse(false, __('labels.cannot_modify_role'), null, 422);
        }

        $validated = $request->validated();
        $role->update($validated);
        return ApiResponseType::sendJsonResponse(true, __('labels.role_updated_successfully'), $role);
    }

    /**
     * Delete a seller-scoped role
     *
     * @param int $id
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $role = Role::findOrFail($id);
            $this->authorize('delete', $role);
        } catch (ModelNotFoundException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.role_not_found'), null, 404);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.permission_denied'), null, 403);
        }

        if (in_array($role->name, ['Super Admin', 'customer', 'seller'])) {
            return ApiResponseType::sendJsonResponse(false, __('labels.cannot_delete_role'), null, 422);
        }

        $teamId = $role->guard_name === GuardNameEnum::SELLER() ? $role->team_id : null;
        $role->permissions()->detach();
        $role->users()->detach();
        $role->delete();
        // Reduce usage after delete (multivendor only for seller role)
        if ($teamId) {
            $this->reduceUsageIfMultivendor((int)$teamId, SubscriptionPlanKeyEnum::ROLE_LIMIT());
        }
        return ApiResponseType::sendJsonResponse(true, __('labels.role_deleted_successfully'), null);
    }

    /**
     * Get list of seller roles (minimal) for dropdowns/search
     *
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function getRoles(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Role::class);

        $user = auth()->user();
        $seller = $user?->seller();
        if (!$seller) {
            return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found'), null, 404);
        }

        $q = $request->input('search');
        $query = Role::query()
            ->where('guard_name', GuardNameEnum::SELLER())
            ->where('team_id', $seller->id)
            ->whereNotIn('name', [
                DefaultSystemRolesEnum::SUPER_ADMIN(),
                DefaultSystemRolesEnum::CUSTOMER(),
                DefaultSystemRolesEnum::SELLER(),
            ])
            ->orderBy('name');
        if (!empty($q)) {
            $query->where('name', 'like', "%$q%");
        }
        $roles = $query->get(['id', 'name']);
        return ApiResponseType::sendJsonResponse(true, __('labels.roles_fetched_successfully'), $roles);
    }
}
