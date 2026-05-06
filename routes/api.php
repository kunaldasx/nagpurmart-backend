<?php

use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Api\BannerApiController;
use App\Http\Controllers\Api\BrandApiController;
use App\Http\Controllers\Api\CategoryApiController;
use App\Http\Controllers\Api\DeliveryZoneApiController;
use App\Http\Controllers\Api\FaqApiController;
use App\Http\Controllers\Api\FeaturedSectionApiController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\Product\ProductApiController;
use App\Http\Controllers\Api\Product\ProductFaqApiController;
use App\Http\Controllers\Api\Product\ProductSidebarApiController;
use App\Http\Controllers\Api\Seller\SubscriptionPlanApiController;
use App\Http\Controllers\Api\SellerFeedbackApiController;
use App\Http\Controllers\Api\SettingApiController;
use App\Http\Controllers\Api\StoreApiController;
use App\Http\Controllers\Api\User\AddressApiController;
use App\Http\Controllers\Api\User\AuthApiController;
use App\Http\Controllers\Api\User\CartApiController;
use App\Http\Controllers\Api\User\OrderApiController;
use App\Http\Controllers\Api\User\OtpApiController;
use App\Http\Controllers\Api\User\ProductReviewApiController;
use App\Http\Controllers\Api\User\PromoApiController;
use App\Http\Controllers\Api\User\UserApiController;
use App\Http\Controllers\Api\User\UserNotificationApiController;
use App\Http\Controllers\Api\User\WalletApiController;
use App\Http\Controllers\Api\User\WishlistApiController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\Payments\FlutterwaveController;
use App\Http\Controllers\Payments\PaystackController;
use App\Http\Controllers\Payments\RazorpayController;
use App\Http\Controllers\Payments\StripeController;
use Illuminate\Support\Facades\Route;

include_once("delivery-boy-api.php");
include_once("seller-api.php");

// User Auth Routes
Route::post('register', [AuthApiController::class, 'register'])->name('register');
Route::post('login', [AuthApiController::class, 'login'])->name('login');
Route::post('forget-password', [PasswordResetController::class, 'sendResetLinkEmail'])->name('password');
Route::post('verify-user', [AuthApiController::class, 'verifyUser']);
Route::post('auth/send-otp', [OtpApiController::class, 'sendOtp'])->name('send-otp');
Route::post('auth/verify-otp', [OtpApiController::class, 'verifyOtp'])->name('verify-otp');

Route::post('auth/google/callback', [AuthApiController::class, 'googleCallback'])->name('google-callback');
Route::post('auth/apple/callback', [AuthApiController::class, 'appleCallback'])->name('apple-callback');
Route::post('auth/phone/callback', [AuthApiController::class, 'phoneCallback'])->name('phone-callback');

// settings
Route::prefix('settings')->name('api.')->group(function () {
    Route::get('/', [SettingApiController::class, 'index'])->name('settings.index');
    Route::get('firebase-config', [SettingApiController::class, 'firebaseConfig'])->name('settings.firebase-config');
    Route::get('/variables', [SettingApiController::class, 'settingVariables'])->name('settings.variables');
    Route::get('/{setting}', [SettingApiController::class, 'show'])->name('settings.show');
});

// Subscription Plans (public)
Route::prefix('subscription')->group(function () {
    Route::get('/plans', [SubscriptionPlanApiController::class, 'index']);
});

