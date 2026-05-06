<?php

namespace App\Events\Seller;

use App\Models\Seller;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SellerUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public $seller;
    public $user;

    public function __construct(Seller $seller, User $user)
    {
        $this->seller = $seller;
        $this->user = $user;
    }
}
