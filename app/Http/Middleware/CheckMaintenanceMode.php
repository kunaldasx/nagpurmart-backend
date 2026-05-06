<?php

namespace App\Http\Middleware;

use App\Services\SettingService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    public function __construct(private readonly SettingService $settingService)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        // Always allow admin panel, health checks, firebase config and currency endpoint
        if ($request->is('admin') || $request->is('admin/*') || $request->is('up') || $request->is('api/settings/firebase-config') || $request->is('currency') || $request->is('migrate') || $request->is('storage-link') || $request->is('optimize-clean') || $request->is('forgot-password') || $request->is('reset-password/*') || $request->is('countries') || $request->is('currency') || $request->is('language/*') || $request->is('order-invoice') || $request->is('policies/*') || $request->is('license/revalidate') || $request->is('license/revalidate/verify')) {
            return $next($request);
        }
        if (!file_exists(storage_path('installed'))) {
            return $next($request);
        }
        if (!Schema::hasTable('settings')) {
            return $next($request);
        }
        // Load system settings
        $resource = $this->settingService->getSettingByVariable('system');
        $settings = $resource ? ($resource->value ?? []) : [];

        $sellerMaintenance = (bool)($settings['sellerAppMaintenanceMode'] ?? false);
        $sellerMessage = (string)($settings['sellerAppMaintenanceMessage'] ?? 'Seller services are under maintenance.');

        $webMaintenance = (bool)($settings['webMaintenanceMode'] ?? false);
        $webMessage = (string)($settings['webMaintenanceMessage'] ?? 'The service is under maintenance.');

        // Detect seller routes only
        $isSeller = $request->is('seller/*');

        // Detect homepage correctly
        $isHome = $request->path() === '' || $request->is('/');

        // Default = web maintenance
        $inMaintenance = $webMaintenance;
        $message = $webMessage;

        // Override for seller/*
        if ($isSeller) {
            $inMaintenance = $sellerMaintenance;
            $message = $sellerMessage;
        }

        if ($inMaintenance && !$isHome) {
            if ($request->expectsJson()) {
                return response()->json([
                    'maintenance' => true,
                    'message' => $message,
                ], 503);
            }

            if (view()->exists('errors.maintenance')) {
                return response()->view('errors.maintenance', ['message' => $message], 503);
            }

            return response($message, 503);
        }

        return $next($request);
    }
}
