<?php

namespace App\Observers;

use App\Models\WalletTransaction;
use App\Notifications\WalletTransactionOccurred;
use Illuminate\Support\Facades\Log;

class WalletTransactionObserver
{
    /**
     * Handle the WalletTransaction "created" event.
     */
    public function created(WalletTransaction $transaction): void
    {
        try {
            $user = $transaction->user; // BelongsTo User
            if ($user) {
                $user->notify(new WalletTransactionOccurred($transaction));
            }
        } catch (\Throwable $e) {
            Log::error('WalletTransactionObserver notify failed: ' . $e->getMessage());
            // Never throw to avoid breaking main flow
        }
    }

    /**
     * Handle the WalletTransaction "updated" event.
     */
    public function updated(WalletTransaction $transaction): void
    {
        try {
            // Notify only when meaningful fields change
            $interesting = $transaction->wasChanged([
                'status',
                'amount',
                'transaction_type',
                'description',
                'payment_method',
            ]);

            if (!$interesting) {
                return; // skip noise updates (timestamps, etc.)
            }

            $user = $transaction->user; // BelongsTo User
            if ($user) {
                $user->notify(new WalletTransactionOccurred($transaction));
            }
        } catch (\Throwable $e) {
            Log::error('WalletTransactionObserver update notify failed: ' . $e->getMessage());
            // Swallow exceptions to avoid breaking main flow
        }
    }
}
