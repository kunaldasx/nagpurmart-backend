<?php

namespace App\Events\DeliveryZone;

use App\Models\DeliveryZone;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeliveryZoneBeforeUpdate
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public DeliveryZone $deliveryZone;
    /**
     * Create a new event instance.
     */
    public function __construct(DeliveryZone $deliveryZone)
    {
        $this->deliveryZone = $deliveryZone;
    }
}
