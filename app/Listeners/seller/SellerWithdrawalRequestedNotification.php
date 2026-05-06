<?php

namespace App\Listeners\seller;

use App\Enums\GuardNameEnum;
use App\Events\Seller\WithdrawalRequestCreated;
use App\Notifications\SellerWithdrawalRequested;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SellerWithdrawalRequestedNotification implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(WithdrawalRequestCreated $event): void
    {
        try {
            $req = $event->withdrawalRequest;

            // Notify the seller user
            $sellerUser = $req->user; // relation on SellerWithdrawalRequest -> user
            if ($sellerUser) {
                $sellerUser->notify(new SellerWithdrawalRequested($event));
            }

            // Notify all admins
            $admins = User::where('access_panel', GuardNameEnum::ADMIN)->get();
            foreach ($admins as $admin) {
                $admin->notify(new SellerWithdrawalRequested($event));
            }
        } catch (\Throwable $e) {
            Log::error('SellerWithdrawalRequestedNotification failed: ' . $e->getMessage());
            // Swallow exceptions to avoid breaking the main flow
        }
    }
}
