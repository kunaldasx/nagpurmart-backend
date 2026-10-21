<?php

namespace App\Jobs;

use App\Services\DeliveryBoyOrderOfferService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExpireDeliveryBoyOrderOffer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $offerId)
    {
    }

    public function handle(DeliveryBoyOrderOfferService $offerService): void
    {
        $offerService->expire($this->offerId);
    }
}