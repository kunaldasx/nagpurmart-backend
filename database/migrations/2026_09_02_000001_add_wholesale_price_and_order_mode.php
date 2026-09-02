<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('store_product_variants', function (Blueprint $table) {
            $table->decimal('wholesale_price', 12, 2)->nullable()->after('special_price');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('order_mode', 20)->default('regular')->after('payment_method');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('order_mode');
        });

        Schema::table('store_product_variants', function (Blueprint $table) {
            $table->dropColumn('wholesale_price');
        });
    }
};
