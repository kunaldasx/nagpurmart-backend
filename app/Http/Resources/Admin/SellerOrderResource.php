<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\Admin\SellerOrderItemResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SellerOrderResource extends JsonResource
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
            'seller_id' => $this->seller_id,
            'total_price' => $this->total_price,
            'status' => $this->status,

            // Relationships
            'order' => $this->whenLoaded('order', function() {
                return [
                    'id' => $this->order->id,
                    'uuid' => $this->order->uuid,
                    'email' => $this->order->email,
                    'payment_method' => $this->order->payment_method,
                    'payment_status' => $this->order->payment_status,
                    'status' => $this->order->status,
                    'billing_name' => $this->order->billing_name,
                    'billing_phone' => $this->order->billing_phone,
                    'shipping_name' => $this->order->shipping_name,
                    'shipping_address_1' => $this->order->shipping_address_1,
                    'shipping_address_2' => $this->order->shipping_address_2,
                    'shipping_landmark' => $this->order->shipping_landmark,
                    'shipping_city' => $this->order->shipping_city,
                    'shipping_state' => $this->order->shipping_state,
                    'shipping_zip' => $this->order->shipping_zip,
                    'shipping_country' => $this->order->shipping_country,
                    'shipping_phone' => $this->order->shipping_phone,
                    'created_at' => $this->order->created_at?->format('Y-m-d H:i:s'),
                ];
            }),
            'seller' => $this->whenLoaded('seller', function() {
                return [
                    'id' => $this->seller->id,
                    'name' => $this->seller->name,
                ];
            }),
            'items' => SellerOrderItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s')
        ];
    }
}
