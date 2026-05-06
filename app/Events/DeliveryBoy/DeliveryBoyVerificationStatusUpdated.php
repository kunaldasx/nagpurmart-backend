<?php

namespace App\Events\DeliveryBoy;

use App\Models\DeliveryBoy;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeliveryBoyVerificationStatusUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public DeliveryBoy $deliveryBoy;
    public User $updatedBy;
    public string $previousStatus;
    public string $newStatus;
    public ?string $remark;

    /**
     * Create a new event instance.
     */
    public function __construct(DeliveryBoy $deliveryBoy, User $updatedBy, string $previousStatus, string $newStatus, ?string $remark = null)
    {
        $this->deliveryBoy = $deliveryBoy;
        $this->updatedBy = $updatedBy;
        $this->previousStatus = $previousStatus;
        $this->newStatus = $newStatus;
        $this->remark = $remark;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}