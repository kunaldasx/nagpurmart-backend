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
        Schema::create('product_variant_attributes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('product_variant_id');
            $table->unsignedBigInteger('global_attribute_id');
            $table->unsignedBigInteger('global_attribute_value_id');
            $table->timestamps();

            $table->foreign('product_variant_id')->references('id')->on('product_variants');
            $table->foreign('global_attribute_id')->references('id')->on('global_product_attributes');
            $table->foreign('global_attribute_value_id')->references('id')->on('global_product_attribute_values');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variant_attributes');
    }
};
