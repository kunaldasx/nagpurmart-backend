<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id(); // bigint [pk, increment]
            $table->unsignedBigInteger('wallet_id'); // bigint
            $table->unsignedBigInteger('user_id'); // bigint
            $table->unsignedBigInteger('order_id')->nullable(); // bigint [null]
            $table->unsignedBigInteger('store_id')->nullable(); // bigint [null]
            $table->enum('transaction_type', ['deposit', 'payment', 'refund', 'adjustment']); // enum
            $table->string('payment_method')->nullable(); // enum
            $table->decimal('amount', 10, 2); // decimal(10,2)
            $table->string('currency_code', 3)->default('USD'); // varchar(3)
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('pending'); // enum
            $table->string('transaction_reference', 100)->unique()->nullable()->comment('Transaction ID from payment gateway'); // varchar(100) [unique, null]
            $table->string('description', 255)->nullable(); // varchar(255) [null]
            $table->timestamps(); // created_at, updated_at

            // Foreign key constraints
            $table->foreign('wallet_id')->references('id')->on('wallets');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('order_id')->references('id')->on('orders');
            $table->foreign('store_id')->references('id')->on('stores');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
