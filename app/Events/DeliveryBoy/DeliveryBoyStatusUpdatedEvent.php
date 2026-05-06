<?php

namespace App\Events\DeliveryBoy;

use App\Models\DeliveryBoy;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeliveryBoyStatusUpdatedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public DeliveryBoy $deliveryBoy;
    public User $updatedBy;
    public string $newStatus;
    public ?float $latitude;
    public ?float $longitude;

    /**
     * Create a new event instance.
     */
    public function __construct(DeliveryBoy $deliveryBoy, User $updatedBy, string $newStatus, ?float $latitude = null, ?float $longitude = null)
    {
        $this->deliveryBoy = $deliveryBoy;
        $this->updatedBy = $updatedBy;
        $this->newStatus = $newStatus;
        $this->latitude = $latitude;
        $this->longitude = $longitude;
    }
}
