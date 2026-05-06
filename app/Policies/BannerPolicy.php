<?php

namespace App\Policies;

use App\Enums\AdminPermissionEnum;
use App\Models\Banner;
use App\Models\User;
use App\Traits\ChecksPermissions;

class BannerPolicy
{
    use ChecksPermissions;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        try {
            return $this->hasPermission(AdminPermissionEnum::BANNER_VIEW());
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Banner $banner): bool
    {
        try {
            return $this->hasPermission(AdminPermissionEnum::BANNER_VIEW());
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
            return $this->hasPermission(AdminPermissionEnum::BANNER_CREATE());
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Banner $banner): bool
    {
        try {
            return $this->hasPermission(AdminPermissionEnum::BANNER_EDIT());
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Banner $banner): bool
    {
        try {
            return $this->hasPermission(AdminPermissionEnum::BANNER_DELETE());
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Banner $banner): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Banner $banner): bool
    {
        return false;
    }
}