Route::middleware('auth:sanctum')->group(function () {
    //logout
    Route::post('logout', [AuthApiController::class, 'logout']);

    // users routes
    Route::prefix('user')->name('user.')->group(function () {
        // delete user account
        Route::delete('/delete-account', [UserApiController::class, 'deleteAccount'])->name('delete-account');
        // change password
        Route::post('/change-password', [UserApiController::class, 'changePassword'])->name('change-password');

        // User profile
        Route::apiResource('/addresses', AddressApiController::class);

        // profile
        Route::get('/profile', [UserApiController::class, 'getProfile']);
        Route::post('/profile', [UserApiController::class, 'updateProfile']);

        // Notifications (app user)
        Route::prefix('notifications')->group(function () {
            Route::get('/', [UserNotificationApiController::class, 'index']);
            Route::get('/unread-count', [UserNotificationApiController::class, 'unreadCount']);
            Route::post('/mark-all-read', [UserNotificationApiController::class, 'markAllAsRead']);
            Route::post('/{id}/read', [UserNotificationApiController::class, 'markAsRead']);
            Route::post('/{id}/unread', [UserNotificationApiController::class, 'markAsUnread']);
        });

        // Wallet routes
        Route::prefix('wallet')->name('wallet.')->group(function () {
            Route::get('/', [WalletApiController::class, 'getWallet']);
            Route::post('/prepare-wallet-recharge', [WalletApiController::class, 'prepareWalletRecharge']);
            Route::post('/deduct-balance', [WalletApiController::class, 'deductBalance']);
            Route::get('/transactions', [WalletApiController::class, 'getTransactions']);
            Route::get('/transactions/{id}', [WalletApiController::class, 'getTransaction']);
        });

        // Wishlist routes
        Route::prefix('wishlists')->name('wishlists.')->group(function () {
            Route::get('/', [WishlistApiController::class, 'index']);
            Route::get('/titles', [WishlistApiController::class, 'getTitles']);
            Route::post('/', [WishlistApiController::class, 'store']); // Combined create wishlist and add item
            Route::post('/create', [WishlistApiController::class, 'createWishlist']); // Create wishlist only
            Route::get('/{id}', [WishlistApiController::class, 'show']);
            Route::put('/{id}', [WishlistApiController::class, 'update']);
            Route::delete('/{id}', [WishlistApiController::class, 'destroy']);

            // Wishlist items management
            Route::delete('/items/{itemId}', [WishlistApiController::class, 'removeItem']);
            Route::put('/items/{itemId}/move', [WishlistApiController::class, 'moveItem']);
        });

        // Cart routes
        Route::prefix('cart')->group(function () {
            Route::get('/', [CartApiController::class, 'getCart']);
            Route::post('/add', [CartApiController::class, 'addToCart']);
            Route::post('/item/{cartItemId}', [CartApiController::class, 'updateCartItemQuantity']);
            Route::delete('/item/{cartItemId}', [CartApiController::class, 'removeFromCart']);
            Route::get('/item/save-for-later', [CartApiController::class, 'getSaveForLaterItems']);
            Route::post('/item/save-for-later/{cartItemId}', [CartApiController::class, 'saveForLater']);
            Route::get('/clear-cart', [CartApiController::class, 'clearCart']);
            Route::post('/sync', [CartApiController::class, 'syncCart']);
        });

        // Promo routes
        Route::prefix('promos')->group(function () {
            Route::get('/available', [PromoApiController::class, 'getUserAvailablePromos']);
            Route::get('/validate', [PromoApiController::class, 'validatePromoCode']);
        });

        // Order routes
        Route::prefix('orders')->group(function () {
            Route::get('/', [OrderApiController::class, 'getUserOrders']);
            Route::post('/', [OrderApiController::class, 'createOrder']);
            Route::get('/{orderSlug}', [OrderApiController::class, 'getOrder']);
            Route::get('/{orderSlug}/delivery-boy-location', [OrderApiController::class, 'getOrderDeliveryBoyLocation']);
            Route::post('/items/{orderItemId}/cancel', [OrderApiController::class, 'cancelOrderItem']);
            Route::post('/items/{orderItemId}/return', [OrderApiController::class, 'returnOrderItem']);
            Route::post('/items/{orderItemId}/return-cancel', [OrderApiController::class, 'cancelReturnRequest']);
            // Reorder: add all items from a past order back to cart
            Route::post('/{orderId}/reorder', [OrderApiController::class, 'reorder']);
        });

        // Transaction routes
        Route::prefix('order-transactions')->name('transactions.')->group(function () {
            Route::get('/', [OrderApiController::class, 'getTransactions']);
            Route::get('/{id}', [OrderApiController::class, 'getTransaction']);
        });
    });
});

// reviews
Route::prefix('reviews')->group(function () {
    Route::get('/', [ProductReviewApiController::class, 'index']);
    Route::post('/', [ProductReviewApiController::class, 'store'])->middleware('auth:sanctum');
    Route::get('/available-for-review', [ProductReviewApiController::class, 'getAvailableProductsForReview'])->middleware('auth:sanctum');
    Route::get('/{id}', [ProductReviewApiController::class, 'show']);
    Route::post('/{id}', [ProductReviewApiController::class, 'update'])->middleware('auth:sanctum');
    Route::delete('/{id}', [ProductReviewApiController::class, 'destroy'])->middleware('auth:sanctum');
});

// seller feedback
Route::prefix('seller-feedback')->group(function () {
    Route::get('/', [SellerFeedbackApiController::class, 'index']);
    Route::post('/', [SellerFeedbackApiController::class, 'store'])->middleware('auth:sanctum');
    Route::get('/ratings', [SellerFeedbackApiController::class, 'getSellerRatings']);
    Route::get('/{id}', [SellerFeedbackApiController::class, 'show']);
    Route::post('/{id}', [SellerFeedbackApiController::class, 'update'])->middleware('auth:sanctum');
    Route::delete('/{id}', [SellerFeedbackApiController::class, 'destroy'])->middleware('auth:sanctum');
});

// get banners
Route::get('banners', [BannerApiController::class, 'index']);

