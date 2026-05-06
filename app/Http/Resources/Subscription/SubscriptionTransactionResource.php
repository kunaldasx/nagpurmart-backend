<?php

namespace App\Http\Resources\Subscription;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'seller_id' => $this->seller_id,
            'seller_subscription_id' => $this->seller_subscription_id,
            'plan_id' => $this->plan_id,
            'payment_gateway' => (string) $this->payment_gateway,
            'transaction_id' => $this->transaction_id,
            'amount' => $this->amount !== null ? (float) $this->amount : 0.0,
            'status' => (string) $this->status,
            'created_at' => optional($this->created_at)->format('d M Y H:i:s'),
        ];
    }
}
