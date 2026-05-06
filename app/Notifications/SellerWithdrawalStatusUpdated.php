<?php

namespace App\Notifications;

use App\Broadcasting\FirebaseChannel;
use App\Enums\NotificationTypeEnum;
use App\Enums\DefaultSystemRolesEnum;
use App\Events\Seller\WithdrawalRequestProcessed;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class SellerWithdrawalStatusUpdated extends Notification implements ShouldQueue
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

            $isAdmin = method_exists($notifiable, 'hasRole')
                && $notifiable->hasRole(DefaultSystemRolesEnum::SUPER_ADMIN());

            $sellerName = $req->seller->name
                ?? $req->seller->user->name
                ?? $req->seller_name
                ?? ('Seller #' . ($req->seller_id ?? ''));

            $amount = app(\App\Services\CurrencyService::class)->getSymbol() . number_format((float)$req->amount, 2);
            $previous = ucfirst((string)$this->event->previousStatus);
            $new = ucfirst((string)$req->status);

            $mail = (new MailMessage)
                ->subject(
                    $isAdmin
                        ? 'Seller Withdrawal Status Updated'
                        : 'Your Withdrawal Request Status Updated'
                )
                ->greeting('Hello ' . ($notifiable->name ?? ''));

            if ($isAdmin) {
                $mail->line("Withdrawal request from {$sellerName} has been updated.")
                    ->line("Amount: {$amount}")
                    ->line("Previous Status: {$previous}")
                    ->line("New Status: {$new}")
                    ->line("Processed At: " . optional($req->processed_at)->toDateTimeString());
            } else {
                $mail->line("Your withdrawal request has been updated.")
                    ->line("Amount: {$amount}")
                    ->line("Previous Status: {$previous}")
                    ->line("New Status: {$new}")
                    ->line("Processed At: " . optional($req->processed_at)->toDateTimeString());
            }

            return $mail;

        } catch (\Throwable $e) {
            Log::error('SellerWithdrawalStatusUpdated mail failed: ' . $e->getMessage());
            return null;
        }
    }

    public function toFirebase($notifiable): array
    {
        $req = $this->event->withdrawalRequest;

        $isAdmin = method_exists($notifiable, 'hasRole')
            && $notifiable->hasRole(DefaultSystemRolesEnum::SUPER_ADMIN());

        $sellerName = $req->seller->name
            ?? $req->seller->user->name
            ?? $req->seller_name
            ?? ('Seller #' . ($req->seller_id ?? ''));

        $amount = app(\App\Services\CurrencyService::class)->getSymbol() . number_format((float)$req->amount, 2);

        return [
            'title' => $isAdmin
                ? 'Seller Withdrawal ' . ucfirst((string)$req->status)
                : 'Withdrawal ' . ucfirst((string)$req->status),

            'body' => $isAdmin
                ? "{$sellerName}'s withdrawal of {$amount} is now {$req->status}."
                : "Your withdrawal of {$amount} is now {$req->status}.",

            'image' => null,

            'data' => [
                'type' => NotificationTypeEnum::WITHDRAWAL_PROCESS(),
                'withdrawal_request_id' => $req->id,
                'status' => (string)$req->status,
            ],
        ];
    }

    public function toArray(object $notifiable): array
    {
        $req = $this->event->withdrawalRequest;

        $isAdmin = method_exists($notifiable, 'hasRole')
            && $notifiable->hasRole(DefaultSystemRolesEnum::SUPER_ADMIN());

        return [
            'withdrawal_request_id' => $req->id,
            'amount' => (float)$req->amount,
            'previous_status' => (string)$this->event->previousStatus,
            'new_status' => (string)$req->status,
            'recipient_type' => $isAdmin ? 'admin' : 'seller',
        ];
    }

    public function toDatabase(object $notifiable): array
    {
        $req = $this->event->withdrawalRequest;

        $isAdmin = method_exists($notifiable, 'hasRole')
            && $notifiable->hasRole(DefaultSystemRolesEnum::SUPER_ADMIN());

        $sellerName = $req->seller->name
            ?? $req->seller->user->name
            ?? $req->seller_name
            ?? ('Seller #' . ($req->seller_id ?? ''));

        $amount = app(\App\Services\CurrencyService::class)->getSymbol() . number_format((float)$req->amount, 2);
        $previous = ucfirst((string)$this->event->previousStatus);
        $new = ucfirst((string)$req->status);

        return [
            'title' => $isAdmin
                ? 'Seller Withdrawal Status Updated'
                : 'Withdrawal Request Status Updated',

            'message' => $isAdmin
                ? "Withdrawal request from {$sellerName} for {$amount} has been updated from {$previous} to {$new}."
                : "Your withdrawal request of {$amount} has been updated from {$previous} to {$new}.",

            'type' => NotificationTypeEnum::WITHDRAWAL_PROCESS(),
            'sent_to' => $isAdmin ? 'admin' : 'seller',
            'user_id' => $notifiable->id ?? null,
            'store_id' => $req->seller_id ?? null,
            'order_id' => null,

            'metadata' => [
                'withdrawal_request_id' => $req->id,
                'previous_status' => (string)$this->event->previousStatus,
                'new_status' => (string)$req->status,
                'seller_name' => $sellerName,
            ],
        ];
    }
}
