<?php

namespace App\Listeners\Order;

use App\Events\Order\OrderPlaced;
use App\Services\StockService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UpdateStockInventory
{
    protected StockService $stockService;

    /**
     * Create the event listener.
     */
    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Handle the event.
     */
    public function handle(OrderPlaced $event): void
    {
        foreach ($event->orderItem as $item) {
            $this->stockService->removeStock(
                $item->store_id,
                $item->product_variant_id,
                $item->quantity,
                "Moved {$item->quantity} item(s) from stock to Order #{$event->order->id}."
            );
        }
    }
}
