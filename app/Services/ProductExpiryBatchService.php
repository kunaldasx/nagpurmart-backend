<?php

namespace App\Services;

use App\Enums\StockInventoryTypeEnum;
use App\Models\OrderItemExpiryBatchAllocation;
use App\Models\StoreInventoryLog;
use App\Models\StoreProductExpiryBatch;
use App\Models\StoreProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductExpiryBatchService
{
    public const DEFAULT_WARNING_DAYS = 15;

    public function receive(
        int $storeProductVariantId,
        int $quantity,
        string $expiryDate,
        ?string $batchCode,
        ?int $userId,
        bool $adjustStock = true
    ): StoreProductExpiryBatch {
        return DB::transaction(function () use ($storeProductVariantId, $quantity, $expiryDate, $batchCode, $userId, $adjustStock) {
            $storeVariant = StoreProductVariant::whereKey($storeProductVariantId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($adjustStock) {
                if ($storeVariant->stock + $quantity > 2147483647) {
                    throw ValidationException::withMessages([
                        'quantity' => 'The resulting stock exceeds the supported inventory limit.',
                    ]);
                }
                $storeVariant->increment('stock', $quantity);
            } else {
                $trackedQuantity = $storeVariant->expiryBatches()->sum('quantity_remaining');
                if ($storeVariant->stock - $trackedQuantity < $quantity) {
                    throw ValidationException::withMessages([
                        'quantity' => 'There is not enough untracked stock to assign to this expiry batch.',
                    ]);
                }
            }

            $batch = $storeVariant->expiryBatches()->create([
                'batch_code' => $batchCode,
                'quantity_received' => $quantity,
                'quantity_remaining' => $quantity,
                'expiry_date' => $expiryDate,
                'created_by' => $userId,
            ]);

            if ($adjustStock) {
                $this->logInventoryChange(
                    $storeVariant,
                    StockInventoryTypeEnum::ADD(),
                    $quantity,
                    'Stock received into expiry batch ' . ($batchCode ?: "#{$batch->id}")
                );
            }

            return $batch;
        });
    }

    public function update(
        StoreProductExpiryBatch $batch,
        array $attributes,
        bool $adjustStock = true
    ): StoreProductExpiryBatch
    {
        return DB::transaction(function () use ($batch, $attributes, $adjustStock) {
            $storeVariant = StoreProductVariant::whereKey($batch->store_product_variant_id)
                ->lockForUpdate()
                ->firstOrFail();
            $batch = $storeVariant->expiryBatches()
                ->whereKey($batch->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (array_key_exists('quantity_remaining', $attributes)) {
                $newQuantity = (int) $attributes['quantity_remaining'];
                $outstandingSoldQuantity = (int) OrderItemExpiryBatchAllocation::query()
                    ->where('expiry_batch_id', $batch->id)
                    ->sum(DB::raw('quantity_consumed - quantity_restored'));
                $maximumRemaining = max(0, $batch->quantity_received - $outstandingSoldQuantity);

                if ($newQuantity > $maximumRemaining) {
                    throw ValidationException::withMessages([
                        'quantity_remaining' => 'Remaining quantity cannot exceed the received quantity less units still sold.',
                    ]);
                }

                $change = $newQuantity - $batch->quantity_remaining;
                if ($adjustStock && $storeVariant->stock + $change < 0) {
                    throw ValidationException::withMessages([
                        'quantity_remaining' => 'The stock adjustment would make total inventory negative.',
                    ]);
                }
                if ($adjustStock && $storeVariant->stock + $change > 2147483647) {
                    throw ValidationException::withMessages([
                        'quantity_remaining' => 'The resulting stock exceeds the supported inventory limit.',
                    ]);
                }
                if (!$adjustStock && $change > 0) {
                    $trackedQuantity = $storeVariant->expiryBatches()->sum('quantity_remaining');
                    if ($storeVariant->stock - $trackedQuantity < $change) {
                        throw ValidationException::withMessages([
                            'quantity_remaining' => 'There is not enough untracked stock to assign to this expiry batch.',
                        ]);
                    }
                }

                if ($adjustStock && $change !== 0) {
                    $storeVariant->increment('stock', $change);
                    $this->logInventoryChange(
                        $storeVariant,
                        $change > 0 ? StockInventoryTypeEnum::ADD() : StockInventoryTypeEnum::REMOVE(),
                        abs($change),
                        "Expiry batch {$batch->id} quantity adjustment"
                    );
                }

                $batch->quantity_remaining = $newQuantity;
            }

            if (array_key_exists('expiry_date', $attributes)) {
                $batch->expiry_date = $attributes['expiry_date'];
            }

            if (array_key_exists('batch_code', $attributes)) {
                $batch->batch_code = $attributes['batch_code'];
            }

            $batch->save();

            return $batch->refresh();
        });
    }

    private function logInventoryChange(
        StoreProductVariant $storeVariant,
        string $changeType,
        int $quantity,
        string $reason
    ): void {
        StoreInventoryLog::create([
            'store_id' => $storeVariant->store_id,
            'product_variant_id' => $storeVariant->product_variant_id,
            'change_type' => $changeType,
            'quantity' => $quantity,
            'reason' => $reason,
            'created_at' => now(),
        ]);
    }
}
