<?php

namespace App\Policies;

use App\Enums\AdminPermissionEnum;
use App\Models\FeaturedSection;
use App\Models\User;
use App\Traits\ChecksPermissions;

class FeaturedSectionPolicy
{
    use ChecksPermissions;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasPermission(AdminPermissionEnum::FEATURED_SECTION_VIEW());
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, FeaturedSection $featuredSection): bool
    {
        return $this->hasPermission(AdminPermissionEnum::FEATURED_SECTION_VIEW());
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->hasPermission(AdminPermissionEnum::FEATURED_SECTION_CREATE());
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, FeaturedSection $featuredSection): bool
    {
        return $this->hasPermission(AdminPermissionEnum::FEATURED_SECTION_EDIT());
    }
    /**
     * Determine whether the user can update the model.
     */
    public function sorting(User $user): bool
    {
        return $this->hasPermission(AdminPermissionEnum::FEATURED_SECTION_SORTING_MODIFY());
    }

    /**
     * Determine whether the user can view sorting page.
     */
    public function sortingView(User $user): bool
    {
        return $this->hasPermission(AdminPermissionEnum::FEATURED_SECTION_SORTING_VIEW());
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, FeaturedSection $featuredSection): bool
    {
        return $this->hasPermission(AdminPermissionEnum::FEATURED_SECTION_DELETE());
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, FeaturedSection $featuredSection): bool
    {
        return $this->hasPermission(AdminPermissionEnum::FEATURED_SECTION_EDIT());
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, FeaturedSection $featuredSection): bool
    {
        return $this->hasPermission(AdminPermissionEnum::FEATURED_SECTION_DELETE());
    }
}
