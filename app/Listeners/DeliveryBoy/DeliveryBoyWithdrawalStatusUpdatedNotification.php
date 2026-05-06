<?php

namespace App\Listeners\DeliveryBoy;

use App\Enums\GuardNameEnum;
use App\Events\DeliveryBoy\WithdrawalRequestProcessed;
use App\Notifications\DeliveryBoy\DeliveryBoyWithdrawalStatusUpdated;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class DeliveryBoyWithdrawalStatusUpdatedNotification implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(WithdrawalRequestProcessed $event): void
    {
        try {
            $req = $event->withdrawalRequest;

            // Notify the delivery boy user
            $deliveryBoyUser = $req->user; // relation on DeliveryBoyWithdrawalRequest -> user
            if ($deliveryBoyUser) {
                $deliveryBoyUser->notify(new DeliveryBoyWithdrawalStatusUpdated($event));
            }

            // Notify all admins
            $admins = User::where('access_panel', GuardNameEnum::ADMIN())->get();
            foreach ($admins as $admin) {
                $admin->notify(new DeliveryBoyWithdrawalStatusUpdated($event));
            }
        } catch (\Throwable $e) {
            Log::error('DeliveryBoyWithdrawalStatusUpdatedNotification failed: ' . $e->getMessage());
        }
    }
}
