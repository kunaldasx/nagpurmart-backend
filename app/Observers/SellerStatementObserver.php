<?php

namespace App\Observers;

use App\Enums\DefaultSystemRolesEnum;
use App\Enums\GuardNameEnum;
use App\Enums\Seller\SellerSettlementStatusEnum;
use App\Models\SellerStatement;
use App\Models\User;
use App\Notifications\SellerSettlementCreated;
use App\Notifications\SellerSettlementSettled;
use Illuminate\Support\Facades\Log;

class SellerStatementObserver
{
    /**
     * Handle the SellerStatement "created" event.
     * Notify seller and admins that a new settlement entry is created (usually on order delivery).
     */
    public function created(SellerStatement $statement): void
    {
        try {
            // Notify Seller (owner user of the seller)
            $sellerUser = $statement->seller?->user;
            if ($sellerUser instanceof User) {
                $sellerUser->notify(new SellerSettlementCreated($statement));
            }

            // Notify Admins
            $this->notifyAdmins(new SellerSettlementCreated($statement));
        } catch (\Throwable $e) {
            Log::error('SellerStatementObserver created notify failed: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        }
    }

    /**
     * Handle the SellerStatement "updated" event.
     * When settlement_status changes to SETTLED, notify seller and admins (wallet settled by admin).
     */
    public function updated(SellerStatement $statement): void
    {
        try {
            if ($statement->wasChanged('settlement_status') && (string)$statement->settlement_status === (string)SellerSettlementStatusEnum::SETTLED()) {
                $sellerUser = $statement->seller?->user;
                if ($sellerUser instanceof User) {
                    $sellerUser->notify(new SellerSettlementSettled($statement));
                }
                $this->notifyAdmins(new SellerSettlementSettled($statement));
            }
        } catch (\Throwable $e) {
            Log::error('SellerStatementObserver updated notify failed: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        }
    }

    protected function notifyAdmins($notification): void
    {
        try {
            // Use Spatie roles scope to fetch super admins
            $admins = User::where('access_panel', GuardNameEnum::ADMIN())->get();
            foreach ($admins as $admin) {
                try {
                    $admin->notify($notification);
                } catch (\Throwable $e) {
                    Log::warning('Failed notifying admin about seller settlement: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
                }
            }
        } catch (\Throwable $e) {

            Log::warning('SellerStatementObserver failed to fetch admins: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        }
    }
}
