<?php

namespace App\Notifications;

use App\Broadcasting\FirebaseChannel;
use App\Enums\NotificationTypeEnum;
use App\Models\WalletTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class WalletTransactionOccurred extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected WalletTransaction $transaction)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail', FirebaseChannel::class];
    }

    public function toMail(object $notifiable): ?MailMessage
    {
        try {
            $t = $this->transaction;
            return (new MailMessage)
                ->subject('Wallet Transaction ' . ucfirst((string)$t->status))
                ->greeting('Hello ' . ($notifiable->name ?? ''))
                ->line('A wallet transaction has been recorded on your account:')
                ->line('Type: ' . (string)$t->transaction_type)
                ->line('Amount: ' . app(\App\Services\CurrencyService::class)->getSymbol() . number_format((float)$t->amount, 2))
                ->line('Status: ' . ucfirst((string)$t->status))
                ->line('Description: ' . ($t->description ?: '-'))
                ->line('Date: ' . optional($t->created_at)->toDateTimeString());
        } catch (\Throwable $e) {
            Log::error('WalletTransactionOccurred mail failed: ' . $e->getMessage());
            return null;
        }
    }

    public function toFirebase($notifiable): array
    {
        $t = $this->transaction;
        return [
            'title' => 'Wallet ' . ucfirst((string)$t->transaction_type),
            'body' => 'Amount ' . app(\App\Services\CurrencyService::class)->getSymbol() . number_format((float)$t->amount, 2) . ' | ' . ucfirst((string)$t->status),
            'image' => null,
            'data' => [
                'type' => NotificationTypeEnum::WALLET_TRANSACTION(),
                'wallet_transaction_id' => $t->id,
                'transaction_type' => (string)$t->transaction_type,
                'status' => (string)$t->status,
            ],
        ];
    }

    public function toArray(object $notifiable): array
    {
        $t = $this->transaction;
        return [
            'wallet_transaction_id' => $t->id,
            'amount' => (float)$t->amount,
            'transaction_type' => (string)$t->transaction_type,
            'status' => (string)$t->status,
        ];
    }

    public function toDatabase(object $notifiable): array
    {
        $t = $this->transaction;
        return [
            'title' => 'Wallet ' . ucfirst((string)$t->transaction_type),
            'message' => 'A wallet transaction has occurred. Amount: ' . app(\App\Services\CurrencyService::class)->getSymbol() . number_format((float)$t->amount, 2),
            'type' => NotificationTypeEnum::WALLET_TRANSACTION(),
            'sent_to' => 'seller',
            'user_id' => $notifiable->id ?? null,
            'store_id' => $t->store_id ?? null,
            'order_id' => $t->order_id ?? null,
            'metadata' => [
                'wallet_transaction_id' => $t->id,
                'transaction_type' => (string)$t->transaction_type,
                'status' => (string)$t->status,
            ],
        ];
    }
}
