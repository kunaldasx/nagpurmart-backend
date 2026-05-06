<?php

namespace App\Listeners\seller;

use App\Events\Seller\SellerUpdated;

class SellerUpdatedNotification
{
    /**
     * Handle the event.
     */
    public function handle(SellerUpdated $event): void
    {
//        dd("Seller updated notification");
    }
}
