<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('highlighted_sections', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('subtitle')->nullable();
            $table->string('template');
            $table->enum('scope_type', ['global', 'category'])->default('global');
            $table->foreignId('scope_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('background_color')->nullable();
            $table->string('font_color')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->enum('status', ['active', 'inactive'])->default('inactive');
            $table->timestamps();
            $table->index(['scope_type', 'scope_id']);
        });

        Schema::create('highlighted_section_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('highlighted_section_id')->constrained()->cascadeOnDelete();
            $table->enum('item_type', ['product', 'category', 'brand']);
            $table->unsignedBigInteger('item_id');
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['item_type', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('highlighted_section_items');
        Schema::dropIfExists('highlighted_sections');
    }
};