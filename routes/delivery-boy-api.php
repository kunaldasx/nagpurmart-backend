<?php

use App\Http\Controllers\Api\DeliveryBoy\DeliveryBoyAuthApiController;
use App\Http\Controllers\Api\DeliveryBoy\DeliveryBoyCashCollectionApiController;
use App\Http\Controllers\Api\DeliveryBoy\DeliveryBoyEarningApiController;
use App\Http\Controllers\Api\DeliveryBoy\DeliveryBoyHomeController;
use App\Http\Controllers\Api\DeliveryBoy\DeliveryBoyOrderApiController;
use App\Http\Controllers\Api\DeliveryBoy\DeliveryBoyReturnPickupApiController;
use App\Http\Controllers\Api\DeliveryBoy\DeliveryBoyWithdrawalApiController;
use App\Http\Controllers\Api\DeliveryFeedbackApiController;
use App\Http\Controllers\Api\DeliveryBoy\DeliveryBoyNotificationApiController;
use App\Http\Middleware\ActiveDeliveryBoy;
use App\Http\Middleware\VerifiedDeliveryBoy;
use Illuminate\Support\Facades\Route;


// Delivery Boy Auth Routes
Route::prefix('delivery-boy')->name('delivery-boy.')->group(function () {
    Route::post('register', [DeliveryBoyAuthApiController::class, 'register'])->name('register');
    Route::post('login', [DeliveryBoyAuthApiController::class, 'login'])->name('login');
});

Route::middleware('auth:sanctum')->prefix('delivery-boy')->group(function () {
// delivery boy routes (requires a verified delivery boy)
    Route::middleware('verified.delivery.boy')->group(function () {
        // Add delivery boy-specific routes here
        Route::get('profile', [DeliveryBoyAuthApiController::class, 'getProfile'])->name('profile');
        Route::post('profile', [DeliveryBoyAuthApiController::class, 'updateProfile'])->name('update-profile');
        Route::post('status/update', [DeliveryBoyAuthApiController::class, 'updateStatus'])->name('update-status');
        Route::post('update-current-location', [DeliveryBoyAuthApiController::class, 'updateCurrentLocation'])->name('update-location');
        Route::get('get-last-location', [DeliveryBoyAuthApiController::class, 'getLastLocation'])->name('get-last-location');
        Route::get('home', [DeliveryBoyHomeController::class, 'index'])->name('home');

        // Notifications (delivery boy)
        Route::prefix('notifications')->group(function () {
            Route::get('/', [DeliveryBoyNotificationApiController::class, 'index']);
            Route::get('/unread-count', [DeliveryBoyNotificationApiController::class, 'unreadCount']);
            Route::post('/mark-all-read', [DeliveryBoyNotificationApiController::class, 'markAllAsRead']);
            Route::post('/{id}/read', [DeliveryBoyNotificationApiController::class, 'markAsRead']);
            Route::post('/{id}/unread', [DeliveryBoyNotificationApiController::class, 'markAsUnread']);
        });

        // Order routes (requires an active delivery boy)
        Route::middleware('active.delivery.boy')->group(function () {
            Route::get('/orders/available', [DeliveryBoyOrderApiController::class, 'getAvailableOrders'])->name('orders.available');
            Route::post('/orders/{orderId}/accept', [DeliveryBoyOrderApiController::class, 'acceptOrder'])->name('orders.accept');
            Route::put('/order-items/{orderItemId}/status', [DeliveryBoyOrderApiController::class, 'updateOrderItemStatus'])->name('order-items.update-status');

            // Return pickup routes (similar structure to orders)
            Route::get('/return-pickups/available', [DeliveryBoyReturnPickupApiController::class, 'getAvailablePickups'])->name('return-pickups.available');
            Route::post('/return-pickups/{returnId}/accept', [DeliveryBoyReturnPickupApiController::class, 'acceptPickup'])->name('return-pickups.accept');
            Route::put('/return-pickups/{returnId}/status', [DeliveryBoyReturnPickupApiController::class, 'updatePickupStatus'])->name('return-pickups.update-status');
        });

        // My lists and details
        Route::get('/orders/my', [DeliveryBoyOrderApiController::class, 'getMyOrders'])->name('orders.my');
        Route::get('/orders/{orderId}', [DeliveryBoyOrderApiController::class, 'getOrderDetails'])->name('orders.details');
        Route::get('/return-pickups/my', [DeliveryBoyReturnPickupApiController::class, 'getMyPickups'])->name('return-pickups.my');
        Route::get('/return-pickups/{returnId}', [DeliveryBoyReturnPickupApiController::class, 'getPickupDetails'])->name('return-pickups.details');

        Route::middleware([VerifiedDeliveryBoy::class])->group(function () {
            // Withdrawal routes
            Route::post('/withdrawals', [DeliveryBoyWithdrawalApiController::class, 'createWithdrawalRequest'])->name('withdrawals.create');
            Route::get('/withdrawals', [DeliveryBoyWithdrawalApiController::class, 'getWithdrawalRequests'])->name('withdrawals.index');
            Route::get('/withdrawals/{id}', [DeliveryBoyWithdrawalApiController::class, 'getWithdrawalRequest'])->name('withdrawals.show');

            // Earnings routes
            Route::get('/earnings', [DeliveryBoyEarningApiController::class, 'getEarnings'])->name('earnings.index');
            Route::get('/earnings/statistics', [DeliveryBoyEarningApiController::class, 'getStatistics'])->name('earnings.statistics');
            Route::get('/earnings/date-range', [DeliveryBoyEarningApiController::class, 'getEarningsByDateRange'])->name('earnings.date-range');

            // Cash Collection routes
            Route::get('/cash-collections', [DeliveryBoyCashCollectionApiController::class, 'getCashCollections'])->name('cash-collections.index');
            Route::get('/cash-collections/statistics', [DeliveryBoyCashCollectionApiController::class, 'getStatistics'])->name('cash-collections.statistics');
            Route::get('/cash-collections/date-range', [DeliveryBoyCashCollectionApiController::class, 'getEarningsByDateRange'])->name('cash-collections.date-range');
        });

        Route::prefix('feedback')->group(function () {
            Route::get('/', [DeliveryFeedbackApiController::class, 'index']);
            Route::get('/ratings', [DeliveryFeedbackApiController::class, 'getDeliveryRatings']);
            Route::get('/{id}', [DeliveryFeedbackApiController::class, 'show']);
        });
    });
    Route::post('feedback/', [DeliveryFeedbackApiController::class, 'store']);
    Route::post('feedback/{id}', [DeliveryFeedbackApiController::class, 'update']);
    Route::delete('feedback/{id}', [DeliveryFeedbackApiController::class, 'destroy']);
});
