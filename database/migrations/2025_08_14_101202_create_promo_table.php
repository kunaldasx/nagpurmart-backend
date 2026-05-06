<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('promo', function (Blueprint $table) {
            $table->id();
            $table->string('code', 25)->unique();
            $table->text('description')->nullable();
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();

            $table->enum('discount_type', ['free_shipping', 'flat', 'percent']);
            $table->decimal('discount_amount', 10, 2)->nullable();
            $table->enum('promo_mode', ['instant', 'cashback'])->default('instant');

            $table->integer('usage_count')->default(0);
            $table->integer('individual_use')->default(0);

            $table->integer('max_total_usage')->nullable();
            $table->integer('max_usage_per_user')->nullable();

            $table->decimal('min_order_total', 10, 2)->nullable();
            $table->decimal('max_discount_value', 10, 2)->nullable();

            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promo');
    }
};
