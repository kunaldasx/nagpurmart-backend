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
        Schema::table('seller_order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('order_item_id')->after('product_variant_id');
            $table->foreign('order_item_id')->references('id')->on('order_items')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seller_order_items', function (Blueprint $table) {
            $table->dropColumn('order_item_id');
        });
    }
};
