<?php

namespace App\Events\DeliveryBoy;

use App\Models\DeliveryBoyWithdrawalRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WithdrawalRequestCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The withdrawal request instance.
     *
     * @var DeliveryBoyWithdrawalRequest
     */
    public $withdrawalRequest;

    /**
     * Create a new event instance.
     *
     * @param DeliveryBoyWithdrawalRequest $withdrawalRequest
     */
    public function __construct(DeliveryBoyWithdrawalRequest $withdrawalRequest)
    {
        $this->withdrawalRequest = $withdrawalRequest;
    }
}
