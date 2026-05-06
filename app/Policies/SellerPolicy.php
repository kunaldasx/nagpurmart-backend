<?php

namespace App\Policies;

use App\Enums\AdminPermissionEnum;
use App\Models\Seller;
use App\Models\Setting;
use App\Models\User;
use App\Traits\ChecksPermissions;
use Illuminate\Auth\Access\Response;

class SellerPolicy
{
    use ChecksPermissions;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return !($this->hasPermission(AdminPermissionEnum::SELLER_VIEW()) === false);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Seller $seller): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if (Setting::isSystemVendorTypeSingle()) {
            return false;
        }

        return !($this->hasPermission(AdminPermissionEnum::SELLER_CREATE()) === false);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Seller $seller): bool
    {
        return !($this->hasPermission(AdminPermissionEnum::SELLER_EDIT()) === false);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Seller $seller): bool
    {
        if (Setting::isSystemVendorTypeSingle()) {
            $adminSeller = Setting::adminSeller();
            if ($adminSeller?->id == $seller->id) {
                return false;
            }
        }
        return !($this->hasPermission(AdminPermissionEnum::SELLER_DELETE()) === false);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Seller $seller): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Seller $seller): bool
    {
        return false;
    }
}
