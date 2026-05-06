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
        Schema::table('delivery_zones', function (Blueprint $table) {
            $table->boolean('rush_delivery_enabled')->after('boundary_json')->default(false);
            $table->integer('rush_delivery_time_per_km')->after('radius_km')->nullable();
            $table->integer('rush_delivery_charges')->after('rush_delivery_time_per_km')->nullable();
            $table->integer('regular_delivery_charges')->after('delivery_time_per_km');
            $table->integer('free_delivery_amount')->after('regular_delivery_charges')->nullable();
            $table->integer('distance_based_delivery_charges')->after('free_delivery_amount')->nullable();
            $table->integer('per_store_drop_off_fee')->after('distance_based_delivery_charges')->nullable();
            $table->integer('handling_charges')->after('per_store_drop_off_fee')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_zones', function (Blueprint $table) {
            $table->dropColumn(['rush_delivery_enabled', 'rush_delivery_time_per_km', 'rush_delivery_charges', 'regular_delivery_charges', 'free_delivery_amount', 'distance_based_delivery_charges', 'per_store_drop_off_fee', 'handling_charges']);
        });
    }
};
