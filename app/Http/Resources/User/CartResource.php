<?php

namespace App\Http\Resources\User;

use App\Http\Resources\DeliveryZoneResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $cart = $this['cart'] ?? $this;
        return [
            'id' => $cart->id,
            'uuid' => $cart->uuid,
            'user_id' => $cart->user_id,
            'items_count' => $cart->items->count(),
            'total_quantity' => $cart->items->sum('quantity'),
            'items' => CartItemResource::collection($cart->items),
            'payment_summary' => $this->when(isset($cart->payment_summary), function() use ($cart) {
                return new PaymentSummaryResource($cart->payment_summary);
            }),
            'removed_items' => $this['removed_items'] ?? [],
            'removed_count' => $this['removed_count'],
            'delivery_zone' =>  $this['delivery_zone'],
            'created_at' => $cart->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $cart->updated_at?->format('Y-m-d H:i:s')
        ];
    }
}
