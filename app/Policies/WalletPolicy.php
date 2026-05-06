<?php

namespace App\Policies;

use App\Enums\AdminPermissionEnum;
use App\Enums\DefaultSystemRolesEnum;
use App\Enums\SellerPermissionEnum;
use App\Models\User;
use App\Models\Wallet;
use App\Traits\ChecksPermissions;
use App\Traits\PanelAware;

class WalletPolicy
{
    use ChecksPermissions, PanelAware;

    /**
     * Determine whether the user can view the wallet pages/listing.
     */
    public function viewAny(User $user): bool
    {
        try {
            // Only sellers with a valid seller record can create product FAQs
            if ($user->seller() === null) {
                return false;
            }

            // Must have a seller role or explicit permission
            if (
                $user->hasRole(DefaultSystemRolesEnum::SELLER()) ||
                $this->hasPermission(SellerPermissionEnum::WALLET_VIEW())
            ) {
                return true;
            }

            return false;
        } catch (\Exception) {
            return false;
        }
    }
    public function viewWithdrawal(User $user): bool
    {
        try {
            // Only sellers with a valid seller record can create product FAQs
            if ($user->seller() === null) {
                return false;
            }

            // Must have a seller role or explicit permission
            if (
                $user->hasRole(DefaultSystemRolesEnum::SELLER()) ||
                $this->hasPermission(SellerPermissionEnum::WITHDRAWAL_VIEW())
            ) {
                return true;
            }

            return false;
        } catch (\Exception) {
            return false;
        }
    }

    public function requestWithdrawal(User $user): bool
    {
        try {
            // Only sellers with a valid seller record can create product FAQs
            if ($user->seller() === null) {
                return false;
            }

            // Must have a seller role or explicit permission
            if (
                $user->hasRole(DefaultSystemRolesEnum::SELLER()) ||
                $this->hasPermission(SellerPermissionEnum::WITHDRAWAL_REQUEST())
            ) {
                return true;
            }

            return false;
        } catch (\Exception) {
            return false;
        }
    }


    /**
     * Determine whether the user can view a specific wallet model.
     */
    public function view(User $user, Wallet $wallet): bool
    {
        try {
            // Only sellers with a valid seller record can create product FAQs
            if ($user->seller() === null) {
                return false;
            }

            // Must have a seller role or explicit permission
            if (
                $user->hasRole(DefaultSystemRolesEnum::SELLER()) ||
                $this->hasPermission(SellerPermissionEnum::WALLET_VIEW())
            ) {
                return true;
            }

            return false;
        } catch (\Exception) {
            return false;
        }
    }
}