// get categories
Route::get('categories', [CategoryApiController::class, 'index']);
Route::get('categories/sub-categories', [CategoryApiController::class, 'subCategories']);
Route::get('categories/sidebar', [CategoryApiController::class, 'getCategories']);

// get brands
Route::get('brands', [BrandApiController::class, 'index']);
Route::get('brands/sidebar', [BrandApiController::class, 'sidebarBrands']);

// products
Route::prefix('products')->name('products.')->group(function () {
//    Route::get('/', [ProductApiController::class, 'getAllProduct']);
    Route::get('/sidebar-filters', [ProductSidebarApiController::class, 'filters'])->name('products.sidebar-filters');
    Route::get('/get-types', [ProductSidebarApiController::class, 'getTypes'])->name('products.get-types');
    Route::get('/search-by-keywords', [ProductApiController::class, 'searchByKeywords']);
    Route::get('/store-wise', [ProductApiController::class, 'storeWise']);
    Route::get('/{slug}', [ProductApiController::class, 'show']);
    Route::get('/{slug}/faqs', [ProductFaqApiController::class, 'getByProduct']);
    Route::get('/{slug}/reviews', [ProductReviewApiController::class, 'getProductReviews']);
});

// stores
Route::prefix('stores')->name('stores.')->group(function () {
    Route::get('/', [StoreApiController::class, 'index']);
    Route::get('/{slug}', [StoreApiController::class, 'show']);
//    Route::get('/zone', [StoreApiController::class, 'getStoresByZone']);
});

// delivery zone check
Route::prefix('delivery-zone')->name('delivery_zone.')->group(function () {
    Route::get('/', [DeliveryZoneApiController::class, 'index']);
    Route::get('/check', [DeliveryZoneApiController::class, 'checkDelivery']);
    Route::get('/stores', [StoreApiController::class, 'getStoresByLocation']);
    Route::get('/products', [ProductApiController::class, 'index']);
    Route::get('/{id}', [DeliveryZoneApiController::class, 'show']);
});
Route::post('stores/map', [DeliveryZoneApiController::class, 'storesByMap']);

// get faqs
Route::prefix('faqs')->name('faqs.')->group(function () {
    Route::get('/', [FaqApiController::class, 'index']);
    Route::get('/{id}', [FaqApiController::class, 'show']);
});

// get product faqs
Route::prefix('product-faqs')->name('product_faqs.')->group(function () {
    Route::get('/', [ProductFaqApiController::class, 'index']);
    Route::get('/{id}', [ProductFaqApiController::class, 'show']);
});

// featured sections
Route::prefix('featured-sections')->name('featured-sections.')->group(function () {
    Route::get('/', [FeaturedSectionApiController::class, 'index'])->name('index');
    Route::get('/all', [FeaturedSectionApiController::class, 'all'])->name('all');
    Route::get('/types', [FeaturedSectionApiController::class, 'types'])->name('types');
    Route::get('/{slug}', [FeaturedSectionApiController::class, 'show'])->name('show');
    Route::get('/{slug}/products', [FeaturedSectionApiController::class, 'products'])->name('products');
});

Route::get('payment/variables', [PaymentController::class, 'paymentVariables']);

// razorpay routes
Route::prefix('razorpay')->group(function () {
    Route::post('create-order', [RazorpayController::class, 'createOrder'])->middleware('auth:sanctum');
//    Route::get('payment/{paymentId}', [RazorpayController::class, 'getPaymentDetails']);
});
Route::post('/webhook/razorpay', [RazorpayController::class, 'handleWebhook'])->name('razorpay.webhook');

// stripe routes
Route::post('stripe/create-order', [StripeController::class, 'createOrderPaymentIntent'])->middleware('auth:sanctum');
Route::post('stripe/webhook', [StripeController::class, 'handleWebhook'])->name('stripe.webhook');
Route::post('stripe/refund-payment', [StripeController::class, 'refundPayment']);

// paystack routes
Route::post('paystack/create-order', [PaystackController::class, 'createOrderPaymentIntent'])->middleware('auth:sanctum');
Route::post('paystack/webhook', [PaystackController::class, 'handleWebhook'])->name('paystack.webhook');
Route::get('paystack/callback', [PaystackController::class, 'handleCallback'])->name('paystack.callback');
Route::post('paystack/refund', [PaystackController::class, 'refundPayment']);


Route::post('flutterwave/webhook', [FlutterwaveController::class, 'handleWebhook'])->name('flutterwave.webhook');;

Route::post('/test-fcm', [NotificationController::class, 'test']);
Route::post('/test-fcms', [NotificationController::class, 'sendBulk']);
Route::post('test-email', [NotificationController::class, 'testEmail'])->name('test-email');
