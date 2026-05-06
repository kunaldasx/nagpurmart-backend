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

class DeliveryBoyRegistered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;
    public DeliveryBoy $deliveryBoy;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, DeliveryBoy $deliveryBoy)
    {
        $this->user = $user;
        $this->deliveryBoy = $deliveryBoy;
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
