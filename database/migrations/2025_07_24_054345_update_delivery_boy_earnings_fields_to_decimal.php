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
        Schema::table('delivery_zones', function (Blueprint $table) {
            $table->decimal('delivery_boy_base_fee', 10, 2)->nullable()->change();
            $table->decimal('delivery_boy_per_store_pickup_fee', 10, 2)->nullable()->change();
            $table->decimal('delivery_boy_distance_based_fee', 10, 2)->nullable()->change();
            $table->decimal('delivery_boy_per_order_incentive', 10, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_zones', function (Blueprint $table) {
            $table->integer('delivery_boy_base_fee')->nullable()->change();
            $table->integer('delivery_boy_per_store_pickup_fee')->nullable()->change();
            $table->integer('delivery_boy_distance_based_fee')->nullable()->change();
            $table->integer('delivery_boy_per_order_incentive')->nullable()->change();
        });
    }
};
