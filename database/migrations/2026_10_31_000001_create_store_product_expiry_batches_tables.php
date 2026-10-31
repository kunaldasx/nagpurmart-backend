<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('store_product_expiry_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_product_variant_id')
                ->constrained('store_product_variants')
                ->cascadeOnDelete();
            $table->string('batch_code', 100)->nullable();
            $table->unsignedInteger('quantity_received');
            $table->unsignedInteger('quantity_remaining');
            $table->date('expiry_date')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(
                ['store_product_variant_id', 'expiry_date'],
                'expiry_batches_variant_expiry_idx'
            );
        });

        Schema::create('order_item_expiry_batch_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->foreignId('expiry_batch_id')
                ->nullable()
                ->constrained('store_product_expiry_batches')
                ->nullOnDelete();
            $table->unsignedInteger('quantity_consumed');
            $table->unsignedInteger('quantity_restored')->default(0);
            $table->timestamps();

            $table->index(
                ['order_item_id', 'expiry_batch_id'],
                'expiry_allocations_order_batch_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_item_expiry_batch_allocations');
        Schema::dropIfExists('store_product_expiry_batches');
    }
};
