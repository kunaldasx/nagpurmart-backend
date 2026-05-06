<?php

namespace App\Http\Resources\DeliveryBoy;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryBoyOrderItemResource extends JsonResource
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
            'product_id' => $this->product_id,
            'product_variant_id' => $this->product_variant_id,
            'store_id' => $this->store_id,
            'title' => $this->title,
            'variant_title' => $this->variant_title,
            'gift_card_discount' => $this->gift_card_discount,
            'admin_commission_amount' => $this->admin_commission_amount,
            'seller_commission_amount' => $this->seller_commission_amount,
            'commission_settled' => $this->commission_settled,
            'discounted_price' => $this->discounted_price,
            'discount' => $this->discount,
            'tax_amount' => $this->tax_amount,
            'tax_percent' => $this->tax_percent,
            'sku' => $this->sku,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'subtotal' => $this->subtotal,
            'status' => $this->status,
            'otp_verified' => $this->otp_verified ?? false,
            'product' => $this->when($this->product, [
                'id' => $this->product->id ?? null,
                'name' => $this->product->name ?? null,
                'slug' => $this->product->slug ?? null,
                'image' => $this->product->main_image ?? null,
                'requires_otp' => $this->product->requires_otp ?? false,
            ]),
            'variant' => $this->when($this->variant, [
                'id' => $this->variant->id ?? null,
                'title' => $this->variant->title ?? null,
                'slug' => $this->variant->slug ?? null,
                'image' => $this->variant->image ?? null,
            ]),
            'store' => $this->when($this->store, [
                'id' => $this->store->id ?? 0,
                'name' => $this->store->name ?? "",
                'slug' => $this->store->slug ?? "",
            ]),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s')
        ];
    }
}
