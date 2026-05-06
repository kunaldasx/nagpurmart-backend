<?php

namespace App\Notifications;

use App\Broadcasting\FirebaseChannel;
use App\Enums\NotificationTypeEnum;
use App\Enums\DefaultSystemRolesEnum;
use App\Models\SellerOrderItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrderStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    protected $event;

    /**
     * Create a new notification instance.
     */
    public function __construct($event)
    {
        $this->event = $event;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail', FirebaseChannel::class];
    }

    /**
     * Get the firebase representation of the notification.
     */
    public function toFirebase($notifiable)
    {
        if (!empty($this->event->firebaseNotification)) {
            return $this->event->firebaseNotification;
        }

        $order = $this->event->orderItem->order;
        $orderStatus = ucfirst(Str::replace('_', ' ', $order->status ?? ''));
        $isSeller = method_exists($notifiable, 'hasRole') && $notifiable->hasRole(DefaultSystemRolesEnum::SELLER());
        $sellerOrderId = null;
        try {
            $sellerOrderId = SellerOrderItem::where('order_item_id', $this->event->orderItem->id)->value('seller_order_id');
        } catch (\Throwable $e) {
            // no-op
        }
        return [
            'title' => 'Order Status Updated',
            'body' => $isSeller
                ? ('Order #' . ($order->id ?? '-') . ' is now ' . ucfirst(str_replace('_', ' ', $orderStatus)) . '.')
                : ('Your order #' . ($order->id ?? '-') . ' is now ' . ucfirst(str_replace('_', ' ', $orderStatus)) . '.'),
            'image' => $this->event->orderItem->product->main_image ?? null,
            'data' => [
                'order_slug' => $order->slug ?? null,
                'order_id' => $order->id ?? null,
                'status' => $orderStatus,
                'type' => NotificationTypeEnum::ORDER_UPDATE(),
                'seller_order_id' => $sellerOrderId,
            ],
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): ?MailMessage
    {
        try {
            // Prefer the message crafted by the listener (firebaseNotification payload)
            if (!empty($this->event->firebaseNotification) && is_array($this->event->firebaseNotification)) {
                $payload = $this->event->firebaseNotification;
                $order = $this->event->orderItem->order;

                $mail = (new MailMessage)
                    ->subject($payload['title'] ?? 'Order Status Updated')
                    ->greeting('Hello ' . ($notifiable->name ?? '') . '!')
                    ->line($payload['body'] ?? '');

                $orderId = $order->id ?? null;
                if ($orderId) {
                    $isSeller = method_exists($notifiable, 'hasRole') && $notifiable->hasRole(DefaultSystemRolesEnum::SELLER());
                    $url = $isSeller ? url('seller/orders/' . $orderId) : url('orders/' . $orderId);
                    $mail->action('View Order', $url);
                }

                return $mail;
            }

            // Fallback to previous behavior if listener didn't prepare payload
            $order = $this->event->orderItem->order;
            $orderStatus = ucfirst(Str::replace('_', ' ', $order->status ?? ''));
            $isSeller = method_exists($notifiable, 'hasRole') && $notifiable->hasRole(DefaultSystemRolesEnum::SELLER());

            $mail = (new MailMessage)
                ->subject('Order Status Updated')
                ->greeting('Hello ' . ($notifiable->name ?? '') . '!')
                ->line($isSeller
                    ? ('Order #' . ($order->id ?? '-') . ' status updated to ' . ucfirst(str_replace('_', ' ', $orderStatus)) . '.')
                    : ('Your order #' . ($order->id ?? '-') . ' status updated to ' . ucfirst(str_replace('_', ' ', $orderStatus)) . '.'));

            $orderId = $order->id ?? null;
            if ($orderId) {
                $url = $isSeller ? url('seller/orders/' . $orderId) : url('orders/' . $orderId);
                $mail->action('View Order', $url);
            }

            return $mail;
        } catch (\Throwable $e) {
            // Log error but don’t stop the process
            Log::error('Mail notification failed: ' . $e->getMessage(), [
                'notifiable_id' => $notifiable->id ?? null,
                'notification' => static::class,
            ]);

            // return null or a fake MailMessage to avoid exception bubbling
            return null;
        }
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $order = $this->event->orderItem->order;
        return [
            'order_id' => $order->id ?? null,
            'order_slug' => $order->slug ?? null,
            'status' => ucfirst(Str::replace('_', ' ', $order->status ?? '')),
        ];
    }

    public function toDatabase(object $notifiable): array
    {
        // If listener prepared a unified payload, adapt it to database structure
        if (!empty($this->event->firebaseNotification) && is_array($this->event->firebaseNotification)) {
            $p = $this->event->firebaseNotification;
            $data = $p['data'] ?? [];
            $order = $this->event->orderItem->order;
            $isSeller = method_exists($notifiable, 'hasRole') && $notifiable->hasRole(DefaultSystemRolesEnum::SELLER());
            // Attempt to resolve seller_order_id from order_item_id
            $sellerOrderId = null;
            try {
                $sellerOrderId = SellerOrderItem::where('order_item_id', $this->event->orderItem->id)->value('seller_order_id');
            } catch (\Throwable $e) {
                // no-op
            }

            return [
                'title' => $p['title'] ?? 'Order Status Updated',
                'message' => $p['body'] ?? '',
                'type' => $data['type'] ?? NotificationTypeEnum::ORDER_UPDATE(),
                'sent_to' => $isSeller ? 'seller' : 'customer',
                'user_id' => $notifiable->id ?? null,
                'store_id' => null,
                'order_id' => $data['order_id'] ?? ($order->id ?? null),
                'metadata' => array_merge([
                    'order_id' => $order->id ?? null,
                    'order_slug' => $order->slug ?? null,
                    'seller_order_id' => $sellerOrderId,
                ], $data),
            ];
        }

        // Fallback to previous behavior
        $order = $this->event->orderItem->order;
        $orderStatus = ucfirst(Str::replace('_', ' ', $order->status ?? ''));
        $isSeller = method_exists($notifiable, 'hasRole') && $notifiable->hasRole(DefaultSystemRolesEnum::SELLER());
        // Attempt to resolve seller_order_id from order_item_id
        $sellerOrderId = null;
        try {
            $sellerOrderId = SellerOrderItem::where('order_item_id', $this->event->orderItem->id)->value('seller_order_id');
        } catch (\Throwable $e) {
            // no-op
        }

        return [
            'title' => 'Order Status Updated',
            'message' => $isSeller
                ? ('Order #' . ($order->id ?? '-') . ' is now ' . ucfirst(str_replace('_', ' ', $orderStatus)) . '.')
                : ('Your order #' . ($order->id ?? '-') . ' is now ' . ucfirst(str_replace('_', ' ', $orderStatus)) . '.'),
            'type' => NotificationTypeEnum::ORDER_UPDATE(),
            'sent_to' => $isSeller ? 'seller' : 'customer',
            'user_id' => $notifiable->id ?? null,
            'store_id' => null,
            'order_id' => $order->id ?? null,
            'metadata' => [
                'order_id' => $order->id ?? null,
                'order_slug' => $order->slug ?? null,
                'status' => $orderStatus,
                'seller_order_id' => $sellerOrderId,
            ],
        ];
    }
}
