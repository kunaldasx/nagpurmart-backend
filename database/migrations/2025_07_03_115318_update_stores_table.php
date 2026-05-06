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
        Schema::table('stores', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign('stores_delivery_zone_id_foreign');
            // Then drop the column
            $table->dropColumn('delivery_zone_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->unsignedBigInteger('delivery_zone_id')->nullable()->after('seller_id');
            $table->foreign('delivery_zone_id')->references('id')->on('delivery_zones')->onDelete('set null');
        });
    }
};
