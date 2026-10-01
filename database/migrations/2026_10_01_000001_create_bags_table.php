<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained()->cascadeOnDelete();
            $table->string('barcode', 255)->unique();
            $table->foreignId('seller_order_id')->nullable()->unique()->constrained('seller_orders')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();

            $table->index(['seller_id', 'seller_order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bags');
    }
};