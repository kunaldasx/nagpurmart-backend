<?php

use App\Http\Middleware\ActiveDeliveryBoy;
use App\Http\Middleware\CheckInstallation;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\EnsureSystemVendorTypeSelected;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\StorageCorsMiddleware;
use App\Http\Middleware\ValidateAdmin;
use App\Http\Middleware\ValidateSeller;
use App\Http\Middleware\EnsureSellerHasSubscription;
use App\Http\Middleware\VerifiedDeliveryBoy;
use App\Http\Middleware\CheckMaintenanceMode;
use App\Http\Middleware\SellerQueryTokenAuthenticate;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use App\Http\Middleware\VerifyLicense;
use App\Http\Middleware\EnsureSubscriptionFeatureSelected;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: [
            __DIR__ . '/../routes/api.php',
            __DIR__ . '/../routes/delivery-boy-api.php',
        ],
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    // Register application and package service providers (Laravel 11/12 style)
    ->withProviders(require __DIR__ . '/providers.php')
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('admin/*') || $request->is('admin')) {
                return route('admin.login');
            } elseif ($request->is('seller/*') || $request->is('seller')) {
                return route('seller.login');
            }
            return route('login');
        });
        $middleware->redirectUsersTo(function (Request $request) {
            if ($request->is('admin/login') || $request->is('admin')) {
                return route('admin.dashboard');
            } elseif ($request->is('seller/login') || $request->is('seller')) {
                return route('seller.dashboard');
            }
            return redirect('/');
        });
        $middleware->alias([
            'validate.admin' => ValidateAdmin::class,
            'validate.seller' => ValidateSeller::class,
            'ensure.seller.subscription' => EnsureSellerHasSubscription::class,
            'seller.query.token' => SellerQueryTokenAuthenticate::class,
            'permission' => CheckPermission::class,
            'verified.delivery.boy' => VerifiedDeliveryBoy::class,
            'active.delivery.boy' => ActiveDeliveryBoy::class,
            'locale' => SetLocale::class,
            'storage.cors' => StorageCorsMiddleware::class,
            'maintenance' => CheckMaintenanceMode::class,
            'license' => VerifyLicense::class,
            'ensure.system.type' => EnsureSystemVendorTypeSelected::class,
            'ensure.subscription.feature' => EnsureSubscriptionFeatureSelected::class,
        ]);

        $middleware->web(prepend: [
            CheckMaintenanceMode::class,
            CheckInstallation::class,
            VerifyLicense::class,
        ], append: [
            SetLocale::class,
            StorageCorsMiddleware::class,
        ]);

        $middleware->api(prepend: [
            CheckMaintenanceMode::class,
            VerifyLicense::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
