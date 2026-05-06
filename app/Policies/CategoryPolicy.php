<?php

namespace App\Policies;

use App\Enums\AdminPermissionEnum;
use App\Enums\DefaultSystemRolesEnum;
use App\Enums\SellerPermissionEnum;
use App\Models\Category;
use App\Models\User;
use App\Traits\ChecksPermissions;

class CategoryPolicy
{
    use ChecksPermissions;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Only the seller who owns the order can view it
        if ($user->seller() === null) {
            return $this->hasPermission(AdminPermissionEnum::CATEGORY_VIEW());
        }

        // Check role or permission
        if (
            $user->hasRole(DefaultSystemRolesEnum::SELLER()) ||
            $this->hasPermission(SellerPermissionEnum::CATEGORY_VIEW())
        ) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Category $category): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        try {
            return $this->hasPermission(AdminPermissionEnum::CATEGORY_CREATE());
        } catch
        (\Exception) {
            return false;
        }
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Category $category): bool
    {
        try {
            return $this->hasPermission(AdminPermissionEnum::CATEGORY_EDIT());
        } catch
        (\Exception) {
            return false;
        }
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Category $category): bool
    {
        try {
            return $this->hasPermission(AdminPermissionEnum::CATEGORY_DELETE());
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Category $category): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Category $category): bool
    {
        return false;
    }
}
