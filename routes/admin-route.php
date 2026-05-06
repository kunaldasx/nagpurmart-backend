<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\SystemUpdateController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DeliveryBoyCashCollectionController;
use App\Http\Controllers\Admin\DeliveryBoyEarningController;
use App\Http\Controllers\Admin\DeliveryBoyWithdrawalController;
use App\Http\Controllers\Admin\PromoController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\SellerWithdrawalController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DeliveryBoyController;
use App\Http\Controllers\DeliveryZoneController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\FeaturedSectionController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductFaqController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SellerController;
use App\Http\Controllers\SellerEarningController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\SystemUserController;
use App\Http\Controllers\TaxClassController;
use App\Http\Controllers\TaxRateController;
use App\Http\Controllers\Admin\WalletTransactionController;
use App\Http\Controllers\Admin\VendorTypeController;
use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\Admin\SubscriptionPlanController;
use App\Http\Controllers\Admin\SubscriptionFeatureController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware(['guest'])->group(function () {
        Route::get('/', [AuthController::class, 'loginAdmin'])->name('login');
        Route::get('login', [AuthController::class, 'loginAdmin'])->name('login');
        Route::post('login', [AuthController::class, 'login'])->name('login.post');

        // Password Reset Routes
        Route::get('forgot-password', [PasswordResetController::class, 'showForgotPasswordForm'])->name('password.request');
        Route::post('forgot-password', [PasswordResetController::class, 'sendResetLinkEmail'])->name('password.email');
        Route::get('reset-password/{token}', [PasswordResetController::class, 'showResetPasswordForm'])->name('password.reset');
        Route::post('reset-password', [PasswordResetController::class, 'resetPassword'])->name('password.update');
    });

    Route::middleware(['auth', 'validate.admin'
        , 'ensure.system.type'
        , 'ensure.subscription.feature'
    ])->group(function () {
        Route::get('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/', [DashboardController::class, 'index'])->name('index');
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('dashboard/chart-data', [DashboardController::class, 'getChartData'])->name('dashboard.chart-data');
        Route::get('dashboard/data', [DashboardController::class, 'getDashboardData'])->name('dashboard.data');
        Route::get('system-type', [VendorTypeController::class, 'index'])->name('system-type');
        Route::post('system-type', [VendorTypeController::class, 'store'])->name('system-type.store');

        Route::get('subscription-feature', [SubscriptionFeatureController::class, 'index'])->name('subscription-feature');
        Route::post('subscription-feature', [SubscriptionFeatureController::class, 'store'])->name('subscription-feature.store');

        // Admin <-> Seller impersonation (Single vendor only)
        Route::get('impersonate/seller', [ImpersonationController::class, 'toSeller'])->name('impersonate.to-seller');
        Route::get('impersonate/admin', [ImpersonationController::class, 'toAdmin'])->name('impersonate.to-admin');

        // profile
        Route::prefix('profile')->name('profile.')->group(function () {
            Route::get('/', [ProfileController::class, 'index'])->name('index');
            Route::get('/edit', [ProfileController::class, 'edit'])->name('edit');
            Route::post('/update', [ProfileController::class, 'update'])->name('update');
            Route::post('/password-update', [ProfileController::class, 'changePassword'])->name('password.update');
        });

        // settings
        Route::prefix('settings')->namespace('Settings')->name('settings.')->group(function () {
            Route::get('/', [SettingController::class, 'index'])->name('index');
            Route::get('{setting}', [SettingController::class, 'show'])->name('show');
            Route::post('store', [SettingController::class, 'store'])->name('store');
        });

        // system updates
        Route::prefix('system-updates')->name('system-updates.')->group(function () {
            Route::get('/', [SystemUpdateController::class, 'index'])->name('index');
            Route::post('/', [SystemUpdateController::class, 'store'])->name('store');
            Route::get('/datatable', [SystemUpdateController::class, 'datatable'])->name('datatable');
            // Live log endpoints
            Route::get('/latest', [SystemUpdateController::class, 'latest'])->name('latest');
            Route::get('/{update}/log', [SystemUpdateController::class, 'showLog'])->name('log');
        });

        // categories
        Route::prefix('categories')->namespace('Categories')->name('categories.')->group(function () {
            Route::get('/', [CategoryController::class, 'index'])->name('index');
            Route::get('/sort', [CategoryController::class, 'sort'])->name('sort');
            Route::post('/sort', [CategoryController::class, 'updateSort'])->name('sort.update');
            Route::post('/home-categories', [CategoryController::class, 'updateHomeCategories'])->name('home-categories.update');
            Route::post('/', [CategoryController::class, 'store'])->name('store');
            Route::post('/bulk-upload', [CategoryController::class, 'bulkUpload'])->name('bulk-upload');
            Route::get('/{id}/edit', [CategoryController::class, 'show'])->name('edit');
            Route::post('/{id}', [CategoryController::class, 'update'])->name('update');
            Route::delete('/{id}', [CategoryController::class, 'destroy'])->name('delete');
            Route::get('/datatable', [CategoryController::class, 'getCategories'])->name('datatable');
            Route::get('/search', [CategoryController::class, 'search'])->name('search')->name('search');
            // Bulk upload routes
            Route::get('/bulk-upload', [CategoryController::class, 'bulkUploadPage'])->name('bulk-upload.page');
            Route::get('/bulk-template', [CategoryController::class, 'downloadTemplate'])->name('bulk-template');
        });

        // brands
        Route::prefix('brands')->namespace('Brands')->name('brands.')->group(function () {
            Route::get('/', [BrandController::class, 'index'])->name('index');
            Route::post('/', [BrandController::class, 'store'])->name('store');
            Route::post('/bulk-upload', [BrandController::class, 'bulkUpload'])->name('bulk-upload');
            Route::get('/{id}/edit', [BrandController::class, 'show'])->name('edit');
            Route::post('/{id}', [BrandController::class, 'update'])->name('update');
            Route::delete('/{id}', [BrandController::class, 'destroy'])->name('delete');
            Route::get('/datatable', [BrandController::class, 'getBrands'])->name('datatable');
            Route::get('/search', [BrandController::class, 'search'])->name('search');
            // Bulk upload routes
            Route::get('/bulk-upload', [BrandController::class, 'bulkUploadPage'])->name('bulk-upload.page');
            Route::get('/bulk-template', [BrandController::class, 'downloadTemplate'])->name('bulk-template');
        });

        // promos
        Route::prefix('promos')->name('promos.')->group(function () {
            Route::get('/', [PromoController::class, 'index'])->name('index');
            Route::post('/', [PromoController::class, 'store'])->name('store');
            Route::get('/datatable', [PromoController::class, 'datatable'])->name('datatable');
            Route::get('/{id}', [PromoController::class, 'show'])->name('show');
            Route::put('/{id}', [PromoController::class, 'update'])->name('update');
            Route::delete('/{id}', [PromoController::class, 'destroy'])->name('destroy');
        });

        // customers (web panel users)
        Route::prefix('customers')->name('customers.')->group(function () {
            Route::get('/', [CustomerController::class, 'index'])->name('index');
            Route::get('/datatable', [CustomerController::class, 'datatable'])->name('datatable');
            Route::get('/export', [CustomerController::class, 'export'])->name('export');
        });

        // sellers
        Route::prefix('sellers')->name('sellers.')->group(function () {
            Route::get('/', [SellerController::class, 'index'])->name('index');
            Route::post('/', [SellerController::class, 'store'])->name('store');
            Route::get('/create', [SellerController::class, 'create'])->name('create');
            Route::get('/{id}/edit', [SellerController::class, 'edit'])->name('edit');
            Route::post('/{id}', [SellerController::class, 'update'])->name('update');
            Route::delete('/{id}', [SellerController::class, 'destroy'])->name('delete');
            Route::get('/datatable', [SellerController::class, 'getSellers'])->name('datatable');
            Route::get('/search', [SellerController::class, 'search'])->name('search')->name('search');
        });

        // subscription plans
        Route::prefix('subscription-plans')->name('subscription-plans.')->group(function () {
            Route::get('/', [SubscriptionPlanController::class, 'index'])->name('index');
            Route::post('/', [SubscriptionPlanController::class, 'store'])->name('store');
            Route::get('/create', [SubscriptionPlanController::class, 'create'])->name('create');
            Route::get('/datatable', [SubscriptionPlanController::class, 'datatable'])->name('datatable');
            // Subscribers list (sellers who purchased plans)
            Route::get('/subscribers', [SubscriptionPlanController::class, 'subscribers'])->name('subscribers');
            Route::get('/subscribers/datatable', [SubscriptionPlanController::class, 'subscribersDatatable'])->name('subscribers.datatable');
            Route::get('/subscribers/{id}', [SubscriptionPlanController::class, 'subscriberShow'])->name('subscribers.show');
            Route::get('view', function () {
                return view('admin.subscription-plans.view');
            });
            Route::get('/{id}/edit', [SubscriptionPlanController::class, 'edit'])->name('edit');
            Route::post('/{id}', [SubscriptionPlanController::class, 'update'])->name('update');
            Route::delete('/{id}', [SubscriptionPlanController::class, 'destroy'])->name('destroy');
        });

        // taxes
        Route::prefix('tax-rates')->namespace('TaxRates')->name('tax-rates.')->group(function () {
            Route::get('/', [TaxRateController::class, 'index'])->name('index');
            Route::post('/', [TaxRateController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [TaxRateController::class, 'show'])->name('edit');
            Route::post('/{id}', [TaxRateController::class, 'update'])->name('update');
            Route::delete('/{id}', [TaxRateController::class, 'destroy'])->name('delete');
            Route::get('/datatable', [TaxRateController::class, 'getTaxRates'])->name('datatable');
            Route::get('/search', [TaxRateController::class, 'search'])->name('search');
        });

        // tax classes
        Route::prefix('tax-classes')->namespace('TaxClasses')->name('tax-classes.')->group(function () {
            Route::get('/', [TaxClassController::class, 'index'])->name('index');
            Route::post('/', [TaxClassController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [TaxClassController::class, 'show'])->name('edit');
            Route::post('/{id}', [TaxClassController::class, 'update'])->name('update');
            Route::delete('/{id}', [TaxClassController::class, 'destroy'])->name('delete');
            Route::get('/get-tax-classes', [TaxClassController::class, 'getTaxClasses'])->name('datatable');
        });

        // Roles and Permissions
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
        Route::prefix('permissions')->namespace('Permissions')->name('permissions.')->group(function () {
            Route::post('/', [PermissionController::class, 'store'])->name('store');
        });

        // System Users
        Route::prefix('system-users')->namespace('systemUsers')->name('system-users.')->group(function () {
            Route::get('/', [SystemUserController::class, 'index'])->name('index');
            Route::post('/', [SystemUserController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [SystemUserController::class, 'show'])->name('show');
            Route::post('/{id}', [SystemUserController::class, 'update'])->name('update');
            Route::delete('/{id}', [SystemUserController::class, 'destroy'])->name('destroy');
            Route::get('/datatable', [SystemUserController::class, 'getSystemUsers'])->name('datatable');
        });

        // seller stores
        Route::prefix('sellers/store')->name('sellers.store.')->group(function () {
            Route::get('/', [StoreController::class, 'index'])->name('index');
            Route::get('/', [StoreController::class, 'index'])->name('index');
            Route::get('/datatable', [StoreController::class, 'getStores'])->name('datatable');
            Route::get('/search', [StoreController::class, 'search'])->name('search');
            Route::get('/view/{id}', [StoreController::class, 'index'])->name('show.index');
            Route::get('/{id}', [StoreController::class, 'show'])->name('show');
            Route::post('/{id}/verify', [StoreController::class, 'verify'])->name('verify');
        });

        // product Faqs
        Route::prefix('faqs')->name('faqs.')->group(function () {
            Route::get('/', [FaqController::class, 'index'])->name('index');
            Route::post('/', [FaqController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [FaqController::class, 'edit'])->name('edit');
            Route::post('/{id}', [FaqController::class, 'update'])->name('update');
            Route::delete('/{id}', [FaqController::class, 'destroy'])->name('delete');
            Route::get('/datatable', [FaqController::class, 'getFaqs'])->name('datatable');
        });

        // banners
        Route::prefix('banners')->name('banners.')->group(function () {
            Route::get('/', [BannerController::class, 'index'])->name('index');
            Route::post('/', [BannerController::class, 'store'])->name('store');
            Route::get('/create', [BannerController::class, 'create'])->name('create');
            Route::get('/{id}/edit', [BannerController::class, 'edit'])->name('edit');
            Route::post('/{id}', [BannerController::class, 'update'])->name('update');
            Route::delete('/{id}', [BannerController::class, 'destroy'])->name('delete');
            Route::get('/datatable', [BannerController::class, 'getBanners'])->name('datatable');
        });

        // wallet transactions and deposits
        Route::prefix('wallet')->name('wallet.')->group(function () {
            // All transactions
            Route::get('/transactions', [WalletTransactionController::class, 'transactions'])->name('transactions');
            Route::get('/transactions/datatable', [WalletTransactionController::class, 'transactionsDatatable'])->name('transactions.datatable');

            // Pending deposits
            Route::get('/deposits', [WalletTransactionController::class, 'deposits'])->name('deposits');
            Route::get('/deposits/datatable', [WalletTransactionController::class, 'depositsDatatable'])->name('deposits.datatable');
            Route::post('/deposits/{id}/process', [WalletTransactionController::class, 'processDeposit'])->name('deposits.process');
        });

        Route::get('products/search', [ProductController::class, 'search'])->name('products.search');

        // delivery zones
        Route::prefix('delivery-zones')->name('delivery-zones.')->group(function () {
            Route::get('/', [DeliveryZoneController::class, 'index'])->name('index');
            Route::post('/', [DeliveryZoneController::class, 'store'])->name('store');
            Route::get('/create', [DeliveryZoneController::class, 'create'])->name('create');
            Route::get('/{id}/edit', [DeliveryZoneController::class, 'edit'])->name('edit');
            Route::post('/{id}', [DeliveryZoneController::class, 'update'])->name('update');
            Route::delete('/{id}', [DeliveryZoneController::class, 'destroy'])->name('delete');
            Route::get('/datatable', [DeliveryZoneController::class, 'getDeliveryZones'])->name('datatable');
            Route::post('/check-exists', [DeliveryZoneController::class, 'checkExists'])->name('check_exists');
        });

        // Featured Sections Routes
        Route::prefix('featured-sections')->name('featured-sections.')->group(function () {
            Route::get('/', [FeaturedSectionController::class, 'index'])->name('index');
            Route::post('/', [FeaturedSectionController::class, 'store'])->name('store');
            Route::get('/datatable', [FeaturedSectionController::class, 'getFeaturedSections'])->name('datatable');
            // Sorting routes
            Route::get('/sort', [FeaturedSectionController::class, 'sort'])->name('sort');
            Route::post('/sort', [FeaturedSectionController::class, 'updateSort'])->name('updateSort');

            Route::get('/{id}', [FeaturedSectionController::class, 'show'])->name('show');
            Route::post('/{id}', [FeaturedSectionController::class, 'update'])->name('update');
            Route::delete('/{id}', [FeaturedSectionController::class, 'destroy'])->name('destroy');
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

        // Delivery Boys Routes
        Route::prefix('delivery-boys')->name('delivery-boys.')->group(function () {
            Route::get('/', [DeliveryBoyController::class, 'index'])->name('index');
            Route::get('/datatable', [DeliveryBoyController::class, 'getDeliveryBoys'])->name('datatable');
            Route::get('search', [DeliveryBoyController::class, 'search'])->name('search');
            Route::get('/{id}', [DeliveryBoyController::class, 'show'])->name('show');
            Route::post('/{id}/verification-status', [DeliveryBoyController::class, 'updateVerificationStatus'])->name('update-verification-status');
            Route::delete('/{id}', [DeliveryBoyController::class, 'destroy'])->name('destroy');
        });

        // Delivery Boy Earnings Routes
        Route::prefix('delivery-boy-earnings')->name('delivery-boy-earnings.')->group(function () {
            Route::get('/', [DeliveryBoyEarningController::class, 'index'])->name('index');
            Route::get('/datatable', [DeliveryBoyEarningController::class, 'getEarnings'])->name('datatable');
            Route::post('/{id}/process-payment', [DeliveryBoyEarningController::class, 'processPayment'])->name('process-payment');
            Route::get('/history', [DeliveryBoyEarningController::class, 'history'])->name('history');
            Route::get('/history/datatable', [DeliveryBoyEarningController::class, 'getPaymentHistory'])->name('history.datatable');
        });

        // Delivery Boy Cash Collection Routes
        Route::prefix('delivery-boy-cash-collections')->name('delivery-boy-cash-collections.')->group(function () {
            Route::get('/', [DeliveryBoyCashCollectionController::class, 'index'])->name('index');
            Route::get('/datatable', [DeliveryBoyCashCollectionController::class, 'getCashCollections'])->name('datatable');
            Route::post('/{id}/process-submission', [DeliveryBoyCashCollectionController::class, 'processCashSubmission'])->name('process-submission');
            Route::get('/history', [DeliveryBoyCashCollectionController::class, 'history'])->name('history');
            Route::get('/history/datatable', [DeliveryBoyCashCollectionController::class, 'getCashSubmissionHistory'])->name('history.datatable');
        });

        // Delivery Boy Withdrawal Routes
        Route::prefix('delivery-boy-withdrawals')->name('delivery-boy-withdrawals.')->group(function () {
            Route::get('/', [DeliveryBoyWithdrawalController::class, 'index'])->name('index');
            Route::get('/datatable', [DeliveryBoyWithdrawalController::class, 'getWithdrawalRequests'])->name('datatable');
            Route::post('/{id}/process', [DeliveryBoyWithdrawalController::class, 'processWithdrawalRequest'])->name('process');
            Route::get('/history', [DeliveryBoyWithdrawalController::class, 'history'])->name('history');
            Route::get('/history/datatable', [DeliveryBoyWithdrawalController::class, 'getWithdrawalHistory'])->name('history.datatable');
            Route::get('/{id}', [DeliveryBoyWithdrawalController::class, 'show'])->name('show');
        });

        // Seller Withdrawal Routes
        Route::prefix('seller-withdrawals')->name('seller-withdrawals.')->group(function () {
            Route::get('/', [SellerWithdrawalController::class, 'index'])->name('index');
            Route::get('/datatable', [SellerWithdrawalController::class, 'getWithdrawalRequests'])->name('datatable');
            Route::post('/{id}/process', [SellerWithdrawalController::class, 'processWithdrawalRequest'])->name('process');
            Route::get('/history', [SellerWithdrawalController::class, 'history'])->name('history');
            Route::get('/history/datatable', [SellerWithdrawalController::class, 'getWithdrawalHistory'])->name('history.datatable');
            Route::get('/{id}', [SellerWithdrawalController::class, 'show'])->name('show');
        });

        // Commission Settlement Routes
        Route::prefix('commissions')->name('commissions.')->group(function () {
            Route::get('/', [SellerEarningController::class, 'index'])->name('index');
            // Credits
            Route::get('/datatable', [SellerEarningController::class, 'getUnsettledCommissions'])->name('datatable');
            Route::post('/{id}/settle', [SellerEarningController::class, 'settleCommission'])->name('settle');
            Route::post('/settle-all', [SellerEarningController::class, 'settleAllCommissions'])->name('settle-all');
            // Debits
            Route::get('/debits/datatable', [SellerEarningController::class, 'getUnsettledDebits'])->name('debits.datatable');
            Route::post('/debits/{id}/settle', [SellerEarningController::class, 'settleDebit'])->name('debits.settle');
            Route::post('/debits/settle-all', [SellerEarningController::class, 'settleAllDebits'])->name('debits.settle-all');
            // History
            Route::get('/history', [SellerEarningController::class, 'history'])->name('history');
            Route::get('/history/datatable', [SellerEarningController::class, 'getSettledCommissions'])->name('history.datatable');
        });

        // orders
        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/', [OrderController::class, 'index'])->name('index');
            Route::get('/datatable', [OrderController::class, 'getOrders'])->name('datatable');
            Route::get('invoice', [OrderController::class, 'orderInvoice']);
            Route::get('/{id}', [OrderController::class, 'show'])->name('show');
            Route::post('/{id}/{status}', [OrderController::class, 'updateStatus'])->name('update_status');
        });

        // products
        Route::prefix('products')->name('products.')->group(function () {
            Route::get('/', [ProductController::class, 'index'])->name('index');
            Route::get('/datatable', [ProductController::class, 'getProducts'])->name('datatable');
            Route::get('/search', [ProductController::class, 'search'])->name('search');
            Route::get('/download-template', [ProductController::class, 'downloadTemplate'])->name('download-template');
            Route::get('/{id}/pricing', [ProductController::class, 'getProductPricing'])->name('pricing');
            Route::post('/{id}/verification-status', [ProductController::class, 'updateVerificationStatus'])->name('update-verification-status');
            Route::post('/{id}/update-status', [ProductController::class, 'updateStatus'])->name('update-status');
            Route::get('/{id}', [ProductController::class, 'show'])->name('show');
        });


        // product Faqs
        Route::prefix('product-faqs')->name('product_faqs.')->group(function () {
            Route::get('/', [ProductFaqController::class, 'index'])->name('index');
            Route::get('/datatable', [ProductFaqController::class, 'getProductFaqs'])->name('datatable');
//            Route::get('/search', [ProductFaqController::class, 'search'])->name('search');
        });
    });
});
