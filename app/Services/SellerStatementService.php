<?php

namespace App\Services;

use App\Enums\Seller\SellerSettlementStatusEnum;
use App\Enums\Seller\SellerSettlementTypeEnum;
use App\Models\OrderItem;
use App\Models\OrderItemReturn;
use App\Models\SellerStatement;
use Illuminate\Support\Arr;

class SellerStatementService
{
    /**
     * Create a generic seller statement entry.
     * Includes settlement status fields. Defaults to pending.
     *
     * @param array $data
     *  - seller_id: int (required)
     *  - entry_type: credit|debit (required)
     *  - amount: float (required)
     *  - currency_code: string|null
     *  - order_id: int|null
     *  - order_item_id: int|null
     *  - return_id: int|null
     *  - reference_type: string|null
     *  - reference_id: string|int|null
     *  - description: string|null
     *  - meta: array|null
     *  - posted_at: \DateTimeInterface|string|null
     *  - settlement_status: pending|settled (optional)
     *  - settled_at: \DateTimeInterface|string|null (optional)
     *  - settlement_reference: string|null (optional)
     */
    public function addEntry(array $data): SellerStatement
    {
        $payload = [
            'seller_id' => $data['seller_id'],
            'entry_type' => $data['entry_type'],
            'amount' => $data['amount'],
            'currency_code' => Arr::get($data, 'currency_code'),
            'order_id' => Arr::get($data, 'order_id'),
            'order_item_id' => Arr::get($data, 'order_item_id'),
            'return_id' => Arr::get($data, 'return_id'),
            'reference_type' => Arr::get($data, 'reference_type'),
            'reference_id' => Arr::get($data, 'reference_id'),
            'description' => Arr::get($data, 'description'),
            'meta' => Arr::get($data, 'meta'),
            'posted_at' => Arr::get($data, 'posted_at') ?? now(),
            'settlement_status' => Arr::get($data, 'settlement_status', SellerSettlementStatusEnum::PENDING()),
            'settled_at' => Arr::get($data, 'settled_at'),
            'settlement_reference' => Arr::get($data, 'settlement_reference'),
        ];

        return SellerStatement::create($payload);
    }

    /**
     * Mark a statement as settled.
     */
    public function markSettled(SellerStatement $statement, ?string $reference = null, $settledAt = null): SellerStatement
    {
        $statement->update([
            'settlement_status' => SellerSettlementStatusEnum::SETTLED(),
            'settled_at' => $settledAt ? \Carbon\Carbon::parse($settledAt) : now(),
            'settlement_reference' => $reference,
        ]);

        return $statement->refresh();
    }

    /**
     * Record commission (debit) for an order item going to admin from seller earnings
     */
    public function recordOrderItemCommission(OrderItem $orderItem, float $commissionAmount, ?string $currency = null, ?array $meta = null): SellerStatement
    {
        return $this->addEntry([
            'seller_id' => $orderItem->store->seller_id,
            'entry_type' => SellerSettlementTypeEnum::DEBIT(),
            'amount' => $commissionAmount,
            'currency_code' => $currency,
            'order_id' => $orderItem->order_id,
            'order_item_id' => $orderItem->id,
            'reference_type' => 'order_item_commission',
            'reference_id' => $orderItem->id,
            'description' => 'Commission for Order Item #' . $orderItem->id,
            'meta' => $meta,
        ]);
    }

    /**
     * Record refund/return impact as credit or debit depending on business rule.
     * Typically, when a return is processed, seller may be debited refund amount.
     */
    public function recordReturnRefund(OrderItemReturn $return, float $amount, bool $isCredit = false, ?string $currency = null, ?array $meta = null): SellerStatement
    {
        return $this->addEntry([
            'seller_id' => $return->seller_id,
            'entry_type' => $isCredit ? SellerSettlementTypeEnum::CREDIT() : SellerSettlementTypeEnum::DEBIT(),
            'amount' => $amount,
            'currency_code' => $currency,
            'order_id' => $return->order_id,
            'order_item_id' => $return->order_item_id,
            'return_id' => $return->id,
            'reference_type' => 'order_item_return',
            'reference_id' => $return->id,
            'description' => ($isCredit ? 'Credit' : 'Debit') . ' for Return #' . $return->id,
            'meta' => $meta,
        ]);
    }
}
