<?php

namespace App\Listeners\Cart;

use App\Events\Cart\ItemAddedToCart;
use App\Events\Cart\ItemRemovedFromCart;
use App\Events\Cart\CartUpdatedByLocation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class LogCartActivity
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle item added to cart event.
     */
    public function handleItemAdded(ItemAddedToCart $event): void
    {
        Log::info('Item added to cart', [
            'user_id' => $event->user->id,
            'cart_id' => $event->cart->id,
            'product_id' => $event->cartItem->product_id,
            'variant_id' => $event->cartItem->product_variant_id,
            'store_id' => $event->cartItem->store_id,
            'quantity' => $event->cartItem->quantity,
        ]);
    }

    /**
     * Handle item removed from cart event.
     */
    public function handleItemRemoved(ItemRemovedFromCart $event): void
    {
        Log::info('Item removed from cart', [
            'user_id' => $event->user->id,
            'cart_id' => $event->cart->id,
            'product_id' => $event->cartItem->product_id,
            'variant_id' => $event->cartItem->product_variant_id,
            'store_id' => $event->cartItem->store_id,
            'quantity' => $event->cartItem->quantity,
        ]);
    }

    /**
     * Handle cart updated by location event.
     */
    public function handleCartUpdatedByLocation(CartUpdatedByLocation $event): void
    {
        Log::info('Cart updated by location', [
            'user_id' => $event->user->id,
            'cart_id' => $event->cart->id,
            'latitude' => $event->latitude,
            'longitude' => $event->longitude,
            'removed_items_count' => count($event->removedItems),
            'removed_items' => $event->removedItems,
        ]);
    }
}