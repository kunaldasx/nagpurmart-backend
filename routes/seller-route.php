<?php

use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\GlobalAttributeController;
use App\Http\Controllers\GlobalAttributeValueController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProductConditionController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductFaqController;
use App\Http\Controllers\ProductImageUploadController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReturnRequestController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\Seller\AuthController;
use App\Http\Controllers\Seller\DashboardController;
use App\Http\Controllers\Seller\WalletController;
use App\Http\Controllers\Seller\WithdrawalController;
use App\Http\Controllers\Seller\SubscriptionPlanController;
use App\Http\Controllers\SellerEarningController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\SystemUserController;
use App\Http\Controllers\TaxClassController;
use App\Http\Controllers\TaxRateController;
use Illuminate\Support\Facades\Route;

Route::prefix('seller')->name('seller.')->group(function () {
    Route::middleware(['guest'])->group(function () {
        Route::get('login', [AuthController::class, 'loginSeller'])->name('login');
        Route::post('login', [AuthController::class, 'login'])->name('login.post');
        Route::get('/', function () {
            return view('seller.auth.login');
        })->name('login.index');

        // Password Reset Routes
        Route::get('forgot-password', [PasswordResetController::class, 'showForgotPasswordForm'])->name('password.request');
        Route::post('forgot-password', [PasswordResetController::class, 'sendResetLinkEmail'])->name('password.email');
        Route::get('reset-password/{token}', [PasswordResetController::class, 'showResetPasswordForm'])->name('password.reset');
        Route::post('reset-password', [PasswordResetController::class, 'resetPassword'])->name('password.update');
    });

    Route::middleware(['seller.query.token', 'validate.seller',
    ])->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('index');

        Route::get('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('dashboard/chart-data', [DashboardController::class, 'getChartData'])->name('dashboard.chart-data');
        Route::get('dashboard/data', [DashboardController::class, 'getDashboardData'])->name('dashboard.data');


        // Roles
        Route::prefix('roles')->name('roles.')->group(function () {
            Route::get('/', [RoleController::class, 'index'])->name('index');
            Route::post('/', [RoleController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [RoleController::class, 'edit'])->name('edit');
            Route::post('/{id}', [RoleController::class, 'update'])->name('update');
            Route::delete('/{id}', [RoleController::class, 'destroy'])->name('destroy');
            Route::get('/get-roles', [RoleController::class, 'getRoles'])->name('datatable');
            Route::get('/{role}/permissions', [PermissionController::class, 'index'])->name('permissions.index');
        });

        // permissions
        Route::prefix('permissions')->name('permissions.')->group(function () {
            Route::post('/', [PermissionController::class, 'store'])->name('store');
        });

        // profile
        Route::prefix('profile')->name('profile.')->group(function () {
            Route::get('/', [ProfileController::class, 'index'])->name('index');
            Route::get('/edit', [ProfileController::class, 'edit'])->name('edit');
            Route::post('/update', [ProfileController::class, 'update'])->name('update');
            Route::post('/password-update', [ProfileController::class, 'changePassword'])->name('password.update');
        });

        // System Users
        Route::prefix('system-users')->name('system-users.')->group(function () {
            Route::get('/', [SystemUserController::class, 'index'])->name('index');
            Route::post('/', [SystemUserController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [SystemUserController::class, 'show'])->name('show');
            Route::post('/{id}', [SystemUserController::class, 'update'])->name('update');
            Route::delete('/{id}', [SystemUserController::class, 'destroy'])->name('destroy');
            Route::get('/datatable', [SystemUserController::class, 'getSystemUsers'])->name('datatable');
        });

        // brands
        Route::prefix('brands')->name('brands.')->group(function () {
            Route::get('/', [BrandController::class, 'index'])->name('index');
            Route::post('/', [BrandController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [BrandController::class, 'show'])->name('edit');
            Route::post('/{id}', [BrandController::class, 'update'])->name('update');
            Route::delete('/{id}', [BrandController::class, 'destroy'])->name('delete');
            Route::get('/datatable', [BrandController::class, 'getBrands'])->name('datatable');
            Route::get('/search', [BrandController::class, 'search'])->name('search');

        });

        // categories
        Route::prefix('categories')->name('categories.')->group(function () {
            Route::get('/', [CategoryController::class, 'index'])->name('index');
            Route::post('/', [CategoryController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [CategoryController::class, 'show'])->name('edit');
            Route::post('/{id}', [CategoryController::class, 'update'])->name('update');
            Route::delete('/{id}', [CategoryController::class, 'destroy'])->name('delete');
            Route::get('/datatable', [CategoryController::class, 'getCategories'])->name('datatable');
            Route::get('/search', [CategoryController::class, 'search'])->name('search');
        });

        // taxes
        Route::prefix('tax-rates')->name('tax-rates.')->group(function () {
            Route::get('/', [TaxRateController::class, 'index'])->name('index');
            Route::post('/', [TaxRateController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [TaxRateController::class, 'show'])->name('edit');
            Route::post('/{id}', [TaxRateController::class, 'update'])->name('update');
            Route::delete('/{id}', [TaxRateController::class, 'destroy'])->name('delete');
            Route::get('/datatable', [TaxRateController::class, 'getTaxRates'])->name('datatable');
            Route::get('/search', [TaxRateController::class, 'search'])->name('search');
        });

        // Subscription plans (web pages for sellers)
        Route::prefix('subscription-plans')->name('subscription-plans.')->group(function () {
            Route::get('/', [SubscriptionPlanController::class, 'index'])->name('index');
            Route::get('/current', [SubscriptionPlanController::class, 'current'])->name('current');
            Route::get('/history', [SubscriptionPlanController::class, 'history'])->name('history');
            Route::get('/history/datatable', [SubscriptionPlanController::class, 'historyDatatable'])->name('history.datatable');
            Route::get('/{plan}', [SubscriptionPlanController::class, 'show'])
                ->whereNumber('plan')
                ->name('show');
            Route::post('/check-eligibility', [SubscriptionPlanController::class, 'checkEligibility'])->name('eligibility');
            Route::post('/buy', [SubscriptionPlanController::class, 'buy'])->name('buy');
            Route::post('/skip', [SubscriptionPlanController::class, 'skip'])->name('skip');
        });

        // tax classes
        Route::prefix('tax-classes')->name('tax-classes.')->group(function () {
            Route::get('/', [TaxClassController::class, 'index'])->name('index');
            Route::post('/', [TaxClassController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [TaxClassController::class, 'show'])->name('edit');
            Route::post('/{id}', [TaxClassController::class, 'update'])->name('update');
            Route::delete('/{id}', [TaxClassController::class, 'destroy'])->name('delete');
            Route::get('/get-tax-classes', [TaxClassController::class, 'getTaxClasses'])->name('datatable');
            Route::get('/search', [TaxClassController::class, 'search'])->name('search');
        });

        // Stores
        Route::prefix('stores')->name('stores.')->group(function () {
            Route::get('/', [StoreController::class, 'index'])->name('index');
            Route::post('/', [StoreController::class, 'store'])->name('store');
            Route::get('/create', [StoreController::class, 'create'])->name('create');
            Route::get('/{id}/edit', [StoreController::class, 'edit'])->name('edit');
            Route::post('/{id}', [StoreController::class, 'update'])->name('update');
            Route::delete('/{id}', [StoreController::class, 'destroy'])->name('delete');
            Route::get('/datatable', [StoreController::class, 'getStores'])->name('datatable');
            Route::get('/list', [StoreController::class, 'StoreList'])->name('storesList');
            Route::get('/search', [StoreController::class, 'search'])->name('search');
            Route::get('/{id}/configuration', [StoreController::class, 'configuration'])->name('configuration');
            Route::post('/{id}/configuration', [StoreController::class, 'storeConfiguration'])->name('store_configuration');
            Route::post('/{id}/update-status', [StoreController::class, 'updateStatus'])->name('update-status');
        });

        // products
        Route::prefix('products')->name('products.')->group(function () {
            Route::get('/', [ProductController::class, 'index'])->name('index');
            Route::get('/create', [ProductController::class, 'create'])->name('create');
            Route::post('/', [ProductController::class, 'store'])->name('store');
            Route::get('/datatable', [ProductController::class, 'getProducts'])->name('datatable');
            Route::get('/search', [ProductController::class, 'search'])->name('search');
            Route::get('/download-template', [ProductController::class, 'downloadTemplate'])->name('download-template');
            // Bulk upload (Shopify-like CSV)
            Route::get('/bulk-upload', [ProductController::class, 'bulkUploadPage'])->name('bulk-upload.page');
            Route::post('/bulk-upload', [ProductController::class, 'bulkUpload'])->name('bulk-upload');
            // Separate images ZIP upload for products/variants
            Route::get('/images-upload', [ProductImageUploadController::class, 'imagesUploadPage'])->name('images-upload.page');
            Route::post('/images-upload', [ProductImageUploadController::class, 'imagesUpload'])->name('images-upload');
            Route::get('/images-upload/status/{token}', [ProductImageUploadController::class, 'imagesUploadStatus'])->name('images-upload.status');
            Route::get('/{id}/edit', [ProductController::class, 'edit'])->name('edit');
            Route::get('/{id}/pricing', [ProductController::class, 'getProductPricing'])->name('pricing');
            Route::post('/{id}', [ProductController::class, 'update'])->name('update');
            Route::post('/{id}/update-status', [ProductController::class, 'updateStatus'])->name('update-status');
            Route::delete('/{id}', [ProductController::class, 'destroy'])->name('delete');
            Route::get('/{id}', [ProductController::class, 'show'])->name('show');
        });

        // global attributes
        Route::prefix('attributes')->name('attributes.')->group(function () {
            Route::get('/', [GlobalAttributeController::class, 'index'])->name('index');
            route::post('/', [GlobalAttributeController::class, 'store'])->name('store');
            Route::get('/datatable', [GlobalAttributeController::class, 'getAttributes'])->name('datatable');
            Route::get('/search', [GlobalAttributeController::class, 'search'])->name('search');
            Route::get('/{id}/edit', [GlobalAttributeController::class, 'edit'])->name('edit');
            Route::post('/{id}', [GlobalAttributeController::class, 'update'])->name('update');
            Route::delete('/{id}', [GlobalAttributeController::class, 'destroy'])->name('delete');
        });

        // product conditions
        Route::prefix('product-conditions')->name('product_conditions.')->group(function () {
            Route::get('/', [ProductConditionController::class, 'index'])->name('index');
            Route::post('/', [ProductConditionController::class, 'store'])->name('store');
            Route::get('/datatable', [ProductConditionController::class, 'getProductConditions'])->name('datatable');
            Route::get('/search', [ProductConditionController::class, 'search'])->name('search');
            Route::get('/{id}/edit', [ProductConditionController::class, 'edit'])->name('edit');
            Route::post('/{id}', [ProductConditionController::class, 'update'])->name('update');
            Route::delete('/{id}', [ProductConditionController::class, 'destroy'])->name('delete');
        });

        route::prefix('attribute/values')->name('attributes.values.')->group(function () {
            Route::get('/datatable', [GlobalAttributeValueController::class, 'getAllAttributeValues'])->name('datatable');
            Route::post('/', [GlobalAttributeValueController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [GlobalAttributeValueController::class, 'edit'])->name('edit');
            Route::post('/{id}', [GlobalAttributeValueController::class, 'update'])->name('update');
            Route::delete('/{id}', [GlobalAttributeValueController::class, 'destroy'])->name('delete');
        });

        // product Faqs
        Route::prefix('product-faqs')->name('product_faqs.')->group(function () {
            Route::get('/', [ProductFaqController::class, 'index'])->name('index');
            Route::post('/', [ProductFaqController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [ProductFaqController::class, 'edit'])->name('edit');
            Route::post('/{id}', [ProductFaqController::class, 'update'])->name('update');
            Route::delete('/{id}', [ProductFaqController::class, 'destroy'])->name('delete');
            Route::get('/datatable', [ProductFaqController::class, 'getProductFaqs'])->name('datatable');
//            Route::get('/search', [ProductFaqController::class, 'search'])->name('search');
        });

        // wallet
        Route::prefix('wallet')->name('wallet.')->group(function () {
            Route::get('/', [WalletController::class, 'index'])->name('index');
            Route::get('/transactions', [WalletController::class, 'transactions'])->name('transactions');
            Route::get('/transactions/datatable', [WalletController::class, 'getTransactions'])->name('transactions.datatable');
        });

        // withdrawals
        Route::prefix('withdrawals')->name('withdrawals.')->group(function () {
            Route::get('/', [WithdrawalController::class, 'index'])->name('index');
            Route::post('/', [WithdrawalController::class, 'store'])->name('store');
            Route::get('/history', [WithdrawalController::class, 'history'])->name('history');
            Route::get('/datatable', [WithdrawalController::class, 'getWithdrawalRequests'])->name('datatable');
            Route::get('/history/datatable', [WithdrawalController::class, 'getWithdrawalHistory'])->name('history.datatable');
        });

        // orders
        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/', [OrderController::class, 'index'])->name('index');
            Route::get('/datatable', [OrderController::class, 'getOrders'])->name('datatable');
            Route::get('/{id}', [OrderController::class, 'show'])->name('show');
            Route::post('/{id}/{status}', [OrderController::class, 'updateStatus'])->name('update_status');
        });

        // Commission Settlement Routes
        Route::prefix('commissions')->name('commissions.')->group(function () {
            Route::get('/', [SellerEarningController::class, 'index'])->name('index');
            // Credits
            Route::get('/datatable', [SellerEarningController::class, 'getUnsettledCommissions'])->name('datatable');
            // Debits
            Route::get('/debits/datatable', [SellerEarningController::class, 'getUnsettledDebits'])->name('debits.datatable');
            // History
            Route::get('/history', [SellerEarningController::class, 'history'])->name('history');
            Route::get('/history/datatable', [SellerEarningController::class, 'getSettledCommissions'])->name('history.datatable');
        });

        // Notifications Routes
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/', [NotificationController::class, 'index'])->name('index');
            Route::get('/datatable', [NotificationController::class, 'getNotifications'])->name('datatable');
            Route::get('/{id}', [NotificationController::class, 'show'])->name('show');
            Route::post('/{id}/mark-read', [NotificationController::class, 'markAsRead'])->name('mark-read');
            Route::post('/{id}/mark-unread', [NotificationController::class, 'markAsUnread'])->name('mark-unread');
            Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
            Route::delete('/{id}', [NotificationController::class, 'destroy'])->name('destroy');
        });

        // Return requests listing / datatable
        Route::get('order-returns', [ReturnRequestController::class, 'index'])->name('returns.index');
        Route::get('order-returns/datatable', [ReturnRequestController::class, 'datatable'])->name('returns.datatable');

        // Approve / reject
        Route::post('order-returns/{id}/decision', [ReturnRequestController::class, 'decision'])->name('returns.decision');

    });
});
