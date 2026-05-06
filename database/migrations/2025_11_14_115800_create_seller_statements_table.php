<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('seller_statements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('seller_id');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('order_item_id')->nullable();
            $table->unsignedBigInteger('return_id')->nullable();
            $table->enum('entry_type', ['credit', 'debit'])->comment('credit adds to seller balance, debit subtracts');
            $table->decimal('amount', 12, 2);
            $table->string('currency_code', 10)->nullable();
            $table->string('reference_type')->nullable()->comment('e.g., order, return, adjustment');
            $table->string('reference_id')->nullable();
            $table->string('description')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->foreign('seller_id')->references('id')->on('sellers')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
            $table->foreign('order_item_id')->references('id')->on('order_items')->onDelete('set null');
            $table->foreign('return_id')->references('id')->on('order_item_returns')->onDelete('set null');

            $table->index(['seller_id', 'posted_at']);
            $table->index(['order_id']);
            $table->index(['order_item_id']);
            $table->index(['return_id']);
            $table->index(['entry_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seller_statements');
    }
};
