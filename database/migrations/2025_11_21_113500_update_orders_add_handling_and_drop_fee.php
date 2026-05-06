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
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'handling_charges')) {
                $table->decimal('handling_charges', 10, 2)->default(0.00)->after('delivery_charge');
            }
            if (!Schema::hasColumn('orders', 'per_store_drop_off_fee')) {
                $table->decimal('per_store_drop_off_fee', 10, 2)->default(0.00)->after('handling_charges');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'per_store_drop_off_fee')) {
                $table->dropColumn('per_store_drop_off_fee');
            }
            if (Schema::hasColumn('orders', 'handling_charges')) {
                $table->dropColumn('handling_charges');
            }
        });
    }
};
