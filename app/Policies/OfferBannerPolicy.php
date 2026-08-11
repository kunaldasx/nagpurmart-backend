<?php

namespace App\Policies;

use App\Enums\AdminPermissionEnum;
use App\Models\OfferBanner;
use App\Models\User;
use App\Traits\ChecksPermissions;

class OfferBannerPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        try {
            return $this->hasPermission(AdminPermissionEnum::BANNER_VIEW());
        } catch (\Exception) {
            return false;
        }
    }

    public function view(User $user, OfferBanner $banner): bool
    {
        try {
            return $this->hasPermission(AdminPermissionEnum::BANNER_VIEW());
        } catch (\Exception) {
            return false;
        }
    }

    public function create(User $user): bool
    {
        try {
            return $this->hasPermission(AdminPermissionEnum::BANNER_CREATE());
        } catch (\Exception) {
            return false;
        }
    }

    public function update(User $user, OfferBanner $banner): bool
    {
        try {
            return $this->hasPermission(AdminPermissionEnum::BANNER_EDIT());
        } catch (\Exception) {
            return false;
        }
    }

    public function delete(User $user, OfferBanner $banner): bool
    {
        try {
            return $this->hasPermission(AdminPermissionEnum::BANNER_DELETE());
        } catch (\Exception) {
            return false;
        }
    }

    public function restore(User $user, OfferBanner $banner): bool
    {
        return false;
    }

    public function forceDelete(User $user, OfferBanner $banner): bool
    {
        return false;
    }
}
