<?php

namespace App\Policies;

use App\Enums\AdminPermissionEnum;
use App\Models\HighlightedSection;
use App\Models\User;
use App\Traits\ChecksPermissions;

class HighlightedSectionPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool { return $this->hasPermission(AdminPermissionEnum::HIGHLIGHTED_SECTION_VIEW()); }
    public function view(User $user, HighlightedSection $section): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return $this->hasPermission(AdminPermissionEnum::HIGHLIGHTED_SECTION_CREATE()); }
    public function update(User $user, HighlightedSection $section): bool { return $this->hasPermission(AdminPermissionEnum::HIGHLIGHTED_SECTION_EDIT()); }
    public function delete(User $user, HighlightedSection $section): bool { return $this->hasPermission(AdminPermissionEnum::HIGHLIGHTED_SECTION_DELETE()); }
}