<?php

namespace App\Listeners\Order;

use App\Enums\Order\OrderItemStatusEnum;
use App\Events\Order\OrderStatusUpdated;
use App\Services\OrderService;
use App\Services\StockService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateStockOnOrderStatusChange
{
    protected StockService $stockService;
    protected OrderService $orderService;

    /**
     * Create the event listener.
     */
    public function __construct(StockService $stockService, OrderService $orderService)
    {
        $this->stockService = $stockService;
        $this->orderService = $orderService;
    }

    /**
     * Handle the event.
     */
    public function handle(OrderStatusUpdated $event): void
    {
        // Only process if the new status requires stock to be returned to inventory
        // This includes REJECTED, CANCELLED, RETURNED, and REFUNDED (for completed returns)
        if (in_array($event->newStatus, [
            OrderItemStatusEnum::REJECTED(),
            OrderItemStatusEnum::CANCELLED(),
            OrderItemStatusEnum::RETURNED(),
            OrderItemStatusEnum::REFUNDED(),
        ], true)) {
            try {
                DB::beginTransaction();

                // Get the order item and its details
                $orderItem = $event->orderItem;
                $quantity = $orderItem->quantity;
                $productVariantId = $orderItem->product_variant_id;
                $storeId = $orderItem->store_id;

                // Add the stock back
                $stockResult = $this->stockService->addStock(
                    $storeId,
                    $productVariantId,
                    $quantity,
                    "Returned {$quantity} item(s) to stock due to {$event->newStatus} of Order Item #{$orderItem->id}."
                );

                if (!$stockResult['success']) {
                    throw new \Exception($stockResult['message']);
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error updating stock after order item status change', [
                    'order_item_id' => $event->orderItem->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}
