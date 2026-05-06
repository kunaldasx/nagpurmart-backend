<?php

namespace App\Policies;

use App\Enums\AdminPermissionEnum;
use App\Models\DeliveryBoy;
use App\Models\User;
use App\Traits\ChecksPermissions;

class DeliveryBoyPolicy
{
    use ChecksPermissions;

    /**
     * Determine whether the user can view any delivery boys.
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $this->hasPermission(AdminPermissionEnum::DELIVERY_BOY_VIEW());
    }

    /**
     * Determine whether the user can view the delivery boy.
     */
    public function view(User $user, DeliveryBoy $deliveryBoy): bool
    {
        return $this->hasPermission(AdminPermissionEnum::DELIVERY_BOY_VIEW());
    }

    /**
     * Determine whether the user can create delivery boys.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the delivery boy.
     */
    public function update(User $user, DeliveryBoy $deliveryBoy): bool
    {
        return $this->hasPermission(AdminPermissionEnum::DELIVERY_BOY_EDIT());
    }

    /**
     * Determine whether the user can delete the delivery boy.
     */
    public function delete(User $user, DeliveryBoy $deliveryBoy): bool
    {
        return $this->hasPermission(AdminPermissionEnum::DELIVERY_BOY_DELETE());
    }

    /**
     * Determine whether the user can restore the delivery boy.
     */
    public function restore(User $user, DeliveryBoy $deliveryBoy): bool
    {
        return $this->hasPermission(AdminPermissionEnum::DELIVERY_BOY_EDIT());
    }

    /**
     * Determine whether the user can permanently delete the delivery boy.
     */
    public function forceDelete(User $user, DeliveryBoy $deliveryBoy): bool
    {
        return $this->hasPermission(AdminPermissionEnum::DELIVERY_BOY_DELETE());
    }
}
