<?php

namespace App\Observers;

use App\Enums\DefaultSystemRolesEnum;
use App\Enums\GuardNameEnum;
use App\Enums\Store\StoreVerificationStatusEnum;
use App\Models\Store;
use App\Models\User;
use App\Notifications\Store\NewStoreCreatedNotification;
use App\Notifications\Store\StoreStatusUpdatedNotification;
use App\Notifications\Store\StoreVerificationUpdatedNotification;

class StoreObserver
{
    /**
     * Handle the Store "created" event.
     */
    public function created(Store $store): void
    {
        // Notify Admins and Seller when a new store is created
        $admins = $this->getAdmins();
        foreach ($admins as $admin) {
            $admin->notify(new NewStoreCreatedNotification($store));
        }

        $sellerUser = $store->seller?->user;
        if ($sellerUser) {
            $sellerUser->notify(new NewStoreCreatedNotification($store));
        }
    }

    /**
     * Handle the Store "updated" event.
     */
    public function updated(Store $store): void
    {
        // Verification status change -> notify Admins and Seller
        if ($store->wasChanged('verification_status')) {
            $old = $store->getOriginal('verification_status');
            $new = $store->verification_status;

            // Normalize to enum string values for payloads
            $oldVal = $old instanceof StoreVerificationStatusEnum ? $old->value : (string) $old;
            $newVal = $new instanceof StoreVerificationStatusEnum ? $new->value : (string) $new;

            $admins = $this->getAdmins();
            foreach ($admins as $admin) {
                $admin->notify(new StoreVerificationUpdatedNotification($store, $oldVal, $newVal));
            }

            $sellerUser = $store->seller?->user;
            if ($sellerUser) {
                $sellerUser->notify(new StoreVerificationUpdatedNotification($store, $oldVal, $newVal));
            }
        }

        // Visibility/status change -> notify Seller
        $visibilityChanged = $store->wasChanged('visibility_status');
        $statusChanged = $store->wasChanged('status');
        if ($visibilityChanged || $statusChanged) {
            $sellerUser = $store->seller?->user;
            if ($sellerUser) {
                $sellerUser->notify(new StoreStatusUpdatedNotification(
                    $store,
                    $visibilityChanged ? $store->getOriginal('visibility_status') : null,
                    $visibilityChanged ? $store->visibility_status : null,
                    $statusChanged ? $store->getOriginal('status') : null,
                    $statusChanged ? $store->status : null,
                ));
            }
        }
    }

    /**
     * Get all admin users (Super Admin role).
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    protected function getAdmins()
    {
        try {
            return User::where('access_panel', GuardNameEnum::ADMIN())->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }
}
