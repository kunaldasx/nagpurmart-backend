<?php

namespace App\Observers;

use App\Enums\Order\OrderStatusEnum;
use App\Models\Order;
use App\Services\DeliveryBoyOrderOfferService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class OrderObserver
{
    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        try {
            if ($order->wasChanged('status') && $order->status === OrderStatusEnum::READY_FOR_PICKUP()) {
                DB::afterCommit(fn () => app(DeliveryBoyOrderOfferService::class)->offerNextRider((int) $order->id));
            }

            if ($order->wasChanged('status') && $order->status === OrderStatusEnum::CANCELLED()) {
                DB::afterCommit(fn () => app(DeliveryBoyOrderOfferService::class)->cancelPendingForOrder((int) $order->id, 'cancelled'));
            }
        } catch (\Throwable $e) {
            Log::warning('OrderObserver rider offer update failed: ' . $e->getMessage(), [
                'order_id' => $order->id ?? null,
                'exception' => $e,
                'stack_trace' => $e->getTraceAsString()
            ]);
        }
    }
}
