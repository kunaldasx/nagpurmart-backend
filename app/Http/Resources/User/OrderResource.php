<?php

namespace App\Http\Resources\User;

use App\Http\Resources\OrderSellerFeedbackResource;
use App\Http\Resources\User\PromoLineResource;
use App\Enums\Order\OrderItemStatusEnum;
use App\Services\DeliveryBoyService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isDeliveryFeedbackGiven = DeliveryBoyService::checkDeliveryBoyFeedbackByOrderId(orderId: $this->id, deliveryBoyId: $this->delivery_boy_id);
        if ($isDeliveryFeedbackGiven) {
            $deliveryFeedback = DeliveryBoyService::getDeliveryBoyFeedbackByOrderId(orderId: $this->id, deliveryBoyId: $this->delivery_boy_id);
        }

        // For display only: include cancelled/rejected items amount back into subtotal and final_total
        // The stored order totals exclude these items, but the API should show amounts including them.
        $cancelledRejectedAmount = (float) ($this->items()
            ->whereIn('status', [
                OrderItemStatusEnum::REJECTED(),
                OrderItemStatusEnum::CANCELLED(),
            ])
            ->sum('subtotal'));

        $displaySubtotal = (float) $this->subtotal + $cancelledRejectedAmount;
        $displayFinalTotal = (float) $this->final_total + $cancelledRejectedAmount;
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'slug' => $this->slug,
            'user_id' => $this->user_id,
            'email' => $this->email,
            'currency_code' => $this->currency_code,
            'currency_rate' => $this->currency_rate,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'status' => $this->status,
            'invoice' => url('order-invoice?id=' . $this->uuid) ?? "",
            'fulfillment_type' => $this->fulfillment_type,
            'is_rush_order' => $this->is_rush_order,
            'estimated_delivery_time' => $this->estimated_delivery_time,
            'delivery_time_slot_id' => $this->delivery_time_slot_id,
            'delivery_boy_id' => $this->delivery_boy_id,
            'delivery_boy_name' => $this->deliveryBoy->full_name ?? "",
            'delivery_boy_phone' => (float)($this->deliveryBoy->user->mobile ?? 0),
            'delivery_boy_profile' => $this->deliveryBoy->user->profile_image ?? "",
            'is_delivery_feedback_given' => $isDeliveryFeedbackGiven,
            'delivery_feedback' => $deliveryFeedback ?? null,
            'wallet_balance' => $this->wallet_balance,
            'promo_code' => $this->promo_code,
            'promo_discount' => $this->promo_discount,
            'gift_card' => $this->gift_card,
            'gift_card_discount' => $this->gift_card_discount,
            'delivery_charge' => $this->delivery_charge,
            'handling_charges' => $this->handling_charges,
            'per_store_drop_off_fee' => $this->per_store_drop_off_fee,
            // Display values include cancelled/rejected items for API visibility only
            'subtotal' => (string)$displaySubtotal,
            'total_payable' => $this->total_payable,
            'final_total' => (string)$displayFinalTotal,

            // Billing info
//            'billing_name' => $this->billing_name,
//            'billing_address_1' => $this->billing_address_1,
//            'billing_address_2' => $this->billing_address_2,
//            'billing_landmark' => $this->billing_landmark,
//            'billing_zip' => $this->billing_zip,
//            'billing_phone' => $this->billing_phone,
//            'billing_address_type' => $this->billing_address_type,
//            'billing_latitude' => $this->billing_latitude,
//            'billing_longitude' => $this->billing_longitude,
//            'billing_city' => $this->billing_city,
//            'billing_state' => $this->billing_state,
//            'billing_country' => $this->billing_country,
//            'billing_country_code' => $this->billing_country_code,

            // Shipping info
            'shipping_name' => $this->shipping_name,
            'shipping_address_1' => $this->shipping_address_1,
            'shipping_address_2' => $this->shipping_address_2,
            'shipping_landmark' => $this->shipping_landmark,
            'shipping_zip' => $this->shipping_zip,
            'shipping_phone' => $this->shipping_phone,
            'shipping_address_type' => $this->shipping_address_type,
            'shipping_latitude' => $this->shipping_latitude,
            'shipping_longitude' => $this->shipping_longitude,
            'shipping_city' => $this->shipping_city,
            'shipping_state' => $this->shipping_state,
            'shipping_country' => $this->shipping_country,
            'shipping_country_code' => $this->shipping_country_code,
            'order_note' => $this->order_note ?? '',

            // Relationships
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'seller_feedbacks' => OrderSellerFeedbackResource::collection($this->whenLoaded('sellerOrders')),
            'promo_line' => new PromoLineResource($this->whenLoaded('promoLine')),
            'user' => $this->when($this->relationLoaded('user'), function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'payment_response' => $this->payment_response ?? null,
        ];
    }
}
