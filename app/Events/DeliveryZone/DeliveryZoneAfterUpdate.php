<?php

namespace App\Events\DeliveryZone;

use App\Models\DeliveryZone;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeliveryZoneAfterUpdate
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
