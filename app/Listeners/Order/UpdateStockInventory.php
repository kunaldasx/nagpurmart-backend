<?php

namespace App\Listeners\Order;

use App\Events\Order\OrderPlaced;
use App\Models\OrderItem;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

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
        DB::transaction(function () use ($event): void {
            foreach ($event->orderItem as $item) {
                $orderItem = OrderItem::query()
                    ->lockForUpdate()
                    ->findOrFail($item->id);

                if ($orderItem->stock_deducted_at) {
                    continue;
                }

                $stockResult = $this->stockService->removeStock(
                    $orderItem->store_id,
                    $orderItem->product_variant_id,
                    $orderItem->quantity,
                    "Moved {$orderItem->quantity} item(s) from stock to Order #{$event->order->id}.",
                    (int) $orderItem->id
                );

                if (!$stockResult['success']) {
                    throw new RuntimeException($stockResult['message']);
                }

                $orderItem->stock_deducted_at = now();
                $orderItem->save();
            }
        });
    }
}
