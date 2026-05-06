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
        Schema::create('delivery_boy_cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delivery_boy_assignment_id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('delivery_boy_id');
            $table->decimal('amount', 10, 2);
            $table->enum('transaction_type', ['collected', 'submitted']);
            $table->timestamp('transaction_date');
            $table->timestamps();

            $table->foreign('delivery_boy_assignment_id', 'fk_dbc_assignment')
                ->references('id')->on('delivery_boy_assignments')->onDelete('cascade');
            $table->foreign('order_id', 'fk_dbc_order')
                ->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('delivery_boy_id', 'fk_dbc_boy')
                ->references('id')->on('delivery_boys')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_boy_cash_transactions');
    }
};
