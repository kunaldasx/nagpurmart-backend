<?php

namespace App\Events\Cart;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CartUpdatedByLocation
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Cart $cart;
    public array $removedItems;
    public User $user;
    public float $latitude;
    public float $longitude;

    /**
     * Create a new event instance.
     */
    public function __construct(Cart $cart, array $removedItems, User $user, float $latitude, float $longitude)
    {
        $this->cart = $cart;
        $this->removedItems = $removedItems;
        $this->user = $user;
        $this->latitude = $latitude;
        $this->longitude = $longitude;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('cart.' . $this->user->id),
        ];
    }
}