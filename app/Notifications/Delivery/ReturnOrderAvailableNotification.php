<?php

namespace App\Notifications\Delivery;

use App\Broadcasting\FirebaseChannel;
use App\Enums\NotificationTypeEnum;
use App\Models\OrderItemReturn;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReturnOrderAvailableNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public OrderItemReturn $orderReturn)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail', FirebaseChannel::class];
    }

    public function toMail(object $notifiable): ?MailMessage
    {
        $or = $this->orderReturn;
        $order = $or->order;
        return (new MailMessage)
            ->subject('New Return Order Available')
            ->greeting('Hello ' . ($notifiable->name ?? ''))
            ->line('A new return pickup is available in your zone.')
            ->line('Order #' . ($order->id ?? '-'))
            ->line('Pickup: Customer address in ' . (($order->shipping_city ?? '') . ', ' . ($order->shipping_state ?? '')))
            ->line('Drop-off: Seller store.');
    }

    public function toFirebase($notifiable): array
    {
        $or = $this->orderReturn;
        $order = $or->order;
        // Use product image from the returned order item if available
        $image = optional($or->orderItem->product ?? null)->main_image ?? null;

        return [
            'title' => 'New Return Order Available',
            'body'  => 'A new return pickup is available in your zone.',
            'image' => $image,
            'data'  => [
                'order_id' => $order->id ?? null,
                'order_item_return_id' => $or->id ?? null,
                'zone_id' => $order->delivery_zone_id ?? null,
                'type' => NotificationTypeEnum::RETURN_ORDER_AVAILABLE(),
            ],
        ];
    }

    public function toDatabase(object $notifiable): array
    {
        $or = $this->orderReturn;
        $order = $or->order;
        return [
            'title' => 'New Return Order Available',
            'message' => 'A new return pickup is available in your zone.',
            'type' => NotificationTypeEnum::RETURN_ORDER_AVAILABLE(),
            'sent_to' => 'delivery_boy',
            'user_id' => $notifiable->id ?? null,
            'order_id' => $order->id ?? null,
            'order_item_return_id' => $or->id ?? null,
            'metadata' => [
                'order_id' => $order->id ?? null,
                'order_slug' => $order->slug ?? null,
                'order_item_return_id' => $or->id ?? null,
                'zone_id' => $order->delivery_zone_id ?? null,
                'shipping_latitude' => $order->shipping_latitude ?? null,
                'shipping_longitude' => $order->shipping_longitude ?? null,
                'store_id' => $or->store_id ?? null,
            ],
        ];
    }
}
