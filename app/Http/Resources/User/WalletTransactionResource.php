<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletTransactionResource extends JsonResource
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
            'wallet_id' => $this->wallet_id,
            'user_id' => $this->user_id,
            'order_id' => $this->order_id,
            'store_id' => $this->store_id,
            'transaction_type' => $this->transaction_type,
            'payment_method' => $this->payment_method,
            'amount' => $this->amount,
            'currency_code' => $this->currency_code,
            'status' => $this->status,
            'transaction_reference' => $this->transaction_reference,
            'description' => $this->description,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

}
