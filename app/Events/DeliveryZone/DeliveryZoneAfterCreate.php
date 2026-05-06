<?php

namespace App\Events\DeliveryZone;

use App\Models\DeliveryZone;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeliveryZoneAfterCreate
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public DeliveryZone $deliveryZone;
    public function __construct(DeliveryZone $deliveryZone)
    {
        $this->deliveryZone = $deliveryZone;
    }
}
