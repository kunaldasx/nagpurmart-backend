<?php

namespace App\Traits;

use App\Enums\DefaultSystemRolesEnum;
use App\Types\Api\ApiResponseType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

trait ChecksPermissions
{
    use PanelAware;
    /**
     * Check if the authenticated user has the given permission.
     *
     * @param string $permission
     * @return bool
     */
    protected function hasPermission(string $permission): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false; // No user is authenticated
        }
        if ($this->getPanel('admin') && $user->hasRole(DefaultSystemRolesEnum::SUPER_ADMIN())) {
            return true; // Super Admin has all permissions
        }
        try {
            return $user && $user->hasPermissionTo($permission);
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Check if the authenticated user has any of the given permissions.
     *
     * @param array $permissions
     * @return bool
     */
    protected function hasAnyPermission(array $permissions): bool
    {
        $user = Auth::user();
        if ($user->hasRole(DefaultSystemRolesEnum::SUPER_ADMIN())) {
            return true; // Super Admin has all permissions
        }
        return $user && $user->hasAnyPermission($permissions);
    }

    /**
     * Check if the authenticated user has all of the given permissions.
     *
     * @param array $permissions
     * @return bool
     */
    protected function hasAllPermissions(array $permissions): bool
    {
        $user = Auth::user();
        if ($user->hasRole(DefaultSystemRolesEnum::SUPER_ADMIN())) {
            return true; // Super Admin has all permissions
        }
        return $user && $user->hasAllPermissions($permissions);
    }

    /**
     * Return a JSON response for unauthorized access.
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function unauthorizedResponse(string $message = 'You do not have permission to perform this action.'): JsonResponse
    {
        return ApiResponseType::sendJsonResponse(success: false, message: $message, data: [], status: 403);
    }
}
