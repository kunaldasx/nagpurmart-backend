<?php

namespace App\Listeners\Order;

use App\Enums\Order\OrderItemStatusEnum;
use App\Events\Order\OrderStatusUpdated;
use App\Models\OrderItem;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class UpdateStockOnOrderStatusChange
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
    public function handle(OrderStatusUpdated $event): void
    {
        // Only terminal item statuses that restore inventory are handled here.
        if (in_array($event->newStatus, [
            OrderItemStatusEnum::REJECTED(),
            OrderItemStatusEnum::CANCELLED(),
            OrderItemStatusEnum::RETURNED(),
            OrderItemStatusEnum::REFUNDED(),
            OrderItemStatusEnum::FAILED(),
        ], true)) {
            if (!$event->orderItem) {
                Log::info('Skipping stock update for order-level status change', [
                    'old_status' => $event->oldStatus,
                    'new_status' => $event->newStatus,
                ]);

                return;
            }

            DB::transaction(function () use ($event): void {
                $orderItem = OrderItem::query()
                    ->lockForUpdate()
                    ->findOrFail($event->orderItem->id);

                if (!$orderItem->stock_deducted_at || $orderItem->stock_restored_at) {
                    return;
                }

                $quantity = $orderItem->quantity;
                $stockResult = $this->stockService->addStock(
                    $orderItem->store_id,
                    $orderItem->product_variant_id,
                    $quantity,
                    "Returned {$quantity} item(s) to stock due to {$event->newStatus} of Order Item #{$orderItem->id}."
                );

                if (!$stockResult['success']) {
                    throw new RuntimeException($stockResult['message']);
                }

                $orderItem->stock_restored_at = now();
                $orderItem->save();
            });
        }
    }
}
