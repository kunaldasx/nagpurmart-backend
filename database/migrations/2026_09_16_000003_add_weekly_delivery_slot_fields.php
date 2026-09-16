<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('delivery_time_slots', function (Blueprint $table) {
            $table->string('day_of_week', 10)->nullable()->after('store_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->date('delivery_date')->nullable()->after('delivery_time_slot_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('delivery_date');
        });

        Schema::table('delivery_time_slots', function (Blueprint $table) {
            $table->dropColumn('day_of_week');
        });
    }
};