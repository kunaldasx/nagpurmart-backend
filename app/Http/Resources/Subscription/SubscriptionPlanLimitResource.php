<?php

namespace App\Http\Resources\Subscription;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\SubscriptionPlanLimit */
class SubscriptionPlanLimitResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        // Represent as an associative pair where key is the array key
        return [
            'key' => $this->key,
            // If value is null, represent it as -1 per requirement
            'value' => $this->value === null ? -1 : (int) $this->value,
        ];
    }
}
