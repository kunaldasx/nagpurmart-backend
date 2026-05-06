<?php

namespace App\Policies;

use App\Enums\AdminPermissionEnum;
use App\Enums\DefaultSystemRolesEnum;
use App\Enums\SellerPermissionEnum;
use App\Models\User;
use App\Traits\ChecksPermissions;

class SellerStatementPolicy
{
    use ChecksPermissions;

    /**
     * Determine whether the user can view any seller statements/commissions pages.
     */
    public function viewAny(User $user): bool
    {
        // Only the seller who owns the order can view it
        if ($user->seller() === null) {
            return $this->hasPermission(AdminPermissionEnum::COMMISSION_VIEW());
        }

        // Check role or permission
        if (
            $user->hasRole(DefaultSystemRolesEnum::SELLER()) ||
            $this->hasPermission(SellerPermissionEnum::EARNING_VIEW())
        ) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view seller statements/commissions data.
     */
    public function view(User $user): bool
    {
        // Only the seller who owns the order can view it
        if ($user->seller() === null) {
            return $this->hasPermission(AdminPermissionEnum::COMMISSION_VIEW());
        }

        // Check role or permission
        if (
            $user->hasRole(DefaultSystemRolesEnum::SELLER()) ||
            $this->hasPermission(SellerPermissionEnum::EARNING_VIEW())
        ) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can process a settlement action for a single entry.
     */
    public function processSettle(User $user): bool
    {
        return $this->hasPermission(AdminPermissionEnum::COMMISSION_SETTLE());
    }

    /**
     * Determine whether the user can process settlement action for all eligible entries.
     */
    public function processSettleAll(User $user): bool
    {
        return $this->hasPermission(AdminPermissionEnum::COMMISSION_SETTLE());
    }

    /**
     * Determine whether the user can view commission history.
     */
    public function viewHistory(User $user): bool
    {
        // Only the seller who owns the order can view it
        if ($user->seller() === null) {
            return $this->hasPermission(AdminPermissionEnum::COMMISSION_VIEW());
        }

        // Check role or permission
        if (
            $user->hasRole(DefaultSystemRolesEnum::SELLER()) ||
            $this->hasPermission(SellerPermissionEnum::EARNING_VIEW())
        ) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view settled commissions list.
     */
    public function viewSettled(User $user): bool
    {
        // Only the seller who owns the order can view it
        if ($user->seller() === null) {
            return $this->hasPermission(AdminPermissionEnum::COMMISSION_VIEW());
        }

        // Check role or permission
        if (
            $user->hasRole(DefaultSystemRolesEnum::SELLER()) ||
            $this->hasPermission(SellerPermissionEnum::EARNING_VIEW())
        ) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view unsettled debits list.
     */
    public function viewUnsettledDebits(User $user): bool
    {
        // Only the seller who owns the order can view it
        if ($user->seller() === null) {
            return $this->hasPermission(AdminPermissionEnum::COMMISSION_VIEW());
        }

        // Check role or permission
        if (
            $user->hasRole(DefaultSystemRolesEnum::SELLER()) ||
            $this->hasPermission(SellerPermissionEnum::EARNING_VIEW())
        ) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can settle a debit entry.
     */
    public function settleDebit(User $user): bool
    {
        return $this->hasPermission(AdminPermissionEnum::COMMISSION_SETTLE());
    }

    /**
     * Determine whether the user can settle all debit entries.
     */
    public function settleAllDebits(User $user): bool
    {
        return $this->hasPermission(AdminPermissionEnum::COMMISSION_SETTLE());
    }
}
