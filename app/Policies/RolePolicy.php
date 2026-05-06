<?php

namespace App\Policies;

use App\Enums\AdminPermissionEnum;
use App\Enums\DefaultSystemRolesEnum;
use App\Enums\SellerPermissionEnum;
use App\Models\User;
use App\Traits\ChecksPermissions;
use App\Traits\PanelAware;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    use PanelAware, ChecksPermissions;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        try {
            if ($this->getPanel() === 'seller') {
                return $this->hasPermission(SellerPermissionEnum::ROLE_VIEW())
                    || $user->hasRole(DefaultSystemRolesEnum::SELLER());
            }
            return $this->hasPermission(AdminPermissionEnum::ROLE_VIEW());
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Role $role): bool
    {
        try {
            if ($this->getPanel() == 'seller') {
                if ($user->seller() === null || $user->seller()->id !== $role->team_id) {
                    return false;
                }
                return $this->hasPermission(SellerPermissionEnum::ROLE_VIEW())
                    || $user->hasRole(DefaultSystemRolesEnum::SELLER());
            }
            return $this->hasPermission(AdminPermissionEnum::ROLE_VIEW());
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        try {
            $enum = $this->getPanel() === 'seller' ? SellerPermissionEnum::class : AdminPermissionEnum::class;
            return $this->hasPermission($enum::ROLE_CREATE()) || $user->hasRole(DefaultSystemRolesEnum::SELLER());
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Role $role): bool
    {
        try {
            if ($this->getPanel() == 'seller') {
                // Only the seller who owns the product can update it
                if ($user->seller() === null) {
                    return false;
                }
                // Check if the user is the owner
                if ($user->seller()->id === $role->team_id) {
                    // Check role or permission
                    if (
                        $user->hasRole(DefaultSystemRolesEnum::SELLER()) ||
                        $this->hasPermission(SellerPermissionEnum::ROLE_EDIT())
                    ) {
                        return true;
                    }
                }
            }
            return $this->hasPermission(AdminPermissionEnum::ROLE_EDIT());

        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Determine whether the user can delete the model.
     */
    public
    function delete(User $user, Role $role): bool
    {
        try {
            if ($this->getPanel() == 'seller') {
                // Only the seller who owns the product can update it
                if ($user->seller() === null) {
                    return false;
                }
                // Check if the user is the owner
                if ($user->seller()->id === $role->team_id) {
                    // Check role or permission
                    if (
                        $user->hasRole(DefaultSystemRolesEnum::SELLER()) ||
                        $this->hasPermission(SellerPermissionEnum::ROLE_DELETE())
                    ) {
                        return true;
                    }
                }
            }
            return $this->hasPermission(AdminPermissionEnum::ROLE_DELETE());

        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Determine whether the user can restore the model.
     */
    public
    function restore(User $user, Role $role): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public
    function forceDelete(User $user, Role $role): bool
    {
        return false;
    }

    public
    function viewPermission(User $user, Role $role): bool
    {
        try {
            if ($this->getPanel() == 'seller') {
                // Only the seller who owns the product can update it
                if ($user->seller() === null) {
                    return false;
                }
                // Check if the user is the owner
                if ($user->seller()->id === $role->team_id) {
                    // Check role or permission
                    if (
                        $user->hasRole(DefaultSystemRolesEnum::SELLER()) ||
                        $this->hasPermission(SellerPermissionEnum::ROLE_PERMISSIONS_VIEW())
                    ) {
                        return true;
                    }
                }
            }
            return $this->hasPermission(AdminPermissionEnum::ROLE_PERMISSIONS_VIEW());

        } catch (\Exception) {
            return false;
        }
    }

    public
    function storePermission(User $user, Role $role): bool
    {
        try {
            if ($user->hasRole(DefaultSystemRolesEnum::SUPER_ADMIN())) {
                return true;
            }

            if ($user->hasRole($role->name, $role->guard_name)) {
                return false;
            }

            if ($this->getPanel() == 'seller') {
                // Only the seller who owns the product can update it
                if ($user->seller() === null) {
                    return false;
                }
                // Check if the user is the owner
                if ($user->seller()->id === $role->team_id) {
                    // Check role or permission
                    if (
                        $user->hasRole(DefaultSystemRolesEnum::SELLER()) ||
                        $this->hasPermission(SellerPermissionEnum::ROLE_PERMISSIONS_EDIT())
                    ) {
                        return true;
                    }
                }
            }
            return $this->hasPermission(AdminPermissionEnum::ROLE_PERMISSIONS_EDIT());

        } catch (\Exception) {
            return false;
        }
    }
}
