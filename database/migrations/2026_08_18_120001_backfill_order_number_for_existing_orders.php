<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;
use App\Models\Order;

return new class extends Migration
{
    /**
     * Run the migrations - backfill order_number for existing orders
     */
    public function up(): void
    {
        // Get all orders that don't have an order_number yet
        $orders = Order::whereNull('order_number')->get();

        foreach ($orders as $order) {
            $orderNumber = $this->generateUniqueOrderNumber();
            $order->update(['order_number' => $orderNumber]);
        }
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        // Set order_number to null for all orders (optional - only if you need to fully reverse)
        // Order::query()->update(['order_number' => null]);
    }

    /**
     * Generate a unique customer-facing order number
     * Format: NM-YYYYMMDD-XXXXXXXX
     */
    private function generateUniqueOrderNumber(int $maxRetries = 5): string
    {
        for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
            $datePrefix = now()->format('Ymd');
            $randomSuffix = strtoupper(Str::random(8));
            $orderNumber = "NM-{$datePrefix}-{$randomSuffix}";

            // Check if this order number already exists
            $exists = Order::where('order_number', $orderNumber)->exists();

            if (!$exists) {
                return $orderNumber;
            }
        }

        throw new Exception('Unable to generate unique order number after ' . $maxRetries . ' attempts.');
    }
};
