<?php

namespace App\Http\Controllers;

use App\Enums\AdminPermissionEnum;
use App\Enums\DefaultSystemRolesEnum;
use App\Enums\GuardNameEnum;
use App\Enums\SellerPermissionEnum;
use App\Exceptions\SellerNotFoundException;
use App\Http\Requests\Permission\StorePermissionRequest;
use App\Traits\ChecksPermissions;
use App\Traits\PanelAware;
use App\Types\Api\ApiResponseType;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class PermissionController extends Controller
{
    use ChecksPermissions, PanelAware, AuthorizesRequests;

    protected bool $editPermission = false;

    public function __construct()
    {
        $enum = $this->getPanel() === 'seller' ? SellerPermissionEnum::class : AdminPermissionEnum::class;
        $user = auth()->user();
        $this->editPermission = $this->hasPermission($enum::ROLE_PERMISSIONS_EDIT()) || $user->hasRole(DefaultSystemRolesEnum::SELLER());
    }

    public function index($role): View
    {
        try {
            if ($this->getPanel() === 'seller') {
                $user = auth()->user();
                $seller = $user->seller();
                if (!empty($seller)) {
                    $role = Role::where('name', $role)
                        ->where('guard_name', GuardNameEnum::SELLER())
                        ->where('team_id', $seller->id)
                        ->firstOrFail();
                }
            } else {
                $role = Role::where('name', $role)->where('guard_name', GuardNameEnum::ADMIN())->firstOrFail();
            }
            $this->authorize('viewPermission', $role);
            if ($this->getPanel() === 'seller') {
                $permissionModule = SellerPermissionEnum::groupedPermissions();
            } else {
                $permissionModule = AdminPermissionEnum::groupedPermissions();
            }
            $rolePermissions = $role->permissions->pluck('name')->toArray();

            $editPermission = $this->editPermission;

            // Prevent users from editing permissions of their own assigned role (UI-level)
            $user = auth()->user();
            $isOwnRole = $user?->hasRole($role->name, $role->guard_name) ?? false;
            // Super Admins are allowed to edit even if it's their own role (matches policy)
            $isSuperAdmin = $user?->hasRole(\App\Enums\DefaultSystemRolesEnum::SUPER_ADMIN()) ?? false;
            $canEditThisRole = $editPermission && (!$isOwnRole || $isSuperAdmin);

            return view($this->panelView('permissions.index'), compact('role', 'permissionModule', 'rolePermissions', 'editPermission', 'canEditThisRole'));
        } catch (AuthorizationException) {
            abort(403, "Unauthorized action.");
        } catch (Exception $e) {
            abort(404, "Seller not found.");
        }
    }

    public function store(StorePermissionRequest $request): JsonResponse
    {
        try {
            $request->validated();
            if ($this->getPanel() === 'seller') {
                $user = auth()->user();
                $seller = $user->seller();
                if (!empty($seller)) {
                    $role = Role::where('name', $request->role)
                        ->where('guard_name', GuardNameEnum::SELLER())
                        ->where('team_id', $seller->id)
                        ->firstOrFail();
                }
            } else {
                $role = Role::where('name', $request->role)->where('guard_name', GuardNameEnum::ADMIN())->firstOrFail();
            }
            $this->authorize('storePermission', $role);
            $role->syncPermissions($request->permissions ?? []);

            return ApiResponseType::sendJsonResponse(
                true,
                "Permissions updated successfully.",
                $role->permissions
            );
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(
                false,
                __('labels.unauthorized_access') ?? "Unauthorized.",
                null,
                403
            );
        } catch (Exception $e) {
            return ApiResponseType::sendJsonResponse(
                false,
                "An error occurred while updating permissions: " . $e->getMessage(),
                null,
                500
            );
        }
    }

}
