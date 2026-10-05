<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('store_product_variants', function (Blueprint $table) {
            $table->decimal('unit_price_basis_quantity', 12, 3)->nullable()->after('wholesale_price');
            $table->string('unit_price_basis_unit', 30)->nullable()->after('unit_price_basis_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('store_product_variants', function (Blueprint $table) {
            $table->dropColumn(['unit_price_basis_quantity', 'unit_price_basis_unit']);
        });
    }
};