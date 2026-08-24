<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('grocery_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('image_path')->nullable();
            $table->string('status')->default('completed');
            $table->string('language')->nullable();
            $table->text('extracted_text')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('grocery_list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grocery_list_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('extracted_name');
            $table->string('normalized_name')->nullable();
            $table->decimal('quantity', 12, 3)->nullable();
            $table->string('unit', 50)->nullable();
            $table->decimal('confidence', 5, 4)->nullable();
            $table->timestamps();

            $table->index(['grocery_list_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grocery_list_items');
        Schema::dropIfExists('grocery_lists');
    }
};