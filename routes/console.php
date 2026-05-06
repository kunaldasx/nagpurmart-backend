<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Cache;
use App\Models\SellerSubscription;
use App\Enums\Subscription\SellerSubscriptionStatusEnum;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule cashback processing to run daily at 2 AM
Schedule::command('cashback:process')->dailyAt('02:00');

// Command: Expire seller subscriptions whose end_date has passed
Artisan::command('subscription:expire', function () {
    $now = now();
    $expiredCount = 0;
    $affectedSellerIds = [];

    // Process in chunks to avoid memory issues
    SellerSubscription::where('status', SellerSubscriptionStatusEnum::ACTIVE())
        ->whereNotNull('end_date')
        ->where('end_date', '<=', $now)
        ->orderBy('id')
        ->chunkById(500, function ($subs) use (&$expiredCount, &$affectedSellerIds) {
            $ids = $subs->pluck('id')->all();
            $sellerIds = $subs->pluck('seller_id')->unique()->values()->all();

            if (!empty($ids)) {
                SellerSubscription::whereIn('id', $ids)
                    ->update(['status' => SellerSubscriptionStatusEnum::EXPIRED()]);
                $expiredCount += count($ids);
                $affectedSellerIds = array_values(array_unique(array_merge($affectedSellerIds, $sellerIds)));
            }
        });

    // Invalidate cache for affected sellers' current subscription
    foreach ($affectedSellerIds as $sellerId) {
        Cache::forget(SellerSubscription::cacheKeyForCurrent((int)$sellerId));
    }
    Log::info("Expired {$expiredCount} subscription(s) as of {$now->toDateTimeString()}.");
    $this->info("Expired {$expiredCount} subscription(s) as of {$now->toDateTimeString()}.");
})->purpose('Expire subscriptions when their timeline is over (end_date passed)');

// Schedule: run the expiration check hourly
Schedule::command('subscription:expire')->hourly();
