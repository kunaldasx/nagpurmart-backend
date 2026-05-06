<?php

namespace App\Observers;

use App\Enums\NotificationTypeEnum;
use App\Models\SellerSubscription;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class SellerSubscriptionObserver
{
    /**
     * Handle the SellerSubscription "updated" event.
     * Notifies when the subscription status changes.
     */
    public function updated(SellerSubscription $subscription): void
    {
        try {
            if (!$subscription->wasChanged('status')) {
                return; // Only notify for status changes
            }

            $old = (string) $subscription->getOriginal('status');
            $new = (string) $subscription->status;

            $title = 'Subscription status updated';

            $sellerUserId = $subscription->seller?->user?->id;

            // Notify Seller
            if ($sellerUserId) {
                app(NotificationService::class)->createNotification([
                    'type'    => NotificationTypeEnum::SYSTEM(),
                    'user_id' => $sellerUserId,
                    'sent_to' => 'seller',
                    'title'   => $title,
                    'message' => sprintf(
                        'Your subscription for plan "%s" changed from %s to %s.',
                        $subscription->plan?->name ?? 'N/A',
                        $old,
                        $new
                    ),
                    'metadata' => [
                        'seller_subscription_id' => $subscription->id,
                        'plan_id' => $subscription->plan_id,
                        'old_status' => $old,
                        'new_status' => $new,
                        'start_date' => optional($subscription->start_date)->toISOString(),
                        'end_date' => optional($subscription->end_date)->toISOString(),
                        'price_paid' => (string) $subscription->price_paid,
                    ],
                ]);
            }

            // Notify Admins
            app(NotificationService::class)->createNotification([
                'type'    => NotificationTypeEnum::SYSTEM(),
                'sent_to' => 'admin',
                'title'   => $title,
                'message' => sprintf(
                    'Seller #%d subscription for plan "%s" changed from %s to %s.',
                    $subscription->seller_id,
                    $subscription->plan?->name ?? 'N/A',
                    $old,
                    $new
                ),
                'metadata' => [
                    'seller_subscription_id' => $subscription->id,
                    'seller_id' => $subscription->seller_id,
                    'plan_id' => $subscription->plan_id,
                    'old_status' => $old,
                    'new_status' => $new,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('SellerSubscriptionObserver updated notify failed: ' . $e->getMessage());
        }
    }
}
