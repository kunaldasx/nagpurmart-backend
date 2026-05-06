<?php

namespace App\Events\Seller;

use App\Models\SellerWithdrawalRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WithdrawalRequestCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The withdrawal request instance.
     *
     * @var SellerWithdrawalRequest
     */
    public $withdrawalRequest;

    /**
     * Create a new event instance.
     *
     * @param SellerWithdrawalRequest $withdrawalRequest
     */
    public function __construct(SellerWithdrawalRequest $withdrawalRequest)
    {
        $this->withdrawalRequest = $withdrawalRequest;
    }
}
