<?php

namespace App\Notifications;

use App\Broadcasting\FirebaseChannel;
use App\Enums\NotificationTypeEnum;
use App\Enums\DefaultSystemRolesEnum;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NewOrderNotification extends Notification implements ShouldQueue
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
        // Ensure Firebase push is sent even if mail channel fails (e.g., bad SMTP)
        // By sending via Firebase before Mail, push notifications are dispatched
        // prior to any potential mail transport exception.
        return ['database', FirebaseChannel::class, 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): ?MailMessage
    {
        try {
            $order = $this->event->order;
            $isSeller = method_exists($notifiable, 'hasRole') && $notifiable->hasRole(DefaultSystemRolesEnum::SELLER());

            $mail = (new MailMessage)
                ->subject($isSeller ? 'New Order Received' : 'Order Placed Successfully')
                ->greeting('Hello ' . ($notifiable->name ?? ''))
                ->line($isSeller
                    ? ('You have received a new order (Order #' . ($order->id ?? '-') . ').')
                    : ('Your order #' . ($order->id ?? '-') . ' has been placed successfully.'))
                ->line('Order Total: ' . app(\App\Services\CurrencyService::class)->getSymbol() . number_format((float)($order->final_total ?? 0), 2))
                ->line('Status: ' . ucfirst(Str::replace('_', ' ', $order->status ?? '')));

            return $mail;
        } catch (\Throwable $e) {
            Log::error('NewOrderNotification mail failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get the firebase representation of the notification.
     */
    public function toFirebase($notifiable)
    {
        // Prefer payload prepared by Listener, else fall back to a safe default
        if (!empty($this->event->firebaseNotification)) {
            return $this->event->firebaseNotification;
        }

        $order = $this->event->order;
        $isSeller = method_exists($notifiable, 'hasRole') && $notifiable->hasRole(DefaultSystemRolesEnum::SELLER());
        $sellerOrderId = null;
        try {
            $sellerOrderId = $order->sellerOrders->first()->id ?? null;
        } catch (\Throwable $e) {
            // no-op
        }

        return [
            'title' => $isSeller ? 'New Order Received' : 'Order Placed Successfully',
            'body' => $isSeller
                ? ('You have received a new order (Order #' . ($order->id ?? '-') . ').')
                : ('Your order #' . ($order->id ?? '-') . ' has been placed successfully.'),
            'image' => $order->items->first()->product->main_image ?? null,
            'data' => [
                'order_slug' => $order->slug ?? null,
                'order_id' => $order->id ?? null,
                'status' =>  ucfirst(Str::replace('_', ' ', $order->status ?? '')),
                'type' => NotificationTypeEnum::ORDER(),
                'seller_order_id' => $sellerOrderId,
            ],
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $order = $this->event->order;
        return [
            'order_id' => $order->id ?? null,
            'order_slug' => $order->slug ?? null,
            'status' =>  ucfirst(Str::replace('_', ' ', $order->status ?? '')),
            'total' => (float)($order->final_total ?? 0),
        ];
    }

    public function toDatabase(object $notifiable): array
    {
        $order = $this->event->order;
        $isSeller = method_exists($notifiable, 'hasRole') && $notifiable->hasRole(DefaultSystemRolesEnum::SELLER());
        // Safely determine first seller_order_id if available
        $sellerOrderId = null;
        try {
            $sellerOrderId = $order->sellerOrders->first()->id ?? null;
        } catch (\Throwable $e) {
            // no-op
        }

        return [
            'title' => $isSeller ? 'New Order Received' : 'Order Placed Successfully',
            'message' => $isSeller
                ? ('You have received a new order (Order #' . ($order->id ?? '-') . '). Please review and confirm it.')
                : ('Your order #' . ($order->id ?? '-') . ' has been placed successfully.'),
            'type' => NotificationTypeEnum::NEW_ORDER(),
            'sent_to' => $isSeller ? 'seller' : 'customer',
            'user_id' => $notifiable->id ?? null,
            'store_id' => $isSeller ? ($order->sellerOrders->first()->seller_id ?? null) : null,
            'order_id' => $order->id ?? null,
            'metadata' => [
                'order_id' => $order->id ?? null,
                'order_slug' => $order->slug ?? null,
                'status' =>  ucfirst(Str::replace('_', ' ', $order->status ?? '')),
                'total' => (float)($order->final_total ?? 0),
                'seller_order_id' => $sellerOrderId,
            ],
        ];
    }
}
