<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemReturnResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'order_item_id' => $this->order_item_id,
            'order_id' => $this->order_id,
            'user_id' => $this->user_id,
            'seller_id' => $this->seller_id,
            'store_id' => $this->store_id,
            'delivery_boy_id' => $this->delivery_boy_id,

            'reason' => $this->reason,
            'seller_comment' => $this->seller_comment,
            'images' => $this->images,
            'refund_amount' => (float) $this->refund_amount,

            'pickup_status' => $this->pickup_status,
            'return_status' => $this->return_status,

            'seller_approved_at' => optional($this->seller_approved_at)->toDateTimeString(),
            'picked_up_at' => optional($this->picked_up_at)->toDateTimeString(),
            'received_at' => optional($this->received_at)->toDateTimeString(),
            'refund_processed_at' => optional($this->refund_processed_at)->toDateTimeString(),

            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
