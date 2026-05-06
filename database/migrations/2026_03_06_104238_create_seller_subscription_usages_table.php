<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seller_subscription_usages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('seller_id')
                ->constrained('sellers')
                ->cascadeOnDelete();

            $table->enum('key', [
                'store_limit',
                'product_limit',
                'role_limit',
                'system_user_limit',
                'variation_product_limit'
            ]);

            $table->integer('used')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_subscription_usages');
    }
};
