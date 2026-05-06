<?php

namespace App\Notifications;

use App\Broadcasting\FirebaseChannel;
use App\Enums\NotificationTypeEnum;
use App\Models\SellerOrderItem;
use App\Models\OrderItemReturn;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class OrderItemReturnUpdated extends Notification implements ShouldQueue
{
    use Queueable;


    /**
     * @param array{return_status?: array{old:mixed,new:mixed}, pickup_status?: array{old:mixed,new:mixed}} $changes
     */
    public function __construct(protected OrderItemReturn $orderReturn, protected array $changes = [], protected string $audience = 'customer')
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail', FirebaseChannel::class];
    }

    public function toMail(object $notifiable): ?MailMessage
    {
        try {
            $r = $this->orderReturn;
            $subject = match ($this->audience) {
                'admin' => 'Return Request Updated',
                'seller' => 'Return Request Status Updated',
                default => 'Your Return Request Update',
            };

            $mail = (new MailMessage)
                ->subject($subject)
                ->greeting('Hello ' . ($notifiable->name ?? ''))
                ->line('Order #' . $r->order_id . ' | Item #' . $r->order_item_id);

            if (isset($this->changes['return_status'])) {
                $mail->line('Return Status: ' . str_replace('_', ' ', ($this->changes['return_status']['old'] ?? '-')) . ' → ' . str_replace('_', ' ', ($this->changes['return_status']['new'] ?? '-')));
            }
            if (isset($this->changes['pickup_status'])) {
                $mail->line('Pickup Status: ' . ($this->changes['pickup_status']['old'] ?? '-') . ' → ' . ($this->changes['pickup_status']['new'] ?? '-'));
            }

            if ($this->audience === 'customer') {
                $mail->line('We will keep you updated on further progress.');
            } elseif ($this->audience === 'seller') {
                $mail->line('Please take the next appropriate action if required.');
            } else {
                $mail->line('This is an automated update for monitoring purposes.');
            }
            return $mail;
        } catch (\Throwable $e) {
            Log::error('OrderItemReturnUpdated mail failed: ' . $e->getMessage());
            return null;
        }
    }

    public function toFirebase($notifiable): array
    {
        $r = $this->orderReturn;
        $status = str_replace('_', ' ', $this->changes['return_status']['new'] ?? (string)$r->return_status);
        $sellerOrderId = null;
        try {
            $sellerOrderId = SellerOrderItem::where('order_item_id', $r->order_item_id)->value('seller_order_id');
        } catch (\Throwable $e) {
            // no-op
        }
        return [
            'title' => match ($this->audience) {
                'admin' => 'Return Updated',
                'seller' => 'Return Status Updated',
                default => 'Return Update',
            },
            'body' => 'Order #' . $r->order_id . ' | Item #' . $r->order_item_id . ' | Status: ' . $status,
            'image' => null,
            'data' => [
                'type' => NotificationTypeEnum::ORDER(),
                'order_id' => $r->order_id,
                'order_item_id' => $r->order_item_id,
                'seller_order_id' => $sellerOrderId,
                'order_item_return_id' => $r->id,
                'audience' => $this->audience,
                'return_status' => (string)$r->return_status,
                'pickup_status' => (string)$r->pickup_status,
            ],
        ];
    }

    public function toArray(object $notifiable): array
    {
        $r = $this->orderReturn;
        return [
            'order_id' => $r->order_id,
            'order_item_id' => $r->order_item_id,
            'order_item_return_id' => $r->id,
            'changes' => $this->changes,
        ];
    }

    public function toDatabase(object $notifiable): array
    {
        $r = $this->orderReturn;
        // Resolve seller_order_id using order_item_id
        $sellerOrderId = null;
        try {
            $sellerOrderId = SellerOrderItem::where('order_item_id', $r->order_item_id)->value('seller_order_id');
        } catch (\Throwable $e) {
            // no-op
        }
        $statusOld = str_replace('_', ' ', $this->changes['return_status']['old'] ?? null);
        $statusNew = str_replace('_', ' ', $this->changes['return_status']['new'] ?? (string)$r->return_status);
        $title = match ($this->audience) {
            'admin' => 'Return Request Updated',
            'seller' => 'Return Request Status Updated',
            default => 'Return Request Update',
        };
        $message = 'Order #' . $r->order_id . ' | Item #' . $r->order_item_id . ' | ';
        if ($statusOld !== null) {
            $message .= 'Status: ' . $statusOld . ' → ' . $statusNew;
        } else {
            $message .= 'Status: ' . $statusNew;
        }

        return [
            'title' => $title,
            'message' => $message,
            'type' => NotificationTypeEnum::RETURN_ORDER_UPDATE(),
            'sent_to' => $this->audience === 'customer' ? 'customer' : ($this->audience === 'seller' ? 'seller' : 'admin'),
            'user_id' => $notifiable->id ?? null,
            'store_id' => $r->store_id ?? null,
            'order_id' => $r->order_id ?? null,
            'metadata' => [
                'order_item_return_id' => $r->id,
                'order_item_id' => $r->order_item_id,
                'order_id' => $r->order_id,
                'seller_order_id' => $sellerOrderId,
                'return_status' => (string)$r->return_status,
                'pickup_status' => (string)$r->pickup_status,
                'changes' => $this->changes,
            ],
        ];
    }
}
