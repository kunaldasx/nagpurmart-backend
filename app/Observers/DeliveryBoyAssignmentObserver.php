<?php

namespace App\Observers;

use App\Enums\GuardNameEnum;
use App\Enums\DeliveryBoy\DeliveryBoyAssignmentStatusEnum;
use App\Enums\DeliveryBoy\EarningPaymentStatusEnum;
use App\Models\DeliveryBoyAssignment;
use App\Models\User;
use App\Notifications\DeliveryBoy\DeliveryBoySettlementCreated;
use App\Notifications\DeliveryBoy\DeliveryBoySettlementSettled;
use Illuminate\Support\Facades\Log;

class DeliveryBoyAssignmentObserver
{
    /**
     * Handle the DeliveryBoyAssignment "created" event.
     * As per latest flow, do not notify on creation. Earning should be added when assignment completes.
     */
    public function created(DeliveryBoyAssignment $assignment): void
    {
        // Intentionally left blank to avoid premature notifications
    }

    /**
     * Handle the DeliveryBoyAssignment "updated" event.
     * When payment_status changes to PAID, notify delivery boy and admins.
     */
    public function updated(DeliveryBoyAssignment $assignment): void
    {
        try {
            // When assignment status becomes COMPLETED, notify delivery boy and admins about new earning entry
            if ($assignment->wasChanged('status')
                && (string)$assignment->status === (string)DeliveryBoyAssignmentStatusEnum::COMPLETED()) {
                $user = $assignment->deliveryBoy?->user;
                if ($user instanceof User) {
                    $user->notify(new DeliveryBoySettlementCreated($assignment));
                }
                $this->notifyAdmins(new DeliveryBoySettlementCreated($assignment));
            }

            if ($assignment->wasChanged('payment_status')
                && (string)$assignment->payment_status === (string)EarningPaymentStatusEnum::PAID()) {
                $user = $assignment->deliveryBoy?->user;
                if ($user instanceof User) {
                    $user->notify(new DeliveryBoySettlementSettled($assignment));
                }
                $this->notifyAdmins(new DeliveryBoySettlementSettled($assignment));
            }
        } catch (\Throwable $e) {
            Log::error('DeliveryBoyAssignmentObserver updated notify failed: ' . $e->getMessage());
        }
    }

    protected function notifyAdmins($notification): void
    {
        try {
            $admins = User::where('access_panel', GuardNameEnum::ADMIN())->get();
            foreach ($admins as $admin) {
                try {
                    $admin->notify($notification);
                } catch (\Throwable $e) {
                    Log::warning('Failed notifying admin about delivery boy settlement: ' . $e->getMessage());
                }
            }
        } catch (\Throwable $e) {
            Log::warning('DeliveryBoyAssignmentObserver failed to fetch admins: ' . $e->getMessage());
        }
    }
}
