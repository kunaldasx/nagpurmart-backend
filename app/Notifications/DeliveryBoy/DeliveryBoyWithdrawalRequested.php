<?php

namespace App\Notifications\DeliveryBoy;

use App\Broadcasting\FirebaseChannel;
use App\Enums\NotificationTypeEnum;
use App\Events\DeliveryBoy\WithdrawalRequestCreated;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class DeliveryBoyWithdrawalRequested extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected WithdrawalRequestCreated $event)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail', FirebaseChannel::class];
    }

    public function toMail(object $notifiable): ?MailMessage
    {
        try {
            $req = $this->event->withdrawalRequest;
            $amount = app(\App\Services\CurrencyService::class)->getSymbol() . number_format((float)$req->amount, 2);

            return (new MailMessage)
                ->subject('Withdrawal Request Submitted')
                ->greeting('Hello ' . ($notifiable->name ?? ''))
                ->line('Your withdrawal request has been submitted successfully.')
                ->line('Amount: ' . $amount)
                ->line('We will update you once it is processed.');
        } catch (\Throwable $e) {
            Log::error('DeliveryBoyWithdrawalRequested mail failed: ' . $e->getMessage());
            return null;
        }
    }

    public function toFirebase($notifiable): array
    {
        $req = $this->event->withdrawalRequest;
        $amount = app(\App\Services\CurrencyService::class)->getSymbol() . number_format((float)$req->amount, 2);
        return [
            'title' => 'Withdrawal Request Submitted',
            'body'  => 'Your withdrawal of ' . $amount . ' has been submitted.',
            'image' => null,
            'data'  => [
                'type' => NotificationTypeEnum::WITHDRAWAL_REQUEST(),
                'withdrawal_request_id' => $req->id,
                'status' => (string)$req->status,
            ],
        ];
    }

    public function toArray(object $notifiable): array
    {
        $req = $this->event->withdrawalRequest;
        return [
            'withdrawal_request_id' => $req->id,
            'amount' => (float)$req->amount,
            'status' => (string)$req->status,
        ];
    }

    public function toDatabase(object $notifiable): array
    {
        $req = $this->event->withdrawalRequest;
        $amount = app(\App\Services\CurrencyService::class)->getSymbol() . number_format((float)$req->amount, 2);
        return [
            'title' => 'Withdrawal Request Submitted',
            'message' => 'Your withdrawal request of ' . $amount . ' has been submitted.',
            'type' => NotificationTypeEnum::WITHDRAWAL_REQUEST(),
            'sent_to' => 'delivery_boy',
            'user_id' => $notifiable->id ?? null,
            'order_id' => null,
            'metadata' => [
                'withdrawal_request_id' => $req->id,
                'status' => (string)$req->status,
            ],
        ];
    }
}
