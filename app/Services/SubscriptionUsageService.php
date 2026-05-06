<?php

namespace App\Services;

use App\Enums\Subscription\SubscriptionPlanKeyEnum;
use App\Models\SellerSubscription;
use App\Models\SellerSubscriptionUsage;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;

class SubscriptionUsageService
{
    /**
     * Get active seller subscription with plan limits map [key => int]
     */
    public function getSellerPlanLimits(int $sellerId): array
    {
        $now = Carbon::now();
        $subscription = SellerSubscription::with(['snapshot'])
            ->where('seller_id', $sellerId)
            ->where('status', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $now);
            })
            ->orderByDesc('id')
            ->first();

        $limits = (array)($subscription->snapshot->limits_json ?? []);

        // Ensure all known keys exist with default -1 (unlimited) if not set in plan
        foreach (SubscriptionPlanKeyEnum::values() as $key) {
            if (!array_key_exists($key, $limits)) {
                $limits[$key] = -1;
            }
        }

        return $limits;
    }

    /**
     * Get limits for a specific subscription plan by plan id.
     * Returns associative array: [key => int|null]. When null, it means unlimited.
     */
    public function getPlanLimitsForPlan(int $planId): array
    {
        $plan = SubscriptionPlan::with('limits')->find($planId);
        $limits = [];
        if ($plan && $plan->relationLoaded('limits')) {
            // Note: value can be null meaning unlimited
            $limits = $plan->limits
                ->pluck('value', 'key')
                ->toArray();
        }

        // Ensure all known keys exist (null indicates unlimited when not present)
        foreach (SubscriptionPlanKeyEnum::values() as $key) {
            if (!array_key_exists($key, $limits)) {
                $limits[$key] = null; // unlimited
            }
        }

        return $limits;
    }

    public function getLimit(int $sellerId, string $key): int
    {
        $limits = $this->getSellerPlanLimits($sellerId);
        return (int)($limits[$key] ?? -1);
    }

    public function getUsage(int $sellerId, string $key): int
    {
        return SellerSubscriptionUsage::getUsage($sellerId, $key);
    }

    /**
     * Check if seller can use count more of key under plan limits.
     */
    public function canUse(int $sellerId, string $key, int $count = 1): bool
    {
        $limit = $this->getLimit($sellerId, $key);
        if ($limit < 0) {
            // unlimited
            return true;
        }
        $used = $this->getUsage($sellerId, $key);
        return ($used + $count) <= $limit;
    }

    /**
     * Record usage if within limit, otherwise return array with error info.
     */
    public function recordUsageOrFail(int $sellerId, string $key, int $count = 1): array
    {
        if ($this->canUse($sellerId, $key, $count)) {
            $usage = SellerSubscriptionUsage::recordUsage($sellerId, $key, $count);
            return [
                'success' => true,
                'usage' => $usage->used,
                'limit' => $this->getLimit($sellerId, $key),
            ];
        }

        $limit = $this->getLimit($sellerId, $key);
        $used = $this->getUsage($sellerId, $key);

        return [
            'success' => false,
            'error' => 'labels.subscription_limit_exceeded',
            'data' => [
                'key' => $key,
                'limit' => $limit,
                'used' => $used,
                'remaining' => max(0, $limit - $used),
            ],
        ];
    }

    public function reduceUsage(int $sellerId, string $key, int $count = 1): array
    {
        $usage = SellerSubscriptionUsage::reduceUsage($sellerId, $key, $count);
        return [
            'success' => true,
            'usage' => $usage->used,
            'limit' => $this->getLimit($sellerId, $key),
        ];
    }
    /**
     * Check if a seller is eligible for a given subscription plan BEFORE purchase.
     * The seller is eligible when all current usage is within the limits provided by the plan.
     * If a limit is null (or missing), it is treated as unlimited.
     *
     * Returns structure:
     * [
     *   'eligible' => bool,
     *   'details' => [
     *       key => [ 'used' => int, 'limit' => int|null, 'remaining' => int|null, 'ok' => bool ]
     *   ],
     *   'failing_keys' => string[]
     * ]
     */
    public function checkEligibilityForPlan(int $sellerId, int $planId): array
    {
        $limits = $this->getPlanLimitsForPlan($planId);
        $details = [];
        $failing = [];

        foreach (SubscriptionPlanKeyEnum::values() as $key) {
            $used = $this->getUsage($sellerId, $key);
            $limit = $limits[$key] ?? null; // null => unlimited
            if ($limit === null) {
                $ok = true;
                $remaining = null; // unlimited
            } else {
                $ok = $used <= (int)$limit;
                $remaining = max(0, ((int)$limit - $used));
            }

            if (!$ok) {
                $failing[] = $key;
            }

            $details[$key] = [
                'used' => (int)$used,
                'limit' => $limit === null ? null : (int)$limit,
                'remaining' => $limit === null ? null : $remaining,
                'ok' => $ok,
            ];
        }

        return [
            'eligible' => empty($failing),
            'details' => $details,
            'failing_keys' => $failing,
        ];
    }
}
