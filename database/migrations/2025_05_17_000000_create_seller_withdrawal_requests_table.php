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
        Schema::create('seller_withdrawal_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('seller_id');
            $table->decimal('amount', 10, 2)->comment('Amount requested for withdrawal');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('request_note')->nullable()->comment('Note from seller');
            $table->text('admin_remark')->nullable()->comment('Remark from admin');
            $table->timestamp('processed_at')->nullable()->comment('When the request was processed');
            $table->unsignedBigInteger('processed_by')->nullable()->comment('Admin who processed the request');
            $table->unsignedBigInteger('transaction_id')->nullable()->comment('Related wallet transaction ID');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('seller_id')->references('id')->on('sellers')->onDelete('cascade');
            $table->foreign('processed_by')->references('id')->on('users')->onDelete('set null');

            $table->index('user_id');
            $table->index('seller_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seller_withdrawal_requests');
    }
};
