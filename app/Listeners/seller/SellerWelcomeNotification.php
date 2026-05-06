<?php

namespace App\Listeners\seller;

use App\Events\Seller\SellerRegistered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SellerWelcomeNotification
{
    /**
     * Handle the event.
     */
    public function handle(SellerRegistered $event): void
    {
//        dd("welcome email sent to " . $event->user->email);
    }
}
