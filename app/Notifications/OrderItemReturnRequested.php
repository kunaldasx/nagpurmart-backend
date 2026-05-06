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

class OrderItemReturnRequested extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected OrderItemReturn $orderReturn, protected string $audience = 'customer')
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail', FirebaseChannel::class];
    }

    public function toMail(object $notifiable): ?MailMessage
    {
        Log::info('OrderItemReturnRequested toMail ' . json_encode($notifiable));
        try {
            $r = $this->orderReturn;
            $subject = match ($this->audience) {
                'admin' => 'New Return Request Submitted',
                'seller' => 'New Return Request Received',
                default => 'Your Return Request Has Been Submitted',
            };

            $mail = (new MailMessage)
                ->subject($subject)
                ->greeting('Hello ' . ($notifiable->name ?? ''))
                ->line('Order #' . $r->order_id . ' | Item #' . $r->order_item_id)
                ->line('Reason: ' . ($r->reason ?: '-'))
                ->line('Refund Amount: ' . app(\App\Services\CurrencyService::class)->getSymbol() . number_format((float)$r->refund_amount, 2));

            if ($this->audience === 'customer') {
                $mail->line('We have received your return request and will keep you updated.');
            } elseif ($this->audience === 'seller') {
                $mail->line('A new return request has been initiated for one of your orders.');
            } else {
                $mail->line('A new return request has been initiated in the system.');
            }
            return $mail;
        } catch (\Throwable $e) {
            Log::error('OrderItemReturnRequested mail failed: ' . $e->getMessage());
            return null;
        }
    }

    public function toFirebase($notifiable): array
    {
        Log::info('OrderItemReturnRequested toFirebase ' . json_encode($notifiable));
        $r = $this->orderReturn;
        $sellerOrderId = null;
        try {
            $sellerOrderId = SellerOrderItem::where('order_item_id', $r->order_item_id)->value('seller_order_id');
        } catch (\Throwable $e) {
            // no-op
        }
        return [
            'title' => match ($this->audience) {
                'admin' => 'New Return Request',
                'seller' => 'Return Request Received',
                default => 'Return Request Submitted',
            },
            'body' => 'Order #' . $r->order_id . ' | Item #' . $r->order_item_id,
            'image' => null,
            'data' => [
                'type' => NotificationTypeEnum::ORDER(),
                'order_id' => $r->order_id,
                'order_item_id' => $r->order_item_id,
                'seller_order_id' => $sellerOrderId,
                'order_item_return_id' => $r->id,
                'audience' => $this->audience,
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
            'return_status' => (string)$r->return_status,
            'pickup_status' => (string)$r->pickup_status,
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
        return [
            'title' => match ($this->audience) {
                'admin' => 'New Return Request',
                'seller' => 'New Return Request Received',
                default => 'Return Request Submitted',
            },
            'message' => 'Order #' . $r->order_id . ' | Item #' . $r->order_item_id,
            'type' => NotificationTypeEnum::RETURN_ORDER(),
            'sent_to' => $this->audience === 'customer' ? 'customer' : ($this->audience === 'seller' ? 'seller' : 'admin'),
            'user_id' => $notifiable->id ?? null,
            'store_id' => $r->store_id ?? null,
            'order_id' => $r->order_id ?? null,
            'metadata' => [
                'order_item_return_id' => $r->id,
                'order_item_id' => $r->order_item_id,
                'order_id' => $r->order_id,
                'seller_order_id' => $sellerOrderId,
                'reason' => $r->reason,
                'refund_amount' => (float)$r->refund_amount,
                'return_status' => (string)$r->return_status,
                'pickup_status' => (string)$r->pickup_status,
            ],
        ];
    }
}
