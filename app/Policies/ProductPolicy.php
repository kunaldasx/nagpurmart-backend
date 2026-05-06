<?php

namespace App\Policies;

use App\Enums\AdminPermissionEnum;
use App\Enums\DefaultSystemRolesEnum;
use App\Enums\SellerPermissionEnum;
use App\Models\Product;
use App\Models\User;
use App\Traits\ChecksPermissions;
use Illuminate\Auth\Access\Response;

class ProductPolicy
{
    use ChecksPermissions;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        try {
            if ($user->hasRole(DefaultSystemRolesEnum::SELLER())) {
                return true;
            }
            // Only sellers can view products
            if ($user->seller() == null) {
                $this->hasPermission(AdminPermissionEnum::PRODUCT_VIEW());
            }

            return $this->hasPermission(SellerPermissionEnum::PRODUCT_VIEW());
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Product $product): bool
    {
        try {
            // Only the seller who owns the product can update it
            if ($user->seller() == null) {
                return $this->hasPermission(AdminPermissionEnum::PRODUCT_VIEW());
            }

            // Check if the user is the owner
            if ($user->seller()->id == $product->seller_id) {
                // Check role or permission
                if (
                    $user->hasRole(DefaultSystemRolesEnum::SELLER()) ||
                    $this->hasPermission(SellerPermissionEnum::PRODUCT_VIEW())
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
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        try {
            // Only sellers with a valid seller record can create product FAQs
            if ($user->seller() == null) {
                return false;
            }

            // Must have a seller role or explicit permission
            if (
                $user->hasRole(DefaultSystemRolesEnum::SELLER()) ||
                $this->hasPermission(SellerPermissionEnum::PRODUCT_CREATE())
            ) {
                return true;
            }

            return false;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Product $product): bool
    {
        try {
            // Only the seller who owns the product can update it
            if ($user->seller() == null) {
                return false;
            }

            // Check if the user is the owner
            if ($user->seller()->id == $product->seller_id) {
                // Check role or permission
                if (
                    $user->hasRole(DefaultSystemRolesEnum::SELLER()) ||
                    $this->hasPermission(SellerPermissionEnum::PRODUCT_EDIT())
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
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Product $product): bool
    {
        try {
            // Only the seller who owns the product can update it
            if ($user->seller() == null) {
                return false;
            }

            // Check if the user is the owner
            if ($user->seller()->id == $product->seller_id) {
                // Check role or permission
                if (
                    $user->hasRole(DefaultSystemRolesEnum::SELLER()) ||
                    $this->hasPermission(SellerPermissionEnum::PRODUCT_DELETE())
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
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Product $product): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Product $product): bool
    {
        return false;
    }

    public function verifyProduct(User $user): bool
    {
        try {
            return $this->hasPermission(AdminPermissionEnum::PRODUCT_STATUS_UPDATE());
        } catch (\Exception) {
            return false;
        }
    }
}
