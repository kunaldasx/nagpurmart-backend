<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderPaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id'              => $this->id,
            'uuid'            => $this->uuid,
            'order_id'        => $this->order_id,
            'user_id'         => $this->user_id,
            'transaction_id'  => $this->transaction_id,
            'amount'          => $this->amount,
            'currency'        => $this->currency,
            'payment_method'  => $this->payment_method,
            'payment_status'  => $this->payment_status,
            'message'         => $this->message,

            'payment_details' => [
                'id'                => $this->payment_details['id'] ?? null,
                'entity'            => $this->payment_details['entity'] ?? null,
                'amount'            => $this->payment_details['amount'] ?? null,
                'currency'          => $this->payment_details['currency'] ?? null,
                'status'            => $this->payment_details['status'] ?? null,
                'order_id'          => $this->payment_details['order_id'] ?? null,
                'invoice_id'        => $this->payment_details['invoice_id'] ?? null,
                'international'     => $this->payment_details['international'] ?? null,
                'method'            => $this->payment_details['method'] ?? null,
                'amount_refunded'   => $this->payment_details['amount_refunded'] ?? null,
                'refund_status'     => $this->payment_details['refund_status'] ?? null,
                'captured'          => $this->payment_details['captured'] ?? null,
                'description'       => $this->payment_details['description'] ?? null,
                'card_id'           => $this->payment_details['card_id'] ?? null,
                'bank'              => $this->payment_details['bank'] ?? null,
                'wallet'            => $this->payment_details['wallet'] ?? null,
                'vpa'               => $this->payment_details['vpa'] ?? null,
                'email'             => $this->payment_details['email'] ?? null,
                'contact'           => $this->payment_details['contact'] ?? null,
                'notes'             => $this->payment_details['notes'] ?? null,
                'fee'               => $this->payment_details['fee'] ?? null,
                'tax'               => $this->payment_details['tax'] ?? null,
                'error_code'        => $this->payment_details['error_code'] ?? null,
                'error_description' => $this->payment_details['error_description'] ?? null,
                'error_source'      => $this->payment_details['error_source'] ?? null,
                'error_step'        => $this->payment_details['error_step'] ?? null,
                'error_reason'      => $this->payment_details['error_reason'] ?? null,
                'acquirer_data'     => $this->payment_details['acquirer_data'] ?? null,
                'created_at'        => $this->payment_details['created_at'] ?? null,
                'reward'            => $this->payment_details['reward'] ?? null,
                'base_amount'       => $this->payment_details['base_amount'] ?? null,
            ],

            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
        ];
    }
}
