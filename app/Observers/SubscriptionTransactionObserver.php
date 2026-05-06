<?php

namespace App\Observers;

use App\Enums\NotificationTypeEnum;
use App\Enums\Subscription\SubscriptionTransactionStatusEnum;
use App\Models\SubscriptionTransaction;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class SubscriptionTransactionObserver
{

    /**
     * Handle the SubscriptionTransaction "created" event.
     * Notify seller and admins that a subscription purchase was initiated.
     */
    public function created(SubscriptionTransaction $txn): void
    {
        try {
            $sellerUserId = $txn->seller?->user?->id;

            // Notify Seller
            if ($sellerUserId) {
                app(NotificationService::class)->createNotification([
                    'type'    => NotificationTypeEnum::PAYMENT(),
                    'user_id' => $sellerUserId,
                    'sent_to' => 'seller',
                    'title'   => 'Subscription purchase initiated',
                    'message' => sprintf(
                        'Your purchase for plan "%s" (%s %s) is %s.',
                        $txn->plan?->name ?? 'N/A',
                        (string) $txn->amount,
                        $txn->payment_gateway ?? 'gateway',
                        (string) $txn->status
                    ),
                    'metadata' => [
                        'subscription_transaction_id' => $txn->id,
                        'seller_subscription_id' => $txn->seller_subscription_id,
                        'plan_id' => $txn->plan_id,
                        'amount' => (string) $txn->amount,
                        'status' => (string) $txn->status,
                        'payment_gateway' => $txn->payment_gateway,
                        'transaction_id' => $txn->transaction_id,
                    ],
                ]);
            }

            // Notify Admins (generic admin feed)
            app(NotificationService::class)->createNotification([
                'type'    => NotificationTypeEnum::PAYMENT(),
                'sent_to' => 'admin',
                'title'   => 'Seller subscription purchase initiated',
                'message' => sprintf(
                    'Seller #%d started purchase for plan "%s". Status: %s.',
                    $txn->seller_id,
                    $txn->plan?->name ?? 'N/A',
                    (string) $txn->status
                ),
                'metadata' => [
                    'subscription_transaction_id' => $txn->id,
                    'seller_id' => $txn->seller_id,
                    'plan_id' => $txn->plan_id,
                    'amount' => (string) $txn->amount,
                    'status' => (string) $txn->status,
                    'payment_gateway' => $txn->payment_gateway,
                    'transaction_id' => $txn->transaction_id,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('SubscriptionTransactionObserver created notify failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle the SubscriptionTransaction "updated" event.
     * On status changes (e.g., COMPLETED/FAILED), notify seller and admins.
     */
    public function updated(SubscriptionTransaction $txn): void
    {
        try {
            if (!$txn->wasChanged('status')) {
                return; // Only care about status updates
            }

            $newStatus = (string) $txn->status;

            $title = 'Subscription payment update';
            if ($newStatus === (string) SubscriptionTransactionStatusEnum::COMPLETED()) {
                $title = 'Subscription payment completed';
            } elseif ($newStatus === (string) SubscriptionTransactionStatusEnum::FAILED()) {
                $title = 'Subscription payment failed';
            }

            $sellerUserId = $txn->seller?->user?->id;

            // Notify Seller
            if ($sellerUserId) {
                app(NotificationService::class)->createNotification([
                    'type'    => NotificationTypeEnum::PAYMENT(),
                    'user_id' => $sellerUserId,
                    'sent_to' => 'seller',
                    'title'   => $title,
                    'message' => sprintf(
                        'Your payment for plan "%s" is now %s. Amount: %s.',
                        $txn->plan?->name ?? 'N/A',
                        $newStatus,
                        (string) $txn->amount
                    ),
                    'metadata' => [
                        'subscription_transaction_id' => $txn->id,
                        'seller_subscription_id' => $txn->seller_subscription_id,
                        'plan_id' => $txn->plan_id,
                        'amount' => (string) $txn->amount,
                        'status' => $newStatus,
                        'payment_gateway' => $txn->payment_gateway,
                        'transaction_id' => $txn->transaction_id,
                    ],
                ]);
            }

            // Notify Admins
            app(NotificationService::class)->createNotification([
                'type'    => NotificationTypeEnum::PAYMENT(),
                'sent_to' => 'admin',
                'title'   => $title,
                'message' => sprintf(
                    'Seller #%d payment for plan "%s" is now %s.',
                    $txn->seller_id,
                    $txn->plan?->name ?? 'N/A',
                    $newStatus
                ),
                'metadata' => [
                    'subscription_transaction_id' => $txn->id,
                    'seller_id' => $txn->seller_id,
                    'plan_id' => $txn->plan_id,
                    'amount' => (string) $txn->amount,
                    'status' => $newStatus,
                    'payment_gateway' => $txn->payment_gateway,
                    'transaction_id' => $txn->transaction_id,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('SubscriptionTransactionObserver updated notify failed: ' . $e->getMessage());
        }
    }
}
