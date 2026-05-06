<?php

namespace App\Notifications;

use App\Broadcasting\FirebaseChannel;
use App\Enums\DefaultSystemRolesEnum;
use App\Enums\NotificationTypeEnum;
use App\Models\SellerStatement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class SellerSettlementSettled extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected SellerStatement $statement)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail', FirebaseChannel::class];
    }

    public function toMail(object $notifiable): ?MailMessage
    {
        try {
            $s = $this->statement;
            $currencySymbol = app(\App\Services\CurrencyService::class)->getSymbol();
            $sellerName = $s->seller?->user?->name ?? ('Seller #' . $s->seller_id);
            $isAdmin = method_exists($notifiable, 'hasRole') && $notifiable->hasRole(DefaultSystemRolesEnum::SUPER_ADMIN());

            $mail = (new MailMessage)
                ->subject($isAdmin ? 'Seller Settlement Settled' : 'Settlement Paid to Wallet')
                ->greeting('Hello ' . ($notifiable->name ?? ''));

            if ($isAdmin) {
                $mail->line('A seller settlement has been marked as settled.')
                    ->line('Seller: ' . $sellerName)
                    ->line('Amount: ' . $currencySymbol . number_format((float)$s->amount, 2))
                    ->line('Reference: ' . ($s->settlement_reference ?: '-'))
                    ->line('Order ID: ' . ($s->order_id ?? '-'))
                    ->line('Settled At: ' . optional($s->settled_at)->toDateTimeString());
            } else {
                $mail->line('An earning from your settlement has been paid to your wallet.')
                    ->line('Amount: ' . $currencySymbol . number_format((float)$s->amount, 2))
                    ->line('Reference: ' . ($s->settlement_reference ?: '-'))
                    ->line('Order ID: ' . ($s->order_id ?? '-'))
                    ->line('Date: ' . optional($s->settled_at)->toDateTimeString());
            }
            return $mail;
        } catch (\Throwable $e) {
            Log::error('SellerSettlementSettled mail failed: ' . $e->getMessage());
            return null;
        }
    }

    public function toFirebase($notifiable): array
    {
        $s = $this->statement;
        $currencySymbol = app(\App\Services\CurrencyService::class)->getSymbol();
        $isAdmin = method_exists($notifiable, 'hasRole') && $notifiable->hasRole(DefaultSystemRolesEnum::SUPER_ADMIN());
        $sellerName = $s->seller?->user?->name ?? ('Seller #' . $s->seller_id);
        return [
            'title' => $isAdmin ? 'Settlement Settled' : 'Wallet Credited',
            'body' => $isAdmin
                ? ($sellerName . ' | ' . $currencySymbol . number_format((float)$s->amount, 2))
                : ('Your wallet credited by ' . $currencySymbol . number_format((float)$s->amount, 2) . '.'),
            'image' => null,
            'data' => [
                'type' => NotificationTypeEnum::SETTLEMENT_PROCESS(),
                'seller_statement_id' => $s->id,
                'order_id' => $s->order_id,
                'status' => (string)$s->settlement_status,
            ],
        ];
    }

    public function toArray(object $notifiable): array
    {
        $s = $this->statement;
        return [
            'seller_statement_id' => $s->id,
            'amount' => (float)$s->amount,
            'status' => (string)$s->settlement_status,
        ];
    }

    public function toDatabase(object $notifiable): array
    {
        $s = $this->statement;
        $currencySymbol = app(\App\Services\CurrencyService::class)->getSymbol();
        $isAdmin = method_exists($notifiable, 'hasRole') && $notifiable->hasRole(DefaultSystemRolesEnum::SUPER_ADMIN());
        $sellerName = $s->seller?->user?->name ?? ('Seller #' . $s->seller_id);
        return [
            'title' => $isAdmin ? 'Settlement Settled' : 'Wallet Credited',
            'message' => $isAdmin
                ? ('Settlement settled for ' . $sellerName . ' amount ' . $currencySymbol . number_format((float)$s->amount, 2) . '.')
                : ('Your wallet has been credited with ' . $currencySymbol . number_format((float)$s->amount, 2) . '.'),
            'type' => NotificationTypeEnum::SETTLEMENT_PROCESS(),
            'sent_to' => $isAdmin ? 'admin' : 'seller',
            'user_id' => $notifiable->id ?? null,
            'store_id' => $s->seller_id ?? null,
            'order_id' => $s->order_id ?? null,
            'metadata' => [
                'seller_statement_id' => $s->id,
                'settlement_reference' => $s->settlement_reference,
                'settled_at' => optional($s->settled_at)->toDateTimeString(),
            ],
        ];
    }
}
