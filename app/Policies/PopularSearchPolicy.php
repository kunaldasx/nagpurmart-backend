<?php

namespace App\Policies;

use App\Enums\AdminPermissionEnum;
use App\Models\PopularSearch;
use App\Models\User;
use App\Traits\ChecksPermissions;

class PopularSearchPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool { return $this->hasPermission(AdminPermissionEnum::POPULAR_SEARCH_VIEW()); }
    public function view(User $user, PopularSearch $popularSearch): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return $this->hasPermission(AdminPermissionEnum::POPULAR_SEARCH_CREATE()); }
    public function update(User $user, PopularSearch $popularSearch): bool { return $this->hasPermission(AdminPermissionEnum::POPULAR_SEARCH_EDIT()); }
    public function delete(User $user, PopularSearch $popularSearch): bool { return $this->hasPermission(AdminPermissionEnum::POPULAR_SEARCH_DELETE()); }
}