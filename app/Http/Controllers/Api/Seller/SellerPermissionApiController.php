<?php

namespace App\Http\Controllers\Api\Seller;

use App\Enums\GuardNameEnum;
use App\Enums\SellerPermissionEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Permission\StorePermissionRequest;
use App\Types\Api\ApiResponseType;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

#[Group('Seller Permissions')]
class SellerPermissionApiController extends Controller
{
    use AuthorizesRequests;

    /**
     * Get permissions for a given role (by name), including grouped permissions for seller module
     *
     * @param Request $request
     * @param string $role Role name
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function index(Request $request, string $role): JsonResponse
    {
        try {
            $user = auth()->user();
            $seller = $user?->seller();
            if (!$seller) {
                return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found'), null, 404);
            }

            // Find seller-scoped role by name
            $roleModel = Role::where('name', $role)
                ->where('guard_name', GuardNameEnum::SELLER())
                ->where('team_id', $seller->id)
                ->firstOrFail();

            $this->authorize('viewPermission', $roleModel);

            $permissionModule = SellerPermissionEnum::groupedPermissions();
            $rolePermissions = $roleModel->permissions->pluck('name')->toArray();

            return ApiResponseType::sendJsonResponse(true, __('labels.permissions_fetched_successfully'), [
                'role' => [
                    'id' => $roleModel->id,
                    'name' => $roleModel->name,
                ],
                'grouped_permissions' => $permissionModule,
                'assigned' => $rolePermissions,
            ]);
        } catch (ModelNotFoundException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.role_not_found'), null, 404);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.permission_denied'), null, 403);
        }
    }

    /**
     * Sync permissions to a seller-scoped role
     *
     * @param StorePermissionRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function store(StorePermissionRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $user = auth()->user();
            $seller = $user?->seller();
            if (!$seller) {
                return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found'), null, 404);
            }

            $roleModel = Role::where('name', $validated['role'])
                ->where('guard_name', GuardNameEnum::SELLER())
                ->where('team_id', $seller->id)
                ->firstOrFail();

            $this->authorize('storePermission', $roleModel);

            $roleModel->syncPermissions($validated['permissions'] ?? []);

            return ApiResponseType::sendJsonResponse(true, __('labels.permissions_updated_successfully'), $roleModel->permissions);
        } catch (ModelNotFoundException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.role_not_found'), null, 404);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.permission_denied'), null, 403);
        }
    }
}
