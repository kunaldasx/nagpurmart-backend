<?php

namespace Tests\Feature;

use App\Enums\GuardNameEnum;
use App\Models\Notification;
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

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $customer->id,
        ]);

        $this->assertDatabaseMissing('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $seller->id,
        ]);

        NotificationFacade::assertSentTo($customer, CustomerBroadcastNotification::class);
    }
}
