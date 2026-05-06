<?php

namespace App\Notifications\DeliveryBoy;

use App\Broadcasting\FirebaseChannel;
use App\Enums\NotificationTypeEnum;
use App\Models\DeliveryBoyAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class DeliveryBoySettlementCreated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected DeliveryBoyAssignment $assignment)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail', FirebaseChannel::class];
    }

    public function toMail(object $notifiable): ?MailMessage
    {
        try {
            $a = $this->assignment;
            $amount = app(\App\Services\CurrencyService::class)->getSymbol() . number_format((float)($a->total_earnings ?? 0), 2);

            return (new MailMessage)
                ->subject('New Earning Added')
                ->greeting('Hello ' . ($notifiable->name ?? ''))
                ->line('A new earning entry has been added for your delivery assignment.')
                ->line('Order ID: ' . ($a->order_id ?? '-'))
                ->line('Amount: ' . $amount)
                ->line('We will notify you when it is paid to your wallet.');
        } catch (\Throwable $e) {
            Log::error('DeliveryBoySettlementCreated mail failed: ' . $e->getMessage());
            return null;
        }
    }

    public function toFirebase($notifiable): array
    {
        $a = $this->assignment;
        $amount = app(\App\Services\CurrencyService::class)->getSymbol() . number_format((float)($a->total_earnings ?? 0), 2);
        return [
            'title' => 'New Earning Added',
            'body'  => 'Amount ' . $amount . ' added for Order #' . ($a->order_id ?? '-') . '.',
            'image' => null,
            'data'  => [
                'type' => NotificationTypeEnum::SETTLEMENT_CREATE(),
                'delivery_boy_assignment_id' => $a->id,
                'order_id' => $a->order_id,
                'payment_status' => (string)($a->payment_status ?? ''),
            ],
        ];
    }

    public function toArray(object $notifiable): array
    {
        $a = $this->assignment;
        return [
            'delivery_boy_assignment_id' => $a->id,
            'amount' => (float)($a->total_earnings ?? 0),
            'payment_status' => (string)($a->payment_status ?? ''),
        ];
    }

    public function toDatabase(object $notifiable): array
    {
        $a = $this->assignment;
        $amount = app(\App\Services\CurrencyService::class)->getSymbol() . number_format((float)($a->total_earnings ?? 0), 2);
        return [
            'title' => 'New Earning Added',
            'message' => 'Earning amount ' . $amount . ' added for Order #' . ($a->order_id ?? '-') . '.',
            'type' => NotificationTypeEnum::SETTLEMENT_CREATE(),
            'sent_to' => 'delivery_boy',
            'user_id' => $notifiable->id ?? null,
            'order_id' => $a->order_id ?? null,
            'metadata' => [
                'delivery_boy_assignment_id' => $a->id,
                'total_earnings' => (float)($a->total_earnings ?? 0),
            ],
        ];
    }
}
