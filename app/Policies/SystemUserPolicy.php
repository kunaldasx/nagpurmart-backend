<?php

namespace App\Policies;

use App\Enums\AdminPermissionEnum;
use App\Enums\DefaultSystemRolesEnum;
use App\Enums\SellerPermissionEnum;
use App\Models\User;
use App\Traits\ChecksPermissions;
use App\Traits\PanelAware;

class SystemUserPolicy
{
    use ChecksPermissions, PanelAware;

    /**
     * Determine whether the user can create system users.
     */
    public function create(User $user): bool
    {
        if (
            $user->hasRole(DefaultSystemRolesEnum::SELLER()) ||
            $this->hasPermission(SellerPermissionEnum::SYSTEM_USER_CREATE())
        ) {
            return true;
        }
        return $this->hasPermission(AdminPermissionEnum::SYSTEM_USER_CREATE());
    }

    /**
     * Determine whether the user can update the system user.
     */
    public function update(User $user, User $model): bool
    {
        try {
            if ($this->getPanel() == 'seller') {
                // Only the seller who owns the product can update it
                if ($user->seller() === null) {
                    return false;
                }
                // Check if the user is the owner
                if ($user->seller()->id === $model->seller()->id) {
                    // Check role or permission
                    if (
                        $user->hasRole(DefaultSystemRolesEnum::SELLER()) ||
                        $this->hasPermission(SellerPermissionEnum::SYSTEM_USER_EDIT())
                    ) {
                        return true;
                    }
                }
            }
            return $this->hasPermission(AdminPermissionEnum::SYSTEM_USER_EDIT());

        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Determine whether the user can delete the system user.
     */
    public function delete(User $user, User $model): bool
    {
        try {
            if ($this->getPanel() == 'seller') {
                // Only the seller who owns the product can update it
                if ($user->seller() === null) {
                    return false;
                }
                // Check if the user is the owner
                if ($user->seller()->id === $model->seller()->id) {
                    // Check role or permission
                    if (
                        $user->hasRole(DefaultSystemRolesEnum::SELLER()) ||
                        $this->hasPermission(SellerPermissionEnum::SYSTEM_USER_DELETE())
                    ) {
                        return true;
                    }
                }
            }
            return $this->hasPermission(AdminPermissionEnum::SYSTEM_USER_DELETE());

        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Determine whether the user can view the system user.
     */
    public function view(User $user, User $model): bool
    {
        try {
            if ($this->getPanel() == 'seller') {
                // Only the seller who owns the product can update it
                if ($user->seller() === null) {
                    return false;
                }
                // Check if the user is the owner
                if ($user->seller()->id === $model->seller()->id) {
                    // Check role or permission
                    if (
                        $user->hasRole(DefaultSystemRolesEnum::SELLER()) ||
                        $this->hasPermission(SellerPermissionEnum::SYSTEM_USER_VIEW())
                    ) {
                        return true;
                    }
                }
            }
            return $this->hasPermission(AdminPermissionEnum::SYSTEM_USER_VIEW());

        } catch (\Exception) {
            return false;
        }
    }

    public function viewAny(User $user): bool
    {
        if ($this->getPanel() == 'seller') {
            // Only the seller who owns the product can update it
            if ($user->seller() === null) {
                return false;
            }
            // Check role or permission
            if (
                $user->hasRole(DefaultSystemRolesEnum::SELLER()) ||
                $this->hasPermission(SellerPermissionEnum::SYSTEM_USER_VIEW())
            ) {
                return true;
            }
        }
        return $this->hasPermission(AdminPermissionEnum::SYSTEM_USER_VIEW());
    }
}
