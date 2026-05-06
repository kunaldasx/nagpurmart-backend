<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SellerFeedbackResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'seller_id' => $this->seller_id,
            'order_id' => $this->order_id,
            'order_item_id' => $this->order_item_id,
            'store_id' => $this->store_id,
            'rating' => $this->rating,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'user' => [
                'id' => $this->user->id ?? null,
                'name' => $this->user->name ?? null,
            ],
            'seller' => [
                'id' => $this->seller->id ?? null,
                'name' => $this->seller->user->name ?? null,
            ],
            'order' => $this->when($this->order_id, [
                'id' => $this->order->id ?? null,
                'order_number' => $this->order->order_number ?? null,
            ]),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
