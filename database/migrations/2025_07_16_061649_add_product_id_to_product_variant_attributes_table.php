<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First add the column as nullable
        Schema::table('product_variant_attributes', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable()->after('id');
        });

        // Update existing records to set product_id based on the product_variant relationship
        DB::statement('UPDATE product_variant_attributes pva
                      JOIN product_variants pv ON pva.product_variant_id = pv.id
                      SET pva.product_id = pv.product_id');

        // Make the column non-nullable and add the foreign key constraint
        Schema::table('product_variant_attributes', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable(false)->change();
            $table->foreign('product_id')->references('id')->on('products');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_variant_attributes', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
        });
    }
};
