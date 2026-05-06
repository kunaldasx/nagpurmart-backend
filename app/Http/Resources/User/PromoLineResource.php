<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PromoLineResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'promo_id' => $this->promo_id,
            'promo_code' => $this->promo_code,
            'discount_amount' => $this->discount_amount,
            'cashback_flag' => $this->cashback_flag,
            'is_awarded' => $this->is_awarded,
            'promo' => $this->when($this->relationLoaded('promo'), function () {
                return [
                    'id' => $this->promo->id,
                    'title' => $this->promo->title ?? null,
                    'description' => $this->promo->description ?? null,
                ];
            }),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
