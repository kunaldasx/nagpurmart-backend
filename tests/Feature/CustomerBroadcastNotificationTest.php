<?php

namespace Tests\Feature;

use App\Enums\GuardNameEnum;
use App\Models\DeliveryBoy;
use App\Models\User;
use App\Notifications\CustomerBroadcastNotification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Tests\TestCase;

class CustomerBroadcastNotificationTest extends TestCase
{
    public function test_admin_can_broadcast_customer_notification_to_all_customer_users(): void
    {
        NotificationFacade::fake();

        $customer = User::create([
            'name' => 'Customer One',
            'email' => 'customer1@example.com',
            'password' => bcrypt('password123'),
            'status' => true,
            'access_panel' => GuardNameEnum::WEB(),
        ]);

        $seller = User::create([
            'name' => 'Seller One',
            'email' => 'seller1@example.com',
            'password' => bcrypt('password123'),
            'status' => true,
            'access_panel' => GuardNameEnum::SELLER(),
        ]);
        $rider = User::create([
            'name' => 'Rider One',
            'email' => 'rider1@example.com',
            'password' => bcrypt('password123'),
            'status' => true,
            'access_panel' => GuardNameEnum::WEB(),
        ]);
        DeliveryBoy::create([
            'user_id' => $rider->id,
            'status' => 'active',
        ]);

        $service = app(NotificationService::class);

        $broadcast = $service->createCustomerBroadcastNotification([
            'title' => 'Fresh deals are here',
            'description' => 'Enjoy a special weekend offer on your favourite items.',
            'image_url' => 'https://example.com/banner.png',
            'action_url' => 'https://example.com/deals',
            'created_by' => 1,
        ]);

        $service->sendCustomerBroadcastNotification($broadcast);

        $this->assertDatabaseHas('customer_broadcast_notifications', [
            'id' => $broadcast->id,
            'title' => 'Fresh deals are here',
            'status' => 'sent',
        ]);

        NotificationFacade::assertSentTo($customer, CustomerBroadcastNotification::class);
        NotificationFacade::assertNotSentTo($seller, CustomerBroadcastNotification::class);
        NotificationFacade::assertNotSentTo($rider, CustomerBroadcastNotification::class);
    }

    public function test_campaign_can_target_the_seller_app_without_notifying_customers(): void
    {
        NotificationFacade::fake();

        $customer = User::create([
            'name' => 'Customer Two',
            'email' => 'customer2@example.com',
            'password' => bcrypt('password123'),
            'status' => true,
            'access_panel' => GuardNameEnum::WEB(),
        ]);
        $seller = User::create([
            'name' => 'Seller Two',
            'email' => 'seller2@example.com',
            'password' => bcrypt('password123'),
            'status' => true,
            'access_panel' => GuardNameEnum::SELLER(),
        ]);

        $service = app(NotificationService::class);
        $broadcast = $service->createCustomerBroadcastNotification([
            'title' => 'Seller update',
            'description' => 'A seller app announcement.',
            'target_apps' => ['seller'],
        ]);

        $service->sendCustomerBroadcastNotification($broadcast);

        NotificationFacade::assertSentTo(
            $seller,
            CustomerBroadcastNotification::class,
            fn (CustomerBroadcastNotification $notification) => $notification->toDatabase($seller)['sent_to'] === 'seller'
        );
        NotificationFacade::assertNotSentTo($customer, CustomerBroadcastNotification::class);
    }

    public function test_campaign_can_target_riders_without_notifying_customers(): void
    {
        NotificationFacade::fake();

        $customer = User::create([
            'name' => 'Customer Three',
            'email' => 'customer3@example.com',
            'password' => bcrypt('password123'),
            'status' => true,
            'access_panel' => GuardNameEnum::WEB(),
        ]);
        $rider = User::create([
            'name' => 'Rider Two',
            'email' => 'rider2@example.com',
            'password' => bcrypt('password123'),
            'status' => true,
            'access_panel' => GuardNameEnum::WEB(),
        ]);
        DeliveryBoy::create([
            'user_id' => $rider->id,
            'status' => 'active',
        ]);

        $service = app(NotificationService::class);
        $broadcast = $service->createCustomerBroadcastNotification([
            'title' => 'Rider update',
            'description' => 'A rider app announcement.',
            'target_apps' => ['rider'],
        ]);

        $service->sendCustomerBroadcastNotification($broadcast);

        NotificationFacade::assertSentTo(
            $rider,
            CustomerBroadcastNotification::class,
            fn (CustomerBroadcastNotification $notification) => $notification->toDatabase($rider)['sent_to'] === 'rider'
                && $notification->toFirebase($rider)['data']['template'] === 'app_broadcast'
        );
        NotificationFacade::assertNotSentTo($customer, CustomerBroadcastNotification::class);
    }
}
