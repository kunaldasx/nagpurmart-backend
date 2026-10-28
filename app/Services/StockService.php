<?php

namespace App\Services;

use App\Enums\StockInventoryTypeEnum;
use App\Models\StoreInventoryLog;
use App\Models\StoreProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockService
{
    /**
     * Update stock quantity for a store product variant
     *
     * @param int $storeId The store ID
     * @param int $productVariantId The product variant ID
     * @param int $quantity The quantity to update (positive to add, negative to remove)
     * @param string $reason The reason for the stock update
     * @return array Result containing success status, message, and data
     */
    public function updateStock(int $storeId, int $productVariantId, int $quantity, string $reason): array
    {
        try {
            return DB::transaction(function () use ($storeId, $productVariantId, $quantity, $reason): array {
                $storeVariant = StoreProductVariant::where('store_id', $storeId)
                    ->where('product_variant_id', $productVariantId)
                    ->lockForUpdate()
                    ->first();

                if (!$storeVariant) {
                    return [
                        'success' => false,
                        'message' => 'Store product variant not found',
                        'data' => []
                    ];
                }

                $newStock = $storeVariant->stock + $quantity;
                if ($newStock < 0) {
                    return [
                        'success' => false,
                        'message' => 'Insufficient stock available',
                        'data' => ['available_stock' => (int) $storeVariant->stock]
                    ];
                }

                $storeVariant->update(['stock' => $newStock]);

                $changeType = $quantity > 0
                    ? StockInventoryTypeEnum::ADD()
                    : StockInventoryTypeEnum::REMOVE();

                $this->logInventoryChange(
                    $storeId,
                    $productVariantId,
                    $changeType,
                    abs($quantity),
                    $reason
                );

                Log::info('Stock updated', [
                    'store_id' => $storeId,
                    'product_variant_id' => $productVariantId,
                    'quantity_change' => $quantity,
                    'new_stock_level' => $newStock,
                    'reason' => $reason
                ]);

                return [
                    'success' => true,
                    'message' => 'Stock updated successfully',
                    'data' => [
                        'new_stock' => $newStock
                    ]
                ];
            });
        } catch (\Exception $e) {
            Log::error('Error updating stock', [
                'store_id' => $storeId,
                'product_variant_id' => $productVariantId,
                'quantity' => $quantity,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Error updating stock: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Add stock to a store product variant
     *
     * @param int $storeId The store ID
     * @param int $productVariantId The product variant ID
     * @param int $quantity The quantity to add
     * @param string $reason The reason for adding stock
     * @return array Result containing success status, message, and data
     */
    public function addStock(int $storeId, int $productVariantId, int $quantity, string $reason): array
    {
        return $this->updateStock($storeId, $productVariantId, abs($quantity), $reason);
    }

    /**
     * Remove stock from a store product variant
     *
     * @param int $storeId The store ID
     * @param int $productVariantId The product variant ID
     * @param int $quantity The quantity to remove
     * @param string $reason The reason for removing stock
     * @return array Result containing success status, message, and data
     */
    public function removeStock(int $storeId, int $productVariantId, int $quantity, string $reason): array
    {
        return $this->updateStock($storeId, $productVariantId, -abs($quantity), $reason);
    }

    /**
     * Log an inventory change
     *
     * @param int $storeId The store ID
     * @param int $productVariantId The product variant ID
     * @param string $changeType The type of change (add, remove, adjust)
     * @param int $quantity The quantity changed
     * @param string $reason The reason for the change
     * @return void
     */
    private function logInventoryChange(
        int $storeId,
        int $productVariantId,
        string $changeType,
        int $quantity,
        string $reason
    ): void {
        StoreInventoryLog::create([
            'store_id' => $storeId,
            'product_variant_id' => $productVariantId,
            'change_type' => $changeType,
            'quantity' => $quantity,
            'reason' => $reason,
            'created_at' => now(),
        ]);
    }
}
