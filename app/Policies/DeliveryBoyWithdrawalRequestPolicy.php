<?php

namespace App\Policies;

use App\Enums\AdminPermissionEnum;
use App\Models\User;
use App\Traits\ChecksPermissions;

class DeliveryBoyWithdrawalRequestPolicy
{
    use ChecksPermissions;

    /**
     * Determine whether the user can view any withdrawal requests.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $this->hasPermission(AdminPermissionEnum::DELIVERY_BOY_WITHDRAWAL_VIEW());
    }

    /**
     * Determine whether the user can view the withdrawal request.
     *
     * @param User $user
     * @return bool
     */
    public function view(User $user): bool
    {
        return $this->hasPermission(AdminPermissionEnum::DELIVERY_BOY_WITHDRAWAL_VIEW());
    }

    /**
     * Determine whether the user can process withdrawal requests.
     *
     * @param User $user
     * @return bool
     */
    public function processRequest(User $user): bool
    {
        return $this->hasPermission(AdminPermissionEnum::DELIVERY_BOY_WITHDRAWAL_PROCESS());
    }
}
