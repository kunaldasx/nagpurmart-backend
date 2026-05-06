<?php

namespace App\Http\Middleware;

use App\Enums\Subscription\SellerSubscriptionStatusEnum;
use App\Enums\Subscription\SubscriptionDurationTypeEnum;
use App\Models\SellerSubscription;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureSellerHasSubscription
{
    /**
     * If the authenticated seller has no active/pending subscription,
     * automatically subscribe them to the default plan.
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            $user = Auth::user();
            if ($user && method_exists($user, 'seller')) {
                $seller = $user->seller();

                if ($seller) {
                    $sellerId = is_object($seller) && method_exists($seller, 'getKey') ? $seller->getKey() : (is_array($seller) && isset($seller['id']) ? $seller['id'] : null);
                    // Some projects define seller() as relation getter returning model directly
                    if (is_null($sellerId) && is_object($seller) && property_exists($seller, 'id')) {
                        $sellerId = $seller->id;
                    }

                    // If relation is defined as function returning BelongsTo, call ->first()
                    if (is_null($sellerId) && is_callable([$user, 'seller'])) {
                        $sellerModel = $user->seller()->first();
                        $sellerId = $sellerModel?->id;
                    }

                    if ($sellerId) {
                        $now = Carbon::now();
                        $existing = SellerSubscription::where('seller_id', $sellerId)
                            ->whereIn('status', [
                                SellerSubscriptionStatusEnum::ACTIVE(),
                                SellerSubscriptionStatusEnum::PENDING(),
                            ])
                            ->where(function ($q) use ($now) {
                                $q->whereNull('end_date')->orWhere('end_date', '>=', $now);
                            })
                            ->first();

                        if (!$existing) {
                            $defaultPlan = SubscriptionPlan::where('is_default', true)->first();
                            if ($defaultPlan) {
                                $startDate = $now;
                                $endDate = null;
                                if ((string)$defaultPlan->duration_type === (string)SubscriptionDurationTypeEnum::DAYS() && !empty($defaultPlan->duration_days)) {
                                    $endDate = (clone $now)->addDays((int)$defaultPlan->duration_days);
                                }

                                // Create a free, active subscription without transaction
                                SellerSubscription::create([
                                    'seller_id'  => $sellerId,
                                    'plan_id'    => $defaultPlan->id,
                                    'status'     => SellerSubscriptionStatusEnum::ACTIVE(),
                                    'start_date' => $startDate,
                                    'end_date'   => $endDate,
                                    'price_paid' => 0,
                                ]);
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // Silently ignore to avoid blocking request flow
        }

        return $next($request);
    }
}
