<?php

namespace App\Events\DeliveryBoy;

use App\Models\DeliveryBoyWithdrawalRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WithdrawalRequestProcessed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The withdrawal request instance.
     *
     * @var DeliveryBoyWithdrawalRequest
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
     * @param DeliveryBoyWithdrawalRequest $withdrawalRequest
     * @param string $previousStatus
     */
    public function __construct(DeliveryBoyWithdrawalRequest $withdrawalRequest, string $previousStatus)
    {
        $this->withdrawalRequest = $withdrawalRequest;
        $this->previousStatus = $previousStatus;
    }
}
