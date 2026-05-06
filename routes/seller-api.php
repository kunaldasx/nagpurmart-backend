<?php

use App\Http\Controllers\Api\Seller\SellerAuthApiController;
use App\Http\Controllers\Api\Seller\SellerAttributeApiController;
use App\Http\Controllers\Api\Seller\SellerAttributeValueApiController;
use App\Http\Controllers\Api\Seller\SellerStoreApiController;
use App\Http\Controllers\Api\Seller\SellerProductApiController;
use App\Http\Controllers\Api\Seller\SellerTaxClassApiController;
use App\Http\Controllers\Api\Seller\SellerDashboardApiController;
use App\Http\Controllers\Api\Seller\SellerOrderApiController;
use App\Http\Controllers\Api\Seller\SellerWalletApiController;
use App\Http\Controllers\Api\Seller\SellerWithdrawalApiController;
use App\Http\Controllers\Api\Seller\SellerEarningApiController;
use App\Http\Controllers\Api\Seller\SellerBrandApiController;
use App\Http\Controllers\Api\Seller\SellerProductFaqApiController;
use App\Http\Controllers\Api\Seller\SellerRoleApiController;
use App\Http\Controllers\Api\Seller\SellerPermissionApiController;
use App\Http\Controllers\Api\Seller\SubscriptionPlanApiController as SellerSubscriptionPlanApiController;
use App\Http\Controllers\Api\Seller\SellerSystemUserApiController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\Api\Seller\SellerNotificationApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('seller')->name('seller-api.')->group(function () {
    Route::post('register', [SellerAuthApiController::class, 'createSeller'])->name('register');
    Route::post('login', [SellerAuthApiController::class, 'login'])->name('login');
});

