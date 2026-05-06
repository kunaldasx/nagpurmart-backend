<?php

namespace App\Listeners\Auth;

use App\Events\UserLoggedin;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendLoggedInNotification
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(\App\Events\Auth\UserLoggedIn $event): void
    {
//        dd("user logged in notification sent to " . $event->user->email);
    }
}
