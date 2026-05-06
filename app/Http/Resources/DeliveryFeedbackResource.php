<?php

namespace App\Http\Resources;

use App\Http\Resources\DeliveryBoy\DeliveryBoyResource;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryFeedbackResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user' => new UserResource($this->whenLoaded('user')),
            'delivery_boy' => new DeliveryBoyResource($this->whenLoaded('deliveryBoy')),
            'order' => new OrderResource($this->whenLoaded('order')),
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'rating' => $this->rating,
            'created_at' => $this->created_at,
        ];
    }
}
