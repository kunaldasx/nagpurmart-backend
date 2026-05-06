<?php

namespace App\Http\Middleware;

use App\Enums\SettingTypeEnum;
use App\Enums\SystemVendorTypeEnum;
use App\Services\SettingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionFeatureSelected
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Allow subscription decision page and system type selection, and logout
        $routeName = optional($request->route())->getName();
        if (in_array($routeName, [
            'admin.subscription-feature',
            'admin.subscription-feature.store',
            'admin.system-type',
            'admin.system-type.store',
            'admin.logout',
        ], true)) {
            return $next($request);
        }

        // Only enforce within admin panel URLs
        if ($request->is('admin/*') || $request->is('admin')) {
            /** @var SettingService $settingService */
            $settingService = app(SettingService::class);

            // Get system settings to check vendor type
            $systemResource = $settingService->getSettingByVariable(SettingTypeEnum::SYSTEM());
            $systemSettings = $systemResource ? ($systemResource->toArray($request)['value'] ?? []) : [];
            $vendorType = $systemSettings['systemVendorType'] ?? SystemVendorTypeEnum::MULTIPLE();

            // Enforce only for multi-vendor systems
            if ($vendorType === SystemVendorTypeEnum::MULTIPLE()) {
                $subscriptionResource = $settingService->getSettingByVariable(SettingTypeEnum::SUBSCRIPTION());
                $subscriptionSettings = $subscriptionResource ? ($subscriptionResource->toArray($request)['value'] ?? []) : [];
                $decision = $subscriptionSettings['enableSubscription'] ?? null; // null|bool

                if ($decision === null) {
                    return redirect()->route('admin.subscription-feature')
                        ->with('warning', __('labels.subscription_feature_decision_required'));
                }
            }
        }

        return $next($request);
    }
}
