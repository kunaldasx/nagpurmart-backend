<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
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
            $table->foreignId('cart_recommendation_section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
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
