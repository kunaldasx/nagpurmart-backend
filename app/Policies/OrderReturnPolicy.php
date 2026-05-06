<?php

namespace App\Policies;

use App\Enums\AdminPermissionEnum;
use App\Enums\DefaultSystemRolesEnum;
use App\Enums\SellerPermissionEnum;
use App\Models\User;
use App\Traits\ChecksPermissions;

class OrderReturnPolicy
{
    use ChecksPermissions;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Only the seller who owns the order can view it
        if ($user->seller() === null) {
            return $this->hasPermission(AdminPermissionEnum::RETURN_VIEW());
        }

        // Check role or permission
        if (
            $user->hasRole(DefaultSystemRolesEnum::SELLER()) ||
            $this->hasPermission(SellerPermissionEnum::RETURN_VIEW())
        ) {
            return true;
        }

        return false;
    }

    public function decide(User $user, $order): bool
    {
        try {
            // Only the seller who owns the order can update it
            if ($user->seller() === null) {
                return false;
            }

            // Check if the user is the owner
            if ($user->seller()->id === $order->seller_id) {
                // Check role or permission
                if (
                    $user->hasRole(DefaultSystemRolesEnum::SELLER()) ||
                    $this->hasPermission(SellerPermissionEnum::RETURN_DECIDE())
                ) {
                    return true;
                }
            }

            return false;
        } catch (\Exception) {
            return false;
        }
    }
}
