<aside class="navbar navbar-vertical navbar-expand-lg overflow-auto" data-bs-theme="dark">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu"
                aria-controls="sidebar-menu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="navbar-brand navbar-brand-autodark">
            @php
                use App\Enums\AdminPermissionEnum;
                use App\Enums\SellerPermissionEnum;
                use App\Enums\DefaultSystemRolesEnum;
                use App\Models\Setting;use Illuminate\Support\Facades\Auth;
                $panel = request()->segment(1);
            @endphp
            @php
                // When impersonating, ensure the brand link points to the seller panel
                $isImpersonatingForLogo = session('impersonating_as_seller', false);
                $linkPanel = $isImpersonatingForLogo ? 'seller' : $panel;
            @endphp
            <a href="{{ url($linkPanel) }}">
                <img
                    src="{{ !empty($systemSettings['logo']) ? $systemSettings['logo'] : asset('logos/hyper-local-logo-white.png') }}"
                    alt="{{$systemSettings['appName'] ?? ""}}" width="150">
            </a>
        </div>

        <div class="collapse navbar-collapse" id="sidebar-menu">
            <ul class="navbar-nav pt-lg-3">
                @php
                    // Get the menu items based on the authenticated user's role.
                    $userRole = Auth::user();
                    // If impersonating as seller, force the seller panel for the sidebar
                    $isImpersonating = session('impersonating_as_seller', false);
                    $currentPanel = $isImpersonating ? 'seller' : ($userRole->access_panel->value ?? $panel);
                    $menuItems = config("menu.{$currentPanel}", []);

                    // Choose the correct permission set per panel
                    $allPerms = $currentPanel === 'admin'
                        ? AdminPermissionEnum::values()
                        : (($currentPanel === 'seller') ? SellerPermissionEnum::values() : []);

                    // Super Admin bypass only applies to admin panel
                    $isSuperAdmin = $currentPanel === 'admin' && $userRole->hasRole(DefaultSystemRolesEnum::SUPER_ADMIN());

                    // Default Seller role can view the full seller module
                    // While impersonating as seller, treat the user as default seller for menu visibility
                    $isDefaultSeller = $currentPanel === 'seller' && ($isImpersonating || $userRole->hasRole(DefaultSystemRolesEnum::SELLER()));

                    $canSee = function ($perm) use ($userRole, $allPerms, $isSuperAdmin, $isDefaultSeller) {
                        // If default seller role on seller panel -> always visible
                        if ($isDefaultSeller) {
                            return true;
                        }
                        // No permission specified -> visible
                        if (empty($perm)) {
                            return true;
                        }
                        // If permission slug does not exist in the panel's enum -> default visible
                        if (!in_array($perm, $allPerms, true)) {
                            return true;
                        }
                        // Super Admin bypass
                        if ($isSuperAdmin) {
                            return true;
                        }
                        try {
                            return $userRole->hasPermissionTo($perm);
                        } catch (\Throwable $e) {
                            return false;
                        }
                    };
                @endphp

                @foreach ($menuItems as $key => $item)
                    @php
                        // Determine if the menu item has sub-routes (dropdown) or a single link.
                        $isDropdown = is_array($item['route']);
                        $isActive = isset($page, $item['active']) && $page === $item['active'];
                        // Hide entire subscription module when system is Single Vendor (both admin and seller panels)
                        if (Setting::isSystemVendorTypeSingle() && $key === 'subscriptions') {
                            continue;
                        }
                        if (!Setting::isSubscriptionEnabled() && $key === 'subscriptions' && $currentPanel === 'seller') {
                            continue;
                        }
                        // Check permission for single link or any children for dropdown
                        if (!$isDropdown) {
                            if (!$canSee($item['permission'] ?? null)) {
                                continue; // skip this menu item if not permitted
                            }
                        }
                        // For dropdowns, pre-filter sub items; if none remain, skip parent entirely
                        $visibleSubRoutes = null;
                        if ($isDropdown) {
                            $visibleSubRoutes = [];
                            foreach ($item['route'] as $srKey => $subRoute) {
                                // Hide Add Sellers from admin sidebar when system is single-vendor
                                if ($currentPanel === 'admin'
                                    && $srKey === 'add_sellers'
                                    && Setting::isSystemVendorTypeSingle()) {
                                    continue;
                                }
                                $perm = is_array($subRoute) ? ($subRoute['permission'] ?? null) : null;
                                if ($canSee($perm)) {
                                    $visibleSubRoutes[$srKey] = $subRoute;
                                }
                            }
                            if (count($visibleSubRoutes) === 0) {
                                continue; // no visible children => hide the whole dropdown
                            }
                        }
                    @endphp

                    <li class="nav-item {{ $isDropdown ? 'dropdown' : '' }} {{ $isActive ? 'active' : '' }}">
                        @if ($isDropdown)
                            <a class="nav-link dropdown-toggle {{ $isActive ? 'active show' : '' }}"
                               href="#navbar-{{ $key }}" data-bs-toggle="dropdown" data-bs-auto-close="false"
                               aria-expanded="{{ $isActive ? 'true' : 'false' }}">
                                <span class="nav-link-icon d-md-none d-lg-inline-block">
                                    <i class="ti {{ $item['icon'] }} fs-2"></i>
                                </span>
                                <span class="nav-link-title">{{ __($item['title']) }}</span>
                            </a>
                            @if (count($visibleSubRoutes) > 0)
                                <div class="dropdown-menu {{ $isActive ? 'show' : '' }}">
                                    @foreach ($visibleSubRoutes as $subRoute)
                                        @php
                                            // If the sub-route is an array, support passing route parameters.
                                            if (is_array($subRoute)) {
                                                // If the array has an explicit 'route' key, use it; otherwise, assume the first value is the route name.
                                                $routeName = $subRoute['route'] ?? array_values($subRoute)[0];
                                                $routeParams =
                                                    $subRoute['params'] ?? (is_array($subRoute) ? $subRoute : []);
                                            } else {
                                                $routeName = $subRoute;
                                                $routeParams = [];
                                            }
                                            $isSubActive =
                                                isset($subRoute) &&
                                                isset($sub_page) &&
                                                $subRoute['sub_active'] === $sub_page;
    //                                        dd($subRoute['sub_title'])
                                        @endphp
                                        <a class="dropdown-item {{ $isSubActive ? 'active' : '' }}"
                                           href="{{route($subRoute['sub_route'],$subRoute['route_param'] ?? []) }}">
                                            <i class="ti ti-point{{ $isSubActive ? '-filled' : '' }} fs-2"></i>{{ __($subRoute['sub_title']) }}
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        @else
                            <a class="nav-link {{ $isActive ? 'active' : '' }}" href="{{ route($item['route']) }}">
                                <span class="nav-link-icon">
                                    <i class="ti {{ $item['icon'] }} fs-2"></i>
                                </span>
                                <span class="nav-link-title">{{ __($item['title']) }}</span>
                            </a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
        <div
            class="d-flex justify-content-center align-content-center pt-2 pb-5 fw-medium">
            V{{\App\Services\SystemUpdater::getCurrentVersion()}}</div>
    </div>
</aside>
