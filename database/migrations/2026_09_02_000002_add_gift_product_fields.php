<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_one_rupee_gift')->default(false)->after('price_drop');
            $table->decimal('gift_minimum_cart_amount', 12, 2)->default(1500)->after('is_one_rupee_gift');
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->boolean('is_gift')->default(false)->after('save_for_later');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->boolean('is_gift')->default(false)->after('discount');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('is_gift');
        });
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropColumn('is_gift');
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['is_one_rupee_gift', 'gift_minimum_cart_amount']);
        });
    }
};
