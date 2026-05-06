<?php

namespace App\Policies;

use App\Enums\AdminPermissionEnum;
use App\Models\DeliveryBoyAssignment;
use App\Models\User;
use App\Traits\ChecksPermissions;

class DeliveryBoyAssignmentPolicy
{
    use ChecksPermissions;

    /**
     * Determine whether the user can view any delivery boy assignments.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasPermission(AdminPermissionEnum::DELIVERY_BOY_CASH_COLLECTION_VIEW());
    }

    /**
     * Determine whether the user can view the delivery boy assignment.
     */
    public function view(User $user, DeliveryBoyAssignment $deliveryBoyAssignment): bool
    {
        return $this->hasPermission(AdminPermissionEnum::DELIVERY_BOY_CASH_COLLECTION_VIEW());
    }

    /**
     * Determine whether the user can process payment for the delivery boy assignment.
     */
    public function processPayment(User $user, DeliveryBoyAssignment $deliveryBoyAssignment): bool
    {
        return $this->hasPermission(AdminPermissionEnum::DELIVERY_BOY_CASH_COLLECTION_PROCESS());
    }
}
