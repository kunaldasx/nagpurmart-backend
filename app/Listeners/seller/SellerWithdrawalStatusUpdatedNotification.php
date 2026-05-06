<?php

namespace App\Listeners\seller;

use App\Enums\GuardNameEnum;
use App\Events\Seller\WithdrawalRequestProcessed;
use App\Notifications\SellerWithdrawalStatusUpdated;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SellerWithdrawalStatusUpdatedNotification implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(WithdrawalRequestProcessed $event): void
    {
        try {
            $req = $event->withdrawalRequest;

            // Notify the seller user
            $sellerUser = $req->user; // relation on SellerWithdrawalRequest -> user
            if ($sellerUser) {
                $sellerUser->notify(new SellerWithdrawalStatusUpdated($event));
            }

            // Notify all admins
            $admins = User::where('access_panel', GuardNameEnum::ADMIN)->get();
            foreach ($admins as $admin) {
                $admin->notify(new SellerWithdrawalStatusUpdated($event));
            }
        } catch (\Throwable $e) {
            Log::error('SellerWithdrawalStatusUpdatedNotification failed: ' . $e->getMessage());
            // Do not break the main process
        }
    }
}
