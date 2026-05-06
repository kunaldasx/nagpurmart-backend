<?php

namespace App\Listeners\DeliveryBoy;

use App\Enums\GuardNameEnum;
use App\Events\DeliveryBoy\WithdrawalRequestCreated;
use App\Notifications\DeliveryBoy\DeliveryBoyWithdrawalRequested;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class DeliveryBoyWithdrawalRequestedNotification implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(WithdrawalRequestCreated $event): void
    {
        try {
            $req = $event->withdrawalRequest;

            // Notify the delivery boy user
            $deliveryBoyUser = $req->user; // relation on DeliveryBoyWithdrawalRequest -> user
            if ($deliveryBoyUser) {
                $deliveryBoyUser->notify(new DeliveryBoyWithdrawalRequested($event));
            }

            // Notify all admins
            $admins = User::where('access_panel', GuardNameEnum::ADMIN())->get();
            foreach ($admins as $admin) {
                $admin->notify(new DeliveryBoyWithdrawalRequested($event));
            }
        } catch (\Throwable $e) {
            Log::error('DeliveryBoyWithdrawalRequestedNotification failed: ' . $e->getMessage());
        }
    }
}
