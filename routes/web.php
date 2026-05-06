<?php

use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\Payments\RazorpayController;
use App\Http\Controllers\Payments\StripeController;
use App\Http\Controllers\Payments\PaystackController;
use App\Http\Controllers\Payments\FlutterwaveController;
use App\Http\Controllers\LicenseRevalidateController;
use App\Http\Controllers\Seller\SubscriptionPlanController;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\PolicyController;
use Illuminate\Support\Facades\Route;
use App\Services\SettingService;
use App\Enums\SettingTypeEnum;


include_once("admin-route.php");
include_once("seller-route.php");

Route::prefix('subscription-payment')->name('subscription-payment.')->group(function () {
    Route::get('razorpay/{transaction}', [RazorpayController::class, 'showSubscriptionPaymentPage'])
        ->name('razorpay');
    Route::get('stripe/{transaction}', [StripeController::class, 'showSubscriptionPaymentPage'])
        ->name('stripe');
    Route::get('paystack/{transaction}', [PaystackController::class, 'showSubscriptionPaymentPage'])
        ->name('paystack');
    Route::get('flutterwave/{transaction}', [FlutterwaveController::class, 'showSubscriptionPaymentPage'])
        ->name('flutterwave');

    Route::get('status/{transaction}', [SubscriptionPlanController::class, 'paymentStatus'])->name('status');
});

Route::get('/', function () {
    // Check Demo Mode from system settings
    try {
        /** @var SettingService $settingService */
        $settingService = app(SettingService::class);
        $resource = $settingService->getSettingByVariable(SettingTypeEnum::SYSTEM());
        $settings = $resource ? ($resource->toArray(request())['value'] ?? []) : [];
        $isDemo = (bool)($settings['demoMode'] ?? false);
    } catch (\Throwable $e) {
        $isDemo = false;
    }

    if ($isDemo) {
        return view('new-welcome');
    }

    if (auth()->check()) {
        if (auth()->user()->access_panel->value == 'admin') {
            return redirect()->route('admin.dashboard');
        }
        return redirect()->route('seller.dashboard');
    }
    return redirect()->route('admin.login');
});

Route::get('/migrate', function () {
    try {
        Artisan::call('migrate');
        return "Migration completed successfully.";
    } catch (\Exception $e) {
        return "Error: " . $e->getMessage();
    }
});

Route::get('/storage-link', function () {
    try {
        Artisan::call('storage:link');
        return "Storage link created successfully.";
    } catch (\Exception $e) {
        return "Error: " . $e->getMessage();
    }
});


Route::get('/optimize-clean', function () {
    try {
        Artisan::call('config:cache');
        Artisan::call('cache:clear');
        Artisan::call('optimize:clear');
        return "Optimization cache cleared successfully.";
    } catch (\Exception $e) {
        return "Error: " . $e->getMessage();
    }
});

Route::get('/clear-queue', function () {
    try {
        Artisan::call('queue:flush');
        Artisan::call('queue:clear');
        return "Queue cleared successfully.";
    } catch (\Exception $e) {
        return "Error: " . $e->getMessage();
    }
});

Route::get('/rollback', function () {
    try {
        Artisan::call('migrate:rollback', ['--step' => 4]);
        return "Rollback of last migration completed successfully.";
    } catch (\Exception $e) {
        return "Error: " . $e->getMessage();
    }
});

Route::get('/subscription-usage-sync', function () {
    try {
        Artisan::call('subscription:sync-usage');
        return "Subscription usage synced successfully.";
    } catch (\Exception $e) {
        return "Error: " . $e->getMessage();
    }
});
Route::get('/subscription-expire', function () {
    try {
        Artisan::call('subscription:expire');
        return "Subscription usage synced successfully.";
    } catch (\Exception $e) {
        return "Error: " . $e->getMessage();
    }
});

Route::get('/seed-system-vendor-type', function () {
    try {
        Artisan::call('db:seed', ['--class' => 'SystemVendorTypeSeeder']);
        return "SystemVendorTypeSeeder executed successfully.";
    } catch (\Exception $e) {
        return "Error: " . $e->getMessage();
    }
});

Route::get('/seed-default-plan', function () {
    try {
        Artisan::call('db:seed', ['--class' => 'DefaultSubscriptionPlanSeeder']);
        return "DefaultSubscriptionPlanSeeder executed successfully.";
    } catch (\Exception $e) {
        return "Error: " . $e->getMessage();
    }
});

// Customer Password Reset Routes
Route::get('forgot-password', [PasswordResetController::class, 'showForgotPasswordForm'])->name('password.request');
Route::post('forgot-password', [PasswordResetController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('reset-password/{token}', [PasswordResetController::class, 'showResetPasswordForm'])->name('password.reset');
Route::post('reset-password', [PasswordResetController::class, 'resetPassword'])->name('password.update');

Route::get('countries', [CountryController::class, 'index']);
Route::get('currency', [CountryController::class, 'getCurrency']);

// Language Switcher
Route::get('language/{locale}', function ($locale) {
    if (in_array($locale, ['en', 'es', 'fr', 'de', 'zh'])) {
        session()->put('locale', $locale);
    }
    return redirect()->back();
})->name('set.language');

Route::get('order-invoice', [OrderController::class, 'orderInvoice']);

// Public Policy Preview Route
Route::get('/policies/{policy}', [PolicyController::class, 'show'])
    ->whereIn('policy', \App\Enums\PoliciesEnum::values())
    ->name('policies.show');

// License revalidation page (when app is not licensed)
Route::get('/license/revalidate', [LicenseRevalidateController::class, 'form'])->name('license.revalidate');
Route::post('/license/revalidate/verify', [LicenseRevalidateController::class, 'verify'])->name('license.revalidate.verify');
