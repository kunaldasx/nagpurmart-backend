<?php

namespace App\Observers;

use App\Enums\Order\OrderItemStatusEnum;
use App\Models\OrderItem;
use App\Services\SellerOrderPopupOfferService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderItemObserver
{
    public function updated(OrderItem $orderItem): void
    {
        if (!$orderItem->wasChanged('status') || $orderItem->status !== OrderItemStatusEnum::CANCELLED()) {
            return;
        }

        DB::afterCommit(function () use ($orderItem): void {
            try {
                app(SellerOrderPopupOfferService::class)
                    ->releaseOfferForCancelledOrderItem((int) $orderItem->id);
            } catch (\Throwable $e) {
                Log::error('Failed to release popup offer for canceled order item.', [
                    'order_item_id' => $orderItem->id,
                    'exception' => $e,
                ]);
                throw $e;
            }
        });
    }
}
