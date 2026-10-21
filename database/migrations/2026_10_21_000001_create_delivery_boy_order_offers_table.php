<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('delivery_boy_order_offers')) {
            Schema::create('delivery_boy_order_offers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained()->cascadeOnDelete();
                $table->foreignId('delivery_boy_id')->constrained()->cascadeOnDelete();
                $table->string('status')->default('pending');
                $table->timestamp('expires_at');
                $table->timestamp('responded_at')->nullable();
                $table->timestamps();
            });
        }

        $indexes = Schema::getIndexes('delivery_boy_order_offers');
        $hasIndexFor = static fn (array $columns): bool => collect($indexes)
            ->contains(static fn (array $index): bool => $index['columns'] === $columns);

        if (!$hasIndexFor(['order_id', 'status'])) {
            Schema::table('delivery_boy_order_offers', function (Blueprint $table) {
                $table->index(['order_id', 'status'], 'dbo_offer_order_status_idx');
            });
        }

        if (!$hasIndexFor(['delivery_boy_id', 'status', 'expires_at'])) {
            Schema::table('delivery_boy_order_offers', function (Blueprint $table) {
                $table->index(['delivery_boy_id', 'status', 'expires_at'], 'dbo_offer_rider_status_expiry_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_boy_order_offers');
    }
};