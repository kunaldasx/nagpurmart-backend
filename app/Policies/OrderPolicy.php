<?php

namespace App\Policies;

use App\Enums\AdminPermissionEnum;
use App\Enums\DefaultSystemRolesEnum;
use App\Enums\GuardNameEnum;
use App\Enums\SellerPermissionEnum;
use App\Models\SellerOrder;
use App\Models\SellerOrderItem;
use App\Models\User;
use App\Traits\ChecksPermissions;

class OrderPolicy
{
    use ChecksPermissions;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Only the seller who owns the order can view it
        if ($user->seller() == null) {
            return $this->hasPermission(AdminPermissionEnum::ORDER_VIEW());
        }

        // Check role or permission
        if (
            $user->hasRole(DefaultSystemRolesEnum::SELLER()) ||
            $this->hasPermission(SellerPermissionEnum::ORDER_VIEW())
        ) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, $order): bool
    {
        try {
            // Only the seller who owns the order can view it
            if ($user->seller() == null) {
                return $this->hasPermission(AdminPermissionEnum::ORDER_VIEW());
            }

            // Check if the user is the owner
            if ($user->seller()->id == $order->seller_id) {
                // Check role or permission
                if (
                    $user->hasRole(DefaultSystemRolesEnum::SELLER()) ||
                    $this->hasPermission(SellerPermissionEnum::ORDER_VIEW())
                ) {
                    return true;
                }
            }

            return false;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, SellerOrder $order): bool
    {
        try {
            // Only the seller who owns the order can update it
            if ($user->seller() == null) {
                return false;
            }

            // Check if the user is the owner
            if ($user->seller()->id == $order->seller_id) {
                // Check role or permission
                if (
                    $user->hasRole(DefaultSystemRolesEnum::SELLER()) ||
                    $this->hasPermission(SellerPermissionEnum::ORDER_EDIT())
                ) {
                    return true;
                }
            }

            return false;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Determine whether the user can update the status of an order item.
     */
    public function updateStatus(User $user, SellerOrderItem $orderItem): bool
    {
        try {
            // Only the seller who owns the order can update its status
            if ($user->seller() == null) {
                return false;
            }

            // Check if the user is the owner
            if ($user->seller()->id == $orderItem->sellerOrder->seller_id) {
                // Check role or permission
                if (
                    $user->hasRole(DefaultSystemRolesEnum::SELLER()) ||
                    $this->hasPermission(SellerPermissionEnum::ORDER_UPDATE_STATUS())
                ) {
                    return true;
                }
            }

            return false;
        } catch (\Exception) {
            return false;
        }
    }

    public function viewInvoice(User $user, $orderData): bool
    {
        try {
            if ($this->hasPermission(AdminPermissionEnum::ORDER_VIEW())) {
                return true;
            }
            if ($orderData['user_id'] == $user->id) {
                return true;
            }
            return false;
        } catch (\Exception) {
            return false;
        }
    }
}
