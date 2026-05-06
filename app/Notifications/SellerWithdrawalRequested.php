<?php

namespace App\Notifications;

use App\Broadcasting\FirebaseChannel;
use App\Enums\NotificationTypeEnum;
use App\Enums\DefaultSystemRolesEnum;
use App\Events\Seller\WithdrawalRequestCreated;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class SellerWithdrawalRequested extends Notification implements ShouldQueue
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
            $isAdmin = method_exists($notifiable, 'hasRole') && $notifiable->hasRole(DefaultSystemRolesEnum::SUPER_ADMIN());
            $sellerName = $req->seller->name ?? $req->seller->user->name ?? $req->seller_name ?? ('Seller #' . ($req->seller_id ?? ''));

            $mail = (new MailMessage)
                ->subject($isAdmin ? 'New Withdrawal Request' : 'Withdrawal Request Submitted')
                ->greeting('Hello ' . ($notifiable->name ?? ''));

            if ($isAdmin) {
                $mail->line('A seller has submitted a withdrawal request.')
                    ->line('Seller: ' . $sellerName)
                    ->line('Amount: ' . app(\App\Services\CurrencyService::class)->getSymbol() . number_format((float)$req->amount, 2))
                    ->line('Status: ' . ucfirst((string)$req->status))
                    ->line('Requested At: ' . optional($req->created_at)->toDateTimeString());
            } else {
                $mail->line('Your withdrawal request has been submitted successfully.')
                    ->line('Amount: ' . app(\App\Services\CurrencyService::class)->getSymbol() . number_format((float)$req->amount, 2))
                    ->line('Status: ' . ucfirst((string)$req->status))
                    ->line('Requested At: ' . optional($req->created_at)->toDateTimeString())
                    ->line('We will notify you once it is processed.');
            }
            return $mail;
        } catch (\Throwable $e) {
            Log::error('SellerWithdrawalRequested mail failed: ' . $e->getMessage());
            return null;
        }
    }

    public function toFirebase($notifiable): array
    {
        $req = $this->event->withdrawalRequest;
        $isAdmin = method_exists($notifiable, 'hasRole') && $notifiable->hasRole(DefaultSystemRolesEnum::SUPER_ADMIN());
        $sellerName = $req->seller->name ?? $req->seller->user->name ?? $req->seller_name ?? ('Seller #' . ($req->seller_id ?? ''));
        return [
            'title' => $isAdmin ? 'New Withdrawal Request' : 'Withdrawal Request Submitted',
            'body' => $isAdmin
                ? ($sellerName . ' requested ' . app(\App\Services\CurrencyService::class)->getSymbol() . number_format((float)$req->amount, 2) . '.')
                : ('Amount ' . app(\App\Services\CurrencyService::class)->getSymbol() . number_format((float)$req->amount, 2) . ' has been requested.'),
            'image' => null,
            'data' => [
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
        $isAdmin = method_exists($notifiable, 'hasRole') && $notifiable->hasRole(DefaultSystemRolesEnum::SUPER_ADMIN());
        $sellerName = $req->seller->name ?? $req->seller->user->name ?? $req->seller_name ?? ('Seller #' . ($req->seller_id ?? ''));
        return [
            'title' => $isAdmin ? 'New Withdrawal Request' : 'Withdrawal Request Submitted',
            'message' => $isAdmin
                ? ('Withdrawal request from ' . $sellerName . ' for ' . app(\App\Services\CurrencyService::class)->getSymbol() . number_format((float)$req->amount, 2) . '.')
                : ('A withdrawal request of amount ' . app(\App\Services\CurrencyService::class)->getSymbol() . number_format((float)$req->amount, 2) . ' has been submitted.'),
            'type' => NotificationTypeEnum::WITHDRAWAL_REQUEST(),
            'sent_to' => $isAdmin ? 'admin' : 'seller',
            'user_id' => $notifiable->id ?? null,
            'store_id' => $req->seller_id ?? null,
            'order_id' => null,
            'metadata' => [
                'withdrawal_request_id' => $req->id,
                'status' => (string)$req->status,
                'seller_name' => $sellerName,
            ],
        ];
    }
}
