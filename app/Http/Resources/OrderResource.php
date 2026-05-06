<?php

namespace App\Http\Resources;

use App\Enums\SpatieMediaCollectionName;
use App\Http\Resources\User\PromoLineResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Check if this is a SellerOrder or an Order
        $isSellerOrder = get_class($this->resource) === 'App\Models\SellerOrder';

        if ($isSellerOrder) {
            return [
                'id' => $this->id,
                'uuid' => $this->order->uuid,
                'email' => $this->order->email,
                'status' => $this->order->status,
                'payment_method' => $this->order->payment_method,
                'payment_status' => $this->order->payment_status,
                'total_price' => $this->total_price,

                // Customer information
                'billing_name' => $this->order->billing_name,
                'billing_phone' => $this->order->billing_phone,

                // Shipping information
                'shipping_name' => $this->order->shipping_name,
                'shipping_address_1' => $this->order->shipping_address_1,
                'shipping_address_2' => $this->order->shipping_address_2,
                'shipping_landmark' => $this->order->shipping_landmark,
                'shipping_city' => $this->order->shipping_city,
                'shipping_state' => $this->order->shipping_state,
                'shipping_zip' => $this->order->shipping_zip,
                'shipping_country' => $this->order->shipping_country,
                'shipping_phone' => $this->order->shipping_phone,
                'order_note' => $this->order->order_note,
                'is_rush_order' => $this->order->is_rush_order,
                'promo_line' => new PromoLineResource($this->whenLoaded('promoLine')),

                // Items
                'items' => $this->whenLoaded('items', function () {
                    return $this->items->map(function ($item) {
                        $attachments = [];
                        try {
                            $mediaItems = $item->orderItem->getMedia(SpatieMediaCollectionName::ORDER_ITEM_ATTACHMENTS());
                            foreach ($mediaItems as $media) {
                                $attachments[] = $media->getUrl();
                            }
                        } catch (\Throwable $e) {
                            // Silently ignore if media library not set up for this resource
                        }
                        return [
                            'id' => $item->id,
                            'attachments' => $attachments,
                            'orderItem' => [
                                'id' => $item->orderItem->id,
                                'status' => $item->orderItem->status,
                                'status_formatted' => Str::ucfirst(Str::replace("_", " ", $item->orderItem->status)),
                            ],
                            'product' => $item->product ? [
                                'id' => $item->product->id,
                                'title' => $item->product->title,
                            ] : null,
                            'variant' => $item->variant ? [
                                'id' => $item->variant->id,
                                'title' => $item->variant->title,
                            ] : null,
                            'store' => $item->store ? [
                                'id' => $item->store->id,
                                'name' => $item->store->name,
                            ] : null,
                            'price' => $item->price,
                            'tax_amount' => $item->orderItem?->tax_amount,
                            'sub_total' => $item->orderItem?->subtotal,
                            'quantity' => $item->quantity,
                            'subtotal' => $item->price * $item->quantity,
                        ];
                    });
                }),

                'created_at' => $this->created_at?->format('M d, Y h:i A'),
            ];
        }
        // This is a regular Order

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'email' => $this->email,
            'status' => $this->status,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'promo_code' => $this->promo_code,
            'promo_discount' => $this->promo_discount,
            'wallet_balance' => $this->wallet_balance,
            'subtotal' => $this->subtotal,
            'delivery_charge' => $this->delivery_charge,
            'handling_charges' => $this->handling_charges,
            'per_store_drop_off_fee' => $this->per_store_drop_off_fee,
            'total_payable' => $this->total_payable,
            'final_total' => $this->final_total,

            // Customer information
            'billing_name' => $this->billing_name,
            'billing_phone' => $this->billing_phone,

            // Shipping information
            'shipping_name' => $this->shipping_name,
            'shipping_address_1' => $this->shipping_address_1,
            'shipping_address_2' => $this->shipping_address_2,
            'shipping_landmark' => $this->shipping_landmark,
            'shipping_city' => $this->shipping_city,
            'shipping_state' => $this->shipping_state,
            'shipping_zip' => $this->shipping_zip,
            'shipping_country' => $this->shipping_country,
            'shipping_phone' => $this->shipping_phone,
            'order_note' => $this['order_note'],
            'is_rush_order' => $this->is_rush_order,
            'promo_line' => new PromoLineResource($this->whenLoaded('promoLine')),

            // Items
            'items' => $this->whenLoaded('items', function () {
                return $this->items->map(function ($item) {
                    $attachments = [];
                    try {
                        $mediaItems = $item->getMedia(SpatieMediaCollectionName::ORDER_ITEM_ATTACHMENTS());
                        foreach ($mediaItems as $media) {
                            $attachments[] = $media->getUrl();
                        }
                    } catch (\Throwable $e) {
                        // Silently ignore if media library not set up for this resource
                    }
                    return [
                        'id' => $item->id,
                        'attachments' => $attachments,
                        'orderItem' => [
                            'id' => $item->id,
                            'status' => $item->status,
                            'status_formatted' => Str::ucfirst(Str::replace("_", " ", $item->status)),
                        ],
                        'product' => $item->product ? [
                            'id' => $item->product->id,
                            'title' => $item->product->title,
                        ] : null,
                        'variant' => $item->variant ? [
                            'id' => $item->variant->id,
                            'title' => $item->variant->title,
                        ] : null,
                        'store' => $item->store ? [
                            'id' => $item->store->id,
                            'name' => $item->store->name,
                        ] : null,
                        'price' => $item->price,
                        'tax_amount' => $item->tax_amount,
                        'sub_total' => $item->subtotal,
                        'quantity' => $item->quantity,
                        'subtotal' => $item->price * $item->quantity,
                    ];
                });
            }),

            'created_at' => $this->created_at?->format('M d, Y h:i A'),
        ];
    }
}
