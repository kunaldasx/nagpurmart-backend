<?php

namespace App\Policies;

use App\Enums\AdminPermissionEnum;
use App\Models\SellerWithdrawalRequest;
use App\Models\User;
use App\Traits\ChecksPermissions;
use Illuminate\Auth\Access\HandlesAuthorization;

class SellerWithdrawalRequestPolicy
{
    use HandlesAuthorization, ChecksPermissions;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasPermission(AdminPermissionEnum::SELLER_WITHDRAWAL_VIEW());
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user): bool
    {
        return $this->hasPermission(AdminPermissionEnum::SELLER_WITHDRAWAL_VIEW());
    }

    /**
     * Determine whether the user can process withdrawal requests.
     */
    public function processRequest(User $user): bool
    {
        return $this->hasPermission(AdminPermissionEnum::SELLER_WITHDRAWAL_PROCESS());
    }
}
