<?php

namespace App\Events\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ItemRemovedFromCart
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Cart $cart;
    public CartItem $cartItem;
    public User $user;

    /**
     * Create a new event instance.
     */
    public function __construct(Cart $cart, CartItem $cartItem, User $user)
    {
        $this->cart = $cart;
        $this->cartItem = $cartItem;
        $this->user = $user;
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