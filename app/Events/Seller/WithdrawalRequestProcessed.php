<?php

namespace App\Events\Seller;

use App\Models\SellerWithdrawalRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WithdrawalRequestProcessed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The withdrawal request instance.
     *
     * @var SellerWithdrawalRequest
     */
    public $withdrawalRequest;

    /**
     * The previous status of the withdrawal request.
     *
     * @var string
     */
    public $previousStatus;

    /**
     * Create a new event instance.
     *
     * @param SellerWithdrawalRequest $withdrawalRequest
     * @param string $previousStatus
     */
    public function __construct(SellerWithdrawalRequest $withdrawalRequest, string $previousStatus)
    {
        $this->withdrawalRequest = $withdrawalRequest;
        $this->previousStatus = $previousStatus;
    }
}
