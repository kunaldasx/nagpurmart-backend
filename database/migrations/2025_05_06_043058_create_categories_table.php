<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->onDelete('set null');
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('image');
            $table->string('banner')->nullable();
            $table->text('description');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->boolean('requires_approval');
            $table->json('metadata');
            $table->softDeletes(); // handles deleted_at
            $table->timestamps();  // handles created_at & updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
