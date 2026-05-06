<?php

namespace App\Listeners\Order;

use App\Enums\NotificationTypeEnum;
use App\Enums\Order\OrderItemStatusEnum;
use App\Enums\Order\OrderStatusEnum;
use App\Events\Order\OrderStatusUpdated;
use App\Notifications\OrderStatusUpdated as StatusUpdateNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Str;

class OrderStatusUpdatedNotification
    implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(OrderStatusUpdated $event): void
    {
        // Get the customer user from the order
        $customer = $event->orderItem->order->user;

        $seller = $event->orderItem->store->seller->user ?? null;

        // Send notification to customer
        if ($customer) {
            $this->sendNotification(user: $customer, event: $event, sendTo: "customer");
        }

        // Send notification to the seller
        if ($seller) {
            if ($event->newStatus === OrderStatusEnum::ASSIGNED()) {
                foreach ($event->orderItem['sellerOrder'] ?? [] as $sellerOrder) {
                    $seller = $sellerOrder->seller->user;
                    $this->sendNotification(user: $seller, event: $event, sendTo: "seller");
                }
            } else {
                $this->sendNotification(user: $seller, event: $event, sendTo: "seller");
            }
        }
    }

    public function sendNotification($user, $event, $sendTo): void
    {
        $event->firebaseNotification = $this->firebaseNotification(event: $event, sendTo: $sendTo);
        $user->notify(new StatusUpdateNotification($event));
    }

    public function firebaseNotification($event, $sendTo): array
    {
        if ($event->newStatus === OrderItemStatusEnum::DELIVERED()) {
            if ($sendTo === "seller") {
                return [
                    'title' => 'Order Delivered: ' . $event->orderItem->title,
                    'body' => 'The order item "' . $event->orderItem->title . '" from order #' . $event->orderItem->order->id . ' has been delivered to the customer.',
                    'image' => $event->orderItem->product->main_image ?? null,
                    'data' => [
                        'order_slug' => $event->orderItem->order->slug,
                        'order_id' => $event->orderItem->order_id,
                        'status' => $event->orderItem->status,
                        'type' => NotificationTypeEnum::DELIVERY(),
                    ],
                ];

            }
            return [
                'title' => 'Order Delivered: ' . $event->orderItem->title,
                'body' => 'Your order item "' . $event->orderItem->title . '" has been successfully delivered. We hope you enjoy your purchase!',
                'image' => $event->orderItem->product->main_image ?? null,
                'data' => [
                    'order_slug' => $event->orderItem->order->slug,
                    'order_id' => $event->orderItem->order_id,
                    'status' => $event->orderItem->status,
                    'type' => NotificationTypeEnum::DELIVERY(),
                ],
            ];

        }

        if ($event->orderItem->order && (string)$event->orderItem->order->status === OrderStatusEnum::ASSIGNED()) {
            return [
                'title' => 'Delivery Partner Assigned',
                'body' => $sendTo === 'seller'
                    ? ('A delivery partner has been assigned for Order #' . ($event->orderItem->order->id ?? '-') . '.')
                    : ('A delivery partner has been assigned for your order #' . ($event->orderItem->order->id ?? '-') . '.'),
                'image' => $event->orderItem->product->main_image ?? null,
                'data' => [
                    'order_slug' => $event->orderItem->order->slug,
                    'order_id' => $event->orderItem->order_id,
                    'status' => $event->orderItem->order->status ?? '',
                    'type' => NotificationTypeEnum::ORDER_UPDATE(),
                ],
            ];
        }
        $order = $event->orderItem->order;
        $status =  ucfirst(Str::replace('_', ' ', $order->status ?? ''));

        // Default order-level update
        return [
            'title' => 'Order Status Updated',
            'body' => $sendTo === 'seller'
                ? ('Order #' . ($order->id ?? '-') . ' is now ' . ucfirst(str_replace('_', ' ', $status)) . '.')
                : ('Your order #' . ($order->id ?? '-') . ' is now ' . ucfirst(str_replace('_', ' ', $status)) . '.'),
            'image' => $event->orderItem->product->main_image ?? null,
            'data' => [
                'order_slug' => $order->slug ?? null,
                'order_id' => $order->id ?? null,
                'status' => $status,
                'type' => NotificationTypeEnum::ORDER_UPDATE(),
            ],
        ];
    }
}
