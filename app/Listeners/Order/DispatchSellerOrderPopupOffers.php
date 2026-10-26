<?php

namespace App\Listeners\Order;

use App\Events\Order\OrderPlaced;
use App\Models\SellerOrder;
use App\Services\SellerOrderPopupOfferService;
use Illuminate\Contracts\Queue\ShouldQueue;

class DispatchSellerOrderPopupOffers implements ShouldQueue
{
    public bool $afterCommit = true;

    public function handle(OrderPlaced $event): void
    {
        SellerOrder::query()
            ->where('order_id', $event->order->id)
            ->pluck('seller_id')
            ->unique()
            ->each(fn ($sellerId) => app(SellerOrderPopupOfferService::class)->dispatchPendingOrders((int) $sellerId));
    }
}
