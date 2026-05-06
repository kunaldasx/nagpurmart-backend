<?php

namespace App\Policies;

use App\Enums\AdminPermissionEnum;
use App\Models\DeliveryZone;
use App\Models\User;
use App\Traits\ChecksPermissions;
use Illuminate\Auth\Access\Response;

class DeliveryZonePolicy
{
    use ChecksPermissions;
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        try {
            return $this->hasPermission(AdminPermissionEnum::DELIVERY_ZONE_VIEW());
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, DeliveryZone $deliveryZone): bool
    {
        try {
            return $this->hasPermission(AdminPermissionEnum::DELIVERY_ZONE_VIEW());
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
            return $this->hasPermission(AdminPermissionEnum::DELIVERY_ZONE_CREATE());
        } catch
        (\Exception) {
            return false;
        }
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, DeliveryZone $deliveryZone): bool
    {
        try {
            return $this->hasPermission(AdminPermissionEnum::DELIVERY_ZONE_EDIT());
        } catch
        (\Exception) {
            return false;
        }
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, DeliveryZone $deliveryZone): bool
    {
        try {
            return $this->hasPermission(AdminPermissionEnum::DELIVERY_ZONE_DELETE());
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, DeliveryZone $deliveryZone): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, DeliveryZone $deliveryZone): bool
    {
        return false;
    }
}
