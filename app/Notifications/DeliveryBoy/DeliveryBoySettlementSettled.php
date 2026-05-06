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

class DeliveryBoySettlementSettled extends Notification implements ShouldQueue
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
                ->subject('Earning Settled')
                ->greeting('Hello ' . ($notifiable->name ?? ''))
                ->line('Your delivery earning has been settled to your wallet.')
                ->line('Order ID: ' . ($a->order_id ?? '-'))
                ->line('Amount: ' . $amount)
                ->line('Paid At: ' . optional($a->paid_at)->toDateTimeString());
        } catch (\Throwable $e) {
            Log::error('DeliveryBoySettlementSettled mail failed: ' . $e->getMessage());
            return null;
        }
    }

    public function toFirebase($notifiable): array
    {
        $a = $this->assignment;
        $amount = app(\App\Services\CurrencyService::class)->getSymbol() . number_format((float)($a->total_earnings ?? 0), 2);
        return [
            'title' => 'Earning Settled',
            'body'  => 'Amount ' . $amount . ' settled for Order #' . ($a->order_id ?? '-') . '.',
            'image' => null,
            'data'  => [
                'type' => NotificationTypeEnum::SETTLEMENT_PROCESS(),
                'delivery_boy_assignment_id' => $a->id,
                'order_id' => $a->order_id,
                'paid_at' => optional($a->paid_at)->toDateTimeString(),
            ],
        ];
    }

    public function toArray(object $notifiable): array
    {
        $a = $this->assignment;
        return [
            'delivery_boy_assignment_id' => $a->id,
            'amount' => (float)($a->total_earnings ?? 0),
            'paid_at' => optional($a->paid_at)->toDateTimeString(),
        ];
    }

    public function toDatabase(object $notifiable): array
    {
        $a = $this->assignment;
        $amount = app(\App\Services\CurrencyService::class)->getSymbol() . number_format((float)($a->total_earnings ?? 0), 2);
        return [
            'title' => 'Earning Settled',
            'message' => 'Your earning of ' . $amount . ' has been settled for Order #' . ($a->order_id ?? '-') . '.',
            'type' => NotificationTypeEnum::SETTLEMENT_PROCESS(),
            'sent_to' => 'delivery_boy',
            'user_id' => $notifiable->id ?? null,
            'order_id' => $a->order_id ?? null,
            'metadata' => [
                'delivery_boy_assignment_id' => $a->id,
                'paid_at' => optional($a->paid_at)->toDateTimeString(),
            ],
        ];
    }
}
