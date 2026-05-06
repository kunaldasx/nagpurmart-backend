<?php

namespace App\Http\Middleware;

use App\Enums\SettingTypeEnum;
use App\Services\SettingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSystemVendorTypeSelected
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Allow the selection page and its POST handler to be accessible
        // Also allow logout to work regardless
        $routeName = optional($request->route())->getName();
        if (in_array($routeName, ['admin.system-type', 'admin.system-type.store', 'admin.logout'])) {
            return $next($request);
        }

        // Only enforce inside admin panel paths
        if ($request->is('admin/*') || $request->is('admin')) {
            /** @var SettingService $settingService */
            $settingService = app(SettingService::class);
            $resource = $settingService->getSettingByVariable(SettingTypeEnum::SYSTEM());
            $settings = $resource ? ($resource->toArray($request)['value'] ?? []) : [];

            $systemVendorType = $settings['systemVendorType'] ?? '';
            if (empty($systemVendorType)) {
                return redirect()->route('admin.system-type')
                    ->with('warning', __('labels.system_vendor_type_required') ?? 'Please select the System Type to continue.');
            }
        }

        return $next($request);
    }
}
