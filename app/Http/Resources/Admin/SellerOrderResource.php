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
            'bag' => $this->whenLoaded('bag', function () {
                return [
                    'id' => $this->bag->id,
                    'barcode' => $this->bag->barcode,
                    'assigned_at' => $this->bag->assigned_at?->toISOString(),
                ];
            }),

            // Relationships
            'order' => $this->whenLoaded('order', function() {
                return [
                    'id' => $this->order->id,
                    'order_number' => $this->order->order_number,
                    'uuid' => $this->order->uuid,
                    'email' => $this->order->email,
                    'payment_method' => $this->order->payment_method,
                    'order_mode' => $this->order->order_mode ?? 'regular',
                    'delivery_date' => $this->order->delivery_date?->format('Y-m-d'),
                    'delivery_time_slot_id' => $this->order->delivery_time_slot_id,
                    'delivery_time_slot' => new \App\Http\Resources\DeliveryTimeSlotResource($this->order->deliveryTimeSlot),
                    'payment_status' => $this->order->payment_status,
                    'status' => $this->order->status,
                    'billing_name' => $this->order->billing_name,
                    'billing_phone' => $this->order->billing_phone,
                    'billing_address_1' => $this->order->billing_address_1,
                    'billing_address_2' => $this->order->billing_address_2,
                    'billing_city' => $this->order->billing_city,
                    'billing_state' => $this->order->billing_state,
                    'billing_zip' => $this->order->billing_zip,
                    'billing_country' => $this->order->billing_country,
                    'customer_gstin' => $this->order->customer_gstin,
                    'customer_gstin_legal_name' => $this->order->customer_gstin_legal_name,
                    'customer_gstin_state_code' => $this->order->customer_gstin
                        ? substr($this->order->customer_gstin, 0, 2)
                        : null,
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
