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
            // Remove time_slot_config column
            $table->dropColumn('time_slot_config');

            // Add status enum field
            $table->enum('status', ['online', 'offline'])->default('online')->after('fulfillment_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            // Remove status column
            $table->dropColumn('status');

            // Add back time_slot_config column
            $table->json('time_slot_config')->nullable()->after('fulfillment_type');
        });
    }
};
