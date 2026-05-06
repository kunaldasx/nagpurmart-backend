<?php

namespace App\Listeners\Order;

use App\Broadcasting\FirebaseChannel;
use App\Enums\NotificationTypeEnum;
use App\Events\Order\OrderPlaced;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class NewOrderNotification
    implements ShouldQueue

{

    /**
     * Handle the event.
     */
    public function handle(OrderPlaced $event): void
    {
        $customer = $event->order->user;
        if ($customer) {
            $this->sendNotification(user: $customer, event: $event, sendTo: "customer");
        }
        foreach ($event->order['sellerOrders'] ?? [] as $sellerOrder) {
            $seller = $sellerOrder->seller->user;
            $this->sendNotification(user: $seller, event: $event, sendTo: "seller");
        }
    }

    public function sendNotification($user, $event, $sendTo): void
    {
        // Build firebase payload to be used by the Notification
        $event->firebaseNotification = $this->firebaseNotification(event: $event, sendTo: $sendTo);

        // If user instance is missing, skip safely
        if (!$user) {
            Log::warning('NewOrderNotification skipped: notifiable user missing', [
                'send_to' => $sendTo,
                'order_id' => $event->order->id ?? null,
            ]);
            return;
        }

        // Let the Notification decide channels via via() and handle queueing
        try {
            $user->notify(new \App\Notifications\NewOrderNotification($event));
        } catch (\Throwable $e) {
            Log::error('NewOrderNotification failed to dispatch notification', [
                'message' => $e->getMessage(),
                'send_to' => $sendTo,
                'user_id' => $user->id ?? null,
                'order_id' => $event->order->id ?? null,
            ]);
        }
    }

    public function firebaseNotification($event, $sendTo): array
    {

        if ($sendTo === "seller") {
            return [
                'title' => 'New Order Received 🎉',
                'body'  => 'You have received a new order (Order #' . $event->order->id . '). Please review and confirm it at your earliest convenience.',
                'image' => $event->order->items->first()->product->main_image ?? null,
                'data'  => [
                    'order_slug' => $event->order->slug,
                    'order_id'   => $event->order->id,
                    'status'     => $event->order->status,
                    'type'       => NotificationTypeEnum::ORDER(),
                ],
            ];
        }
        return [
            'title' => 'Order Placed Successfully 🎉',
            'body'  => 'Thank you for your order! Your order #' . $event->order->id . ' has been placed successfully. We’ll notify you once it’s confirmed by the seller.',
            'image' => $event->order->items->first()->product->main_image ?? null,
            'data'  => [
                'order_slug' => $event->order->slug,
                'order_id'   => $event->order->id,
                'status'     => $event->order->status,
                'type'       => NotificationTypeEnum::ORDER(),
            ],
        ];
    }
}
