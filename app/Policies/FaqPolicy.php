<?php

namespace App\Policies;

use App\Enums\AdminPermissionEnum;
use App\Models\Faq;
use App\Models\User;
use App\Traits\ChecksPermissions;

class FaqPolicy
{
    use ChecksPermissions;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        try {
            return $this->hasPermission(AdminPermissionEnum::FAQ_VIEW());
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Faq $Faq): bool
    {
        try {
            return $this->hasPermission(AdminPermissionEnum::FAQ_VIEW());
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->hasPermission(AdminPermissionEnum::FAQ_CREATE());
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Faq $Faq): bool
    {
        return $this->hasPermission(AdminPermissionEnum::FAQ_EDIT());
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Faq $Faq): bool
    {
        return $this->hasPermission(AdminPermissionEnum::FAQ_DELETE());
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Faq $Faq): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Faq $Faq): bool
    {
        return false;
    }
}