Route::middleware(['auth:sanctum',
//    'ensure.seller.subscription'
])->prefix('seller')->name('seller.api')->group(function () {
    Route::post('logout', [SellerAuthApiController::class, 'logout']);

    // Dashboard (single endpoint only)
    Route::get('dashboard', [SellerDashboardApiController::class, 'overview'])->name('dashboard.overview');

    // Notifications (seller)
    Route::prefix('notifications')->group(function () {
        Route::get('/', [SellerNotificationApiController::class, 'index']);
        Route::get('/unread-count', [SellerNotificationApiController::class, 'unreadCount']);
        Route::post('/mark-all-read', [SellerNotificationApiController::class, 'markAllAsRead']);
        Route::post('/{id}/read', [SellerNotificationApiController::class, 'markAsRead']);
        Route::post('/{id}/unread', [SellerNotificationApiController::class, 'markAsUnread']);
    });

    // Stores (seller-owned)
    Route::prefix('stores')->name('stores.')->group(function () {
        Route::get('/', [SellerStoreApiController::class, 'index'])->name('index');
        Route::get('/enums', [SellerStoreApiController::class, 'getStoresEnums'])->name('enums');
        Route::get('/{id}', [SellerStoreApiController::class, 'show'])->name('show');
        Route::post('/', [SellerStoreApiController::class, 'store'])->name('store');
        Route::post('/{id}', [SellerStoreApiController::class, 'update'])->name('update');
        Route::delete('/{id}', [SellerStoreApiController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/status', [SellerStoreApiController::class, 'updateStatus'])->name('update_status');
    });

    // Attributes CRUD
    Route::prefix('attributes')->name('attributes.')->group(function () {
        Route::get('/', [SellerAttributeApiController::class, 'index'])->name('index');
        Route::get('/{id}', [SellerAttributeApiController::class, 'show'])->name('show');
        Route::post('/', [SellerAttributeApiController::class, 'store'])->name('store');
        Route::post('/{id}', [SellerAttributeApiController::class, 'update'])->name('update');
        Route::delete('/{id}', [SellerAttributeApiController::class, 'destroy'])->name('destroy');
    });

    // Attribute values CRUD
    Route::prefix('attribute-values')->name('attribute_values.')->group(function () {
        Route::get('/', [SellerAttributeValueApiController::class, 'index'])->name('index');
        Route::get('/{id}', [SellerAttributeValueApiController::class, 'show'])->name('show');
        Route::post('/', [SellerAttributeValueApiController::class, 'store'])->name('store');
        Route::post('/{id}', [SellerAttributeValueApiController::class, 'update'])->name('update');
        Route::delete('/{id}', [SellerAttributeValueApiController::class, 'destroy'])->name('destroy');
    });

    // Products CRUD
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', [SellerProductApiController::class, 'index'])->name('index');
        Route::get('/enums', [SellerProductApiController::class, 'getProductEnums'])->name('enums');
        Route::get('/{id}', [SellerProductApiController::class, 'show'])->name('show');
        Route::post('/', [SellerProductApiController::class, 'store'])->name('store');
        Route::post('/{id}', [SellerProductApiController::class, 'update'])->name('update');
        Route::post('/{id}/update-status', [SellerProductApiController::class, 'updateStatus'])->name('update-status');
        Route::delete('/{id}', [SellerProductApiController::class, 'destroy'])->name('destroy');
    });

    // Product FAQs (seller-managed)
    Route::prefix('product-faqs')->name('product_faqs.')->group(function () {
        Route::get('/', [SellerProductFaqApiController::class, 'index'])->name('index');
        Route::get('/{id}', [SellerProductFaqApiController::class, 'show'])->name('show');
        Route::post('/', [SellerProductFaqApiController::class, 'store'])->name('store');
        Route::post('/{id}', [SellerProductFaqApiController::class, 'update'])->name('update');
        Route::delete('/{id}', [SellerProductFaqApiController::class, 'destroy'])->name('destroy');
    });

    // Tax Classes (read-only for sellers)
    Route::prefix('tax-classes')->name('tax_classes.')->group(function () {
        Route::get('/', [SellerTaxClassApiController::class, 'index'])->name('index');
        Route::get('/{id}', [SellerTaxClassApiController::class, 'show'])->name('show');
    });

    // Brands (read-only for sellers)
    Route::prefix('brands')->name('brands.')->group(function () {
        Route::get('/', [SellerBrandApiController::class, 'index'])->name('index');
        Route::get('/{id}', [SellerBrandApiController::class, 'show'])->name('show');
    });

    // Orders (seller-managed)
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [SellerOrderApiController::class, 'index'])->name('index');
        Route::get('/enums', [SellerOrderApiController::class, 'enums'])->name('enums');
        Route::get('/{id}', [SellerOrderApiController::class, 'show'])->name('show');
        Route::post('/{id}/{status}', [SellerOrderApiController::class, 'updateStatus'])->name('update_status');
    });

    // Wallet
    Route::prefix('wallet')->name('wallet.')->group(function () {
        Route::get('/', [SellerWalletApiController::class, 'show'])->name('show');
        Route::get('/transactions', [SellerWalletApiController::class, 'transactions'])->name('transactions');
        Route::get('/transactions/{id}', [SellerWalletApiController::class, 'transaction'])->name('transactions.show');
    });

    // Withdrawals
    Route::prefix('withdrawals')->name('withdrawals.')->group(function () {
        Route::get('/', [SellerWithdrawalApiController::class, 'getWithdrawalRequests'])->name('index');
        Route::get('/history', [SellerWithdrawalApiController::class, 'history'])->name('history');
        Route::post('/', [SellerWithdrawalApiController::class, 'createWithdrawalRequest'])->name('store');
        Route::get('/{id}', [SellerWithdrawalApiController::class, 'getWithdrawalRequest'])->name('show');
    });

    // Earnings / Commissions
    Route::prefix('commissions')->name('commissions.')->group(function () {
        Route::get('/', [SellerEarningApiController::class, 'unsettled'])->name('index');
        Route::get('/debits', [SellerEarningApiController::class, 'unsettledDebits'])->name('debits');
        Route::get('/history', [SellerEarningApiController::class, 'history'])->name('history');
    });

    // Roles (seller-scoped)
    Route::prefix('roles')->name('roles.')->group(function () {
        Route::get('/', [SellerRoleApiController::class, 'index'])->name('index');
        Route::get('/list', [SellerRoleApiController::class, 'getRoles'])->name('list');
        Route::get('/{id}', [SellerRoleApiController::class, 'show'])->name('show');
        Route::post('/', [SellerRoleApiController::class, 'store'])->name('store');
        Route::post('/{id}', [SellerRoleApiController::class, 'update'])->name('update');
        Route::delete('/{id}', [SellerRoleApiController::class, 'destroy'])->name('destroy');
    });

    // Permissions (seller-scoped)
    Route::prefix('permissions')->name('permissions.')->group(function () {
        // Get grouped + assigned permissions for a role
        Route::get('/{role}', [SellerPermissionApiController::class, 'index'])->name('index');
        // Sync role permissions
        Route::post('/', [SellerPermissionApiController::class, 'store'])->name('store');
    });

    // System Users (seller-scoped)
    Route::prefix('system-users')->name('system_users.')->group(function () {
        Route::get('/', [SellerSystemUserApiController::class, 'index'])->name('index');
        Route::get('/{id}', [SellerSystemUserApiController::class, 'show'])->name('show');
        Route::post('/', [SellerSystemUserApiController::class, 'store'])->name('store');
        Route::post('/{id}', [SellerSystemUserApiController::class, 'update'])->name('update');
        Route::delete('/{id}', [SellerSystemUserApiController::class, 'destroy'])->name('destroy');
    });

    // Subscription usage (seller-scoped)
    Route::prefix('subscription')->name('subscription.')->group(function () {
        Route::post('/check-eligibility', [SellerSubscriptionPlanApiController::class, 'checkEligibility'])->name('eligibility.check');
        Route::post('/buy', [SellerSubscriptionPlanApiController::class, 'buy'])->name('buy');
        Route::get('/current', [SellerSubscriptionPlanApiController::class, 'current'])->name('current');
        Route::get('/{id}/status', [SellerSubscriptionPlanApiController::class, 'status'])->name('status');
        Route::get('/history', [SellerSubscriptionPlanApiController::class, 'history'])->name('history');
    });
});
