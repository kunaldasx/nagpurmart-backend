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
        Schema::table('store_product_variants', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('product_variant_attributes', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store_product_variants', function (Blueprint $table) {
            $table->dropColumn('deleted_at');
        });
        Schema::table('product_variant_attributes', function (Blueprint $table) {
            $table->dropColumn('deleted_at');
        });
    }
};
