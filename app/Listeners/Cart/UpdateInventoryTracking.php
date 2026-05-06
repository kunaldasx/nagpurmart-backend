<?php

namespace App\Listeners\Cart;

use App\Enums\StockInventoryTypeEnum;
use App\Events\Cart\ItemAddedToCart;
use App\Events\Cart\ItemRemovedFromCart;
use App\Models\StoreInventoryLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UpdateInventoryTracking
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
        // Log inventory reservation (optional)
        StoreInventoryLog::create([
            'store_id' => $event->cartItem->store_id,
            'product_variant_id' => $event->cartItem->product_variant_id,
            'change_type' => StockInventoryTypeEnum::ADD(),
            'quantity' => $event->cartItem->quantity,
            'reason' => 'Added to cart by user ' . $event->user->id,
            'created_at' => now(),
        ]);
    }

    /**
     * Handle item removed from cart event.
     */
    public function handleItemRemoved(ItemRemovedFromCart $event): void
    {
        // Log inventory release (optional)
        StoreInventoryLog::create([
            'store_id' => $event->cartItem->store_id,
            'product_variant_id' => $event->cartItem->product_variant_id,
            'change_type' => StockInventoryTypeEnum::REMOVE(),
            'quantity' => $event->cartItem->quantity,
            'reason' => 'Removed from cart by user ' . $event->user->id,
            'created_at' => now(),
        ]);
    }
}
