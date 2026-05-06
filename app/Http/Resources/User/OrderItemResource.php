<?php

namespace App\Http\Resources\User;

use App\Enums\SpatieMediaCollectionName;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isReviewExist = OrderService::checkUserReviewExistByOrderItemId($this->id);
        if ($isReviewExist) {
            $userReview = OrderService::getUserReviewByOrderItemId($this->id);
        }
        $sellerId = $this->store?->seller?->id;
        $sellerName = $this->store?->seller?->user?->name;

        $attachments = [];
        try {
            $mediaItems = $this->getMedia(SpatieMediaCollectionName::ORDER_ITEM_ATTACHMENTS());
            foreach ($mediaItems as $media) {
                $attachments[] = $media->getUrl();
            }
        } catch (\Throwable $e) {
            // Silently ignore if media library not set up for this resource
        }
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'product_id' => $this->product_id,
            'product_variant_id' => $this->product_variant_id,
            'store_id' => $this->store_id,
            'seller_id' => $sellerId,
            'seller_name' => $sellerName,
            'title' => $this->title,
            'variant_title' => $this->variant_title,
            'gift_card_discount' => $this->gift_card_discount,
            'admin_commission_amount' => $this->admin_commission_amount,
            'seller_commission_amount' => $this->seller_commission_amount,
            'commission_settled' => $this->commission_settled,
            'discounted_price' => $this->discounted_price,
            'promo_discount' => $this->promo_discount,
            'discount' => $this->discount,
            'tax_amount' => $this->tax_amount,
            'tax_percent' => $this->tax_percent,
            'sku' => $this->sku,
            'return_eligible' => $this->return_eligible,
            'return_deadline' => $this->return_deadline,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'subtotal' => $this->subtotal,
            'status' => $this->status,
            'otp' => $this->otp ?? null,
            'otp_verified' => $this->otp_verified ?? false,
            'is_user_review_given' => $isReviewExist,
            'user_review' => $userReview ?? null,
            'product' => $this->when($this->product, [
                'id' => $this->product->id ?? "N/A",
                'name' => $this->product->title ?? "N/A",
                'slug' => $this->product->slug ?? "N/A",
                'is_returnable' => (bool)$this->product?->is_returnable ?? false,
                'returnable_days' => (int)$this->product?->returnable_days ?? 0,
                'is_cancelable' => (bool)$this->product?->is_cancelable ?? false,
                'cancelable_till' => $this->product?->cancelable_till ?? null,
                'image' => $this->product->main_image ?? null,
                'requires_otp' => $this->product->requires_otp ?? false,
            ]),
            'variant' => $this->when($this->variant, [
                'id' => $this->variant->id ?? "N/A",
                'title' => $this->variant->title ?? "N/A",
                'slug' => $this->variant->slug ?? "N/A",
                'image' => $this->variant->image ?? null,
            ]),
            'store' => $this->when($this->store, [
                'id' => $this->store->id ?? "N/A",
                'name' => $this->store->name ?? "N/A",
                'slug' => $this->store->slug ?? "N/A",
            ]),
            'returns' => OrderItemReturnResource::collection($this->whenLoaded('returns')),
            'attachments' => $attachments,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s')
        ];
    }
}
