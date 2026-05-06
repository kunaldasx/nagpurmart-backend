<?php

namespace App\Listeners\seller;

use App\Events\Seller\SellerStatusUpdated;

class SellerStatusUpdatedNotification
{

    /**
     * Handle the event.
     */
    public function handle(SellerStatusUpdated $event): void
    {
        // seller status updated notification
    }
}
