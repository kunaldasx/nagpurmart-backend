<?php

namespace App\Policies;

use App\Enums\AdminPermissionEnum;
use App\Models\Promo;
use App\Models\User;
use App\Traits\ChecksPermissions;

class PromoPolicy
{
    use ChecksPermissions;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        try {
            return $this->hasPermission(AdminPermissionEnum::PROMO_VIEW());
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Promo $promo): bool
    {
        try {
            return $this->hasPermission(AdminPermissionEnum::PROMO_VIEW());
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
            return $this->hasPermission(AdminPermissionEnum::PROMO_CREATE());
        } catch
        (\Exception) {
            return false;
        }
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Promo $promo): bool
    {
        try {
            return $this->hasPermission(AdminPermissionEnum::PROMO_EDIT());
        } catch
        (\Exception) {
            return false;
        }
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Promo $promo): bool
    {
        try {
            return $this->hasPermission(AdminPermissionEnum::PROMO_DELETE());
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Promo $promo): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Promo $promo): bool
    {
        return false;
    }
}
