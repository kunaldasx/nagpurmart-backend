<?php

namespace App\Providers;

use App\Events\Auth\UserLoggedIn;
use App\Events\Auth\UserRegistered;
use App\Events\Cart\CartUpdatedByLocation;
use App\Events\Cart\ItemAddedToCart;
use App\Events\Cart\ItemRemovedFromCart;
use App\Events\DeliveryBoy\DeliveryBoyStatusUpdatedEvent;
use App\Events\DeliveryBoy\WithdrawalRequestCreated as DeliveryBoyWithdrawalRequestCreated;
use App\Events\DeliveryBoy\WithdrawalRequestProcessed as DeliveryBoyWithdrawalRequestProcessed;
use App\Events\Order\OrderPlaced;
use App\Events\Order\OrderStatusUpdated;
use App\Events\Product\ProductAfterCreate;
use App\Events\Product\ProductAfterUpdate;
use App\Events\Product\ProductStatusAfterUpdate;
use App\Events\Seller\WithdrawalRequestCreated;
use App\Events\Seller\WithdrawalRequestProcessed;
use App\Listeners\seller\SellerWithdrawalRequestedNotification;
use App\Listeners\seller\SellerWithdrawalStatusUpdatedNotification;
use App\Listeners\Auth\SendLoggedInNotification;
use App\Listeners\Auth\SendWelcomeNotification;
use App\Listeners\Cart\LogCartActivity;
use App\Listeners\DeliveryBoy\StoreDeliveryBoyLocation;
use App\Listeners\DeliveryBoy\DeliveryBoyWithdrawalRequestedNotification;
use App\Listeners\DeliveryBoy\DeliveryBoyWithdrawalStatusUpdatedNotification;
use App\Listeners\Order\NewOrderNotification;
use App\Listeners\Order\OrderStatusUpdatedNotification;
use App\Listeners\Order\UpdateStockOnOrderStatusChange;
use App\Listeners\Product\ProductCreatedNotification;
use App\Listeners\Product\ProductUpdatedNotification;
use App\Listeners\Product\ProductStatusUpdatedNotification;
use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */

    protected array $listen = [
        UserRegistered::class => [
            SendWelcomeNotification::class,
        ],
        UserLoggedIn::class => [
            SendLoggedInNotification::class,
        ],
        // Cart Events
        ItemAddedToCart::class => [
            LogCartActivity::class . '@handleItemAdded',
        ],

        ItemRemovedFromCart::class => [
            LogCartActivity::class . '@handleItemRemoved',
        ],

        CartUpdatedByLocation::class => [
            LogCartActivity::class . '@handleCartUpdatedByLocation',
        ],

        // Order Events
        OrderStatusUpdated::class => [
            OrderStatusUpdatedNotification::class,
            UpdateStockOnOrderStatusChange::class,
        ],

        OrderPlaced::class => [
            NewOrderNotification::class
        ],

        // DeliveryBoy Events
        DeliveryBoyStatusUpdatedEvent::class => [
            StoreDeliveryBoyLocation::class,
        ],

        // Delivery Boy Withdrawal Events
        DeliveryBoyWithdrawalRequestCreated::class => [
            DeliveryBoyWithdrawalRequestedNotification::class,
        ],
        DeliveryBoyWithdrawalRequestProcessed::class => [
            DeliveryBoyWithdrawalStatusUpdatedNotification::class,
        ],

        // Product Events
        ProductAfterCreate::class => [
            ProductCreatedNotification::class,
        ],

        ProductAfterUpdate::class => [
            ProductUpdatedNotification::class,
        ],

        ProductStatusAfterUpdate::class => [
            ProductStatusUpdatedNotification::class,
        ],

        // Seller Withdrawal Events
        WithdrawalRequestCreated::class => [
            SellerWithdrawalRequestedNotification::class,
        ],
        WithdrawalRequestProcessed::class => [
            SellerWithdrawalStatusUpdatedNotification::class,
        ],
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }

}
