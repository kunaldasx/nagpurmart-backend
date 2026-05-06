<?php

namespace App\Http\Resources;

use App\Models\OrderItem;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderSellerFeedbackResource extends JsonResource
{
    public function toArray($request): array
    {
        $feedback = OrderItem::scopeSellerFeedback(orderId: $this->order_id, sellerId: $this->seller_id);
        return [
            'seller_id' => $this->seller_id,
            'is_feedback_given' => !empty($feedback),
            'feedback' => $feedback ?? []
        ];
    }
}
