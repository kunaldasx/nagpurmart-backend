<?php

namespace App\Notifications\DeliveryBoy;

use App\Broadcasting\FirebaseChannel;
use App\Enums\DefaultSystemRolesEnum;
use App\Enums\NotificationTypeEnum;
use App\Events\DeliveryBoy\WithdrawalRequestProcessed;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class DeliveryBoyWithdrawalStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected WithdrawalRequestProcessed $event)
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

            $isAdmin = method_exists($notifiable, 'hasRole')
                && $notifiable->hasRole(DefaultSystemRolesEnum::SUPER_ADMIN());

            $mail = (new MailMessage)
                ->subject($isAdmin ? 'Delivery Boy Withdrawal Status Updated' : 'Your Withdrawal Status Updated')
                ->greeting('Hello ' . ($notifiable->name ?? ''))
                ->line($isAdmin ? 'A delivery boy withdrawal request status has been updated.' : 'Your withdrawal request status has been updated.')
                ->line('Amount: ' . $amount)
                ->line('Previous Status: ' . ucfirst((string)$this->event->previousStatus))
                ->line('New Status: ' . ucfirst((string)$req->status))
                ->line('Processed At: ' . optional($req->processed_at)->toDateTimeString());

            return $mail;
        } catch (\Throwable $e) {
            Log::error('DeliveryBoyWithdrawalStatusUpdated mail failed: ' . $e->getMessage());
            return null;
        }
    }

    public function toFirebase($notifiable): array
    {
        $req = $this->event->withdrawalRequest;
        $amount = app(\App\Services\CurrencyService::class)->getSymbol() . number_format((float)$req->amount, 2);
        $isAdmin = method_exists($notifiable, 'hasRole')
            && $notifiable->hasRole(DefaultSystemRolesEnum::SUPER_ADMIN());

        return [
            'title' => $isAdmin ? 'Delivery Boy Withdrawal ' . ucfirst((string)$req->status) : 'Withdrawal ' . ucfirst((string)$req->status),
            'body'  => $isAdmin
                ? ('Withdrawal of ' . $amount . ' is now ' . $req->status . '.')
                : ('Your withdrawal of ' . $amount . ' is now ' . $req->status . '.'),
            'image' => null,
            'data'  => [
                'type' => NotificationTypeEnum::WITHDRAWAL_PROCESS(),
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
            'previous_status' => (string)$this->event->previousStatus,
            'new_status' => (string)$req->status,
        ];
    }

    public function toDatabase(object $notifiable): array
    {
        $req = $this->event->withdrawalRequest;
        $amount = app(\App\Services\CurrencyService::class)->getSymbol() . number_format((float)$req->amount, 2);
        $isAdmin = method_exists($notifiable, 'hasRole')
            && $notifiable->hasRole(DefaultSystemRolesEnum::SUPER_ADMIN());
        return [
            'title' => $isAdmin ? 'Delivery Boy Withdrawal Status Updated' : 'Withdrawal Status Updated',
            'message' => $isAdmin
                ? ('Withdrawal of ' . $amount . ' updated from ' . ucfirst((string)$this->event->previousStatus) . ' to ' . ucfirst((string)$req->status) . '.')
                : ('Your withdrawal of ' . $amount . ' updated from ' . ucfirst((string)$this->event->previousStatus) . ' to ' . ucfirst((string)$req->status) . '.'),
            'type' => NotificationTypeEnum::WITHDRAWAL_PROCESS(),
            'sent_to' => $isAdmin ? 'admin' : 'delivery_boy',
            'user_id' => $notifiable->id ?? null,
            'order_id' => null,
            'metadata' => [
                'withdrawal_request_id' => $req->id,
                'previous_status' => (string)$this->event->previousStatus,
                'new_status' => (string)$req->status,
            ],
        ];
    }
}
