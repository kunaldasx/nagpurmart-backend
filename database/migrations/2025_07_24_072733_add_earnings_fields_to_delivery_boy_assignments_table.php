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
        Schema::table('delivery_boy_assignments', function (Blueprint $table) {
            $table->decimal('base_fee', 10, 2)->nullable()->after('status');
            $table->decimal('per_store_pickup_fee', 10, 2)->nullable()->after('base_fee');
            $table->decimal('distance_based_fee', 10, 2)->nullable()->after('per_store_pickup_fee');
            $table->decimal('per_order_incentive', 10, 2)->nullable()->after('distance_based_fee');
            $table->decimal('total_earnings', 10, 2)->nullable()->after('per_order_incentive');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_boy_assignments', function (Blueprint $table) {
            $table->dropColumn([
                'base_fee',
                'per_store_pickup_fee',
                'distance_based_fee',
                'per_order_incentive',
                'total_earnings'
            ]);
        });
    }
};
