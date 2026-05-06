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
            $table->integer('delivery_boy_base_fee')->after('handling_charges')->nullable();
            $table->integer('delivery_boy_per_store_pickup_fee')->after('delivery_boy_base_fee')->nullable();
            $table->integer('delivery_boy_distance_based_fee')->after('delivery_boy_per_store_pickup_fee')->nullable();
            $table->integer('delivery_boy_per_order_incentive')->after('delivery_boy_distance_based_fee')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_zones', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_boy_base_fee',
                'delivery_boy_per_store_pickup_fee',
                'delivery_boy_distance_based_fee',
                'delivery_boy_per_order_incentive'
            ]);
        });
    }
};
