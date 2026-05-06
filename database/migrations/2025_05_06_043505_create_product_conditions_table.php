<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_conditions', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->string('title');
            $table->string('slug')->unique();
            $table->enum('alignment', ['strip']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_conditions');
    }
};
