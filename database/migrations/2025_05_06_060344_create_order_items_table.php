<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_variant_id')->constrained()->onDelete('cascade');
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('variant_title');
            $table->decimal('gift_card_discount', 10, 2)->default(0.00);
            $table->decimal('admin_commission_amount', 10, 2)->default(0.00);
            $table->decimal('seller_commission_amount', 10, 2)->default(0.00);
            $table->enum('commission_settled', ['0', '1'])->default('0');
            $table->decimal('discounted_price', 10, 2)->default(0.00);
            $table->decimal('discount', 10, 2)->default(0.00);
            $table->decimal('tax_amount', 10, 2)->nullable();
            $table->decimal('tax_percent', 5, 2)->nullable();
            $table->string('sku');
            $table->integer('quantity');
            $table->decimal('price', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->enum('status', ['pending', 'confirmed', 'out_for_delivery', 'delivered', 'canceled'])->default('pending');
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
