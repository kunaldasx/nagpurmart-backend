<?php

namespace App\Http\Resources\Subscription;

use App\Enums\Subscription\SubscriptionPlanKeyEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\SubscriptionPlan */
class SubscriptionPlanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $limits = $this->whenLoaded('limits', function () {
            // Return as associative list: key => value (int)
            return $this->limits
                ->pluck('value', 'key')
                ->map(function ($v) {
                    // When limit value is null, treat it as -1 per requirement
                    return $v === null ? -1 : (int) $v;
                })
                ->toArray();
        }, []);

        foreach (SubscriptionPlanKeyEnum::values() as $key) {
            $limits[$key] = $limits[$key] ?? -1;
        }
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => (float) $this->price,
            'duration_type' => $this->duration_type,
            'duration_days' => $this->duration_days ? (int) $this->duration_days : null,
            'is_free' => (bool) $this->is_free,
            'is_default' => (bool) $this->is_default,
            'is_recommended' => (bool) $this->is_recommended,
            'status' => (bool) $this->status,
            'limits' => $limits,
        ];
    }
}
