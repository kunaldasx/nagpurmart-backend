<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('title');
            $table->string('slug', 500)->unique();
            $table->float('weight');
            $table->float('height');
            $table->float('breadth');
            $table->float('length');
            $table->boolean('availability');
            $table->string('provider')->default('self');
            $table->string('provider_product_id')->nullable();
            $table->json('provider_json')->nullable();
            $table->string('barcode');
            $table->enum('visibility', ['published', 'draft']);
            $table->boolean('is_default');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
