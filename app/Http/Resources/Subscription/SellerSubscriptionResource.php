<?php

namespace App\Http\Resources\Subscription;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SellerSubscriptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'seller_id' => $this->seller_id,
            'plan_id' => $this->plan_id,
            'status' => (string)$this->status,
            'start_date' => optional($this->start_date)->format('d M Y H:i:s'),
            'end_date' => optional($this->end_date)->format('d M Y H:i:s'),
            'price_paid' => $this->price_paid !== null ? (float)$this->price_paid : 0.0,

            // Related entities
            'plan' => $this->whenLoaded('plan', function () {
                return new SubscriptionPlanResource($this->plan);
            }),

            'snapshot' => $this->whenLoaded('snapshot', function () {
                // Normalize snapshot limits: if a limit value is null, represent it as -1
                $limits = $this->snapshot->limits_json ?? [];
                if (is_array($limits)) {
                    $limits = collect($limits)
                        ->map(function ($v) {
                            return $v === null ? -1 : (int) $v;
                        })
                        ->toArray();
                }
                return [
                    'plan_name' => $this->snapshot->plan_name,
                    'price' => (float)$this->snapshot->price,
                    'duration_days' => $this->snapshot->duration_days !== null ? (int)$this->snapshot->duration_days : null,
                    'limits' => $limits,
                ];
            }),

            'transactions' => $this->whenLoaded('transactions', function () {
                return SubscriptionTransactionResource::collection($this->transactions);
            }),
        ];
    }
}
