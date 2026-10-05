<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->decimal('net_quantity', 12, 3)->nullable()->after('weight');
            $table->string('net_quantity_unit', 10)->nullable()->after('net_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn(['net_quantity', 'net_quantity_unit']);
        });
    }
};