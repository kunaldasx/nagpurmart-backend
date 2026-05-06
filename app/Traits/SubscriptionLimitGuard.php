<?php

namespace App\Traits;

use App\Enums\SettingTypeEnum;
use App\Enums\Subscription\SellerSubscriptionStatusEnum;
use App\Models\Setting;
use App\Models\SellerSubscription;
use App\Services\SubscriptionUsageService;
use App\Types\Api\ApiResponseType;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Carbon\Carbon;

trait SubscriptionLimitGuard
{
    /**
     * If multivendor and limit exceeded, return a standardized 422 response. Otherwise null.
     */
    protected function ensureCanUseOrError(int $sellerId, string $key): ?JsonResponse
    {
        if (!Setting::isSystemVendorTypeMultiple()) {
            return null; // No limits enforced in single-vendor mode
        }

        // If subscription feature is disabled or not decided, do not enforce limits
        if (!Setting::isSubscriptionEnabled()) {
            return null;
        }

        // If seller does not have an active/pending subscription, block usage entirely
        if (!$this->hasActiveSubscription($sellerId)) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.seller_not_subscribed'),
                data: [],
                status: 422
            );
        }

        /** @var SubscriptionUsageService $subscription */
        $subscription = app(SubscriptionUsageService::class);
        if ($subscription->canUse($sellerId, $key)) {
            return null;
        }

        $limit = $subscription->getLimit($sellerId, $key);
        $used = $subscription->getUsage($sellerId, $key);

        $key = Str::ucfirst(Str::replace("_", " ", $key));
        return ApiResponseType::sendJsonResponse(
            success: false,
            message: __('labels.subscription_limit_exceeded', [
                'key' => $key,
                'limit' => $limit,
                'used' => $used,
                'remaining' => max(0, $limit - $used),
            ]),
            data: [
                'key' => $key,
                'limit' => $limit,
                'used' => $used,
                'remaining' => max(0, $limit - $used),
            ],
            status: 422
        );
    }

    /**
     * Record usage if multivendor.
     */
    protected function recordUsageIfMultivendor(int $sellerId, string $key, int $count = 1): void
    {
        if (!Setting::isSystemVendorTypeMultiple()) {
            return;
        }
        // Skip recording usage when subscription feature is disabled
        if (!Setting::isSubscriptionEnabled()) {
            return;
        }
        // Skip recording when seller has no active/pending subscription
        if (!$this->hasActiveSubscription($sellerId)) {
            return;
        }
        app(SubscriptionUsageService::class)->recordUsageOrFail($sellerId, $key, $count);
    }

    /**
     * Reduce usage if multivendor.
     */
    protected function reduceUsageIfMultivendor(int $sellerId, string $key, int $count = 1): void
    {
        if (!Setting::isSystemVendorTypeMultiple()) {
            return;
        }
        // Skip reducing usage when subscription feature is disabled
        if (!Setting::isSubscriptionEnabled()) {
            return;
        }
        // Skip reducing when seller has no active/pending subscription
        if (!$this->hasActiveSubscription($sellerId)) {
            return;
        }
        app(SubscriptionUsageService::class)->reduceUsage($sellerId, $key, $count);
    }


    /**
     * Determine if the seller currently has an active or pending subscription within date range.
     */
    private function hasActiveSubscription(int $sellerId): bool
    {
        try {
            $now = Carbon::now();
            return SellerSubscription::where('seller_id', $sellerId)
                ->whereIn('status', [
                    SellerSubscriptionStatusEnum::ACTIVE(),
                    SellerSubscriptionStatusEnum::PENDING(),
                ])
                ->where(function ($q) use ($now) {
                    $q->whereNull('start_date')->orWhere('start_date', '<=', $now);
                })
                ->where(function ($q) use ($now) {
                    $q->whereNull('end_date')->orWhere('end_date', '>=', $now);
                })
                ->exists();
        } catch (\Throwable) {
            // On any error, assume no active subscription
            return false;
        }
    }
}
