<?php

namespace App\Policies;

use App\Enums\AdminPermissionEnum;
use App\Models\GiftSection;
use App\Models\User;
use App\Traits\ChecksPermissions;

class GiftSectionPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool { return $this->hasPermission(AdminPermissionEnum::GIFT_SECTION_VIEW()); }
    public function view(User $user, GiftSection $section): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return $this->hasPermission(AdminPermissionEnum::GIFT_SECTION_CREATE()); }
    public function update(User $user, GiftSection $section): bool { return $this->hasPermission(AdminPermissionEnum::GIFT_SECTION_EDIT()); }
    public function delete(User $user, GiftSection $section): bool { return $this->hasPermission(AdminPermissionEnum::GIFT_SECTION_DELETE()); }
}
