<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // MySQL can leave DDL-created tables after a failed foreign-key statement.
        Schema::dropIfExists('cart_recommendation_section_product');
        Schema::dropIfExists('cart_recommendation_sections');

        Schema::create('cart_recommendation_sections', function (Blueprint $table) {
            $table->id();
            $table->string('heading');
            $table->boolean('is_tabular')->default(false);
            $table->enum('status', ['active', 'inactive'])->default('inactive');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['status', 'sort_order']);
        });

        Schema::create('cart_recommendation_section_product', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cart_recommendation_section_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->foreign('cart_recommendation_section_id', 'cart_rec_section_fk')
                ->references('id')->on('cart_recommendation_sections')->cascadeOnDelete();
            $table->foreign('product_id', 'cart_rec_product_fk')
                ->references('id')->on('products')->cascadeOnDelete();
            $table->unique(['cart_recommendation_section_id', 'product_id'], 'cart_recommendation_product_unique');
            $table->index(['cart_recommendation_section_id', 'sort_order'], 'cart_recommendation_product_order_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_recommendation_section_product');
        Schema::dropIfExists('cart_recommendation_sections');
    }
};
