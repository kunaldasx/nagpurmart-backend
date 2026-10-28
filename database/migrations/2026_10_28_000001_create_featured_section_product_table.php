<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('featured_section_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('featured_section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order');
            $table->timestamps();

            $table->unique(['featured_section_id', 'product_id']);
            $table->index(['featured_section_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('featured_section_product');
    }
};
