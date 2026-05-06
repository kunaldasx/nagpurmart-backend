<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_item_returns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_item_id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('seller_id');
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('delivery_boy_id')->nullable();

            $table->text('reason')->nullable();
            $table->decimal('refund_amount', 10, 2)->default(0.00);
            $table->text('seller_comment')->nullable();

            $table->enum('pickup_status', [
                'pending',
                'assigned',
                'picked_up',
                'delivered_to_seller',
                'cancelled',
            ])->default('pending');

            $table->enum('return_status', [
                'cancelled',
                'requested',           // customer requested return
                'seller_approved',     // seller approved
                'seller_rejected',     // seller rejected
                'pickup_assigned',     // admin assigned delivery boy
                'picked_up',           // courier picked item
                'received_by_seller',  // seller confirmed receipt
                'refund_processed',    // admin refunded customer
                'completed',           // closed
            ])->default('requested');

            $table->timestamp('seller_approved_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('refund_processed_at')->nullable();

            $table->timestamps();

            // Foreign key constraints
            $table->foreign('order_item_id')->references('id')->on('order_items')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('seller_id')->references('id')->on('sellers')->onDelete('cascade');
            $table->foreign('store_id')->references('id')->on('stores')->onDelete('cascade');
            $table->foreign('delivery_boy_id')->references('id')->on('delivery_boys')->onDelete('set null');

            $table->comment('Tracks each return request for individual order items');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_item_returns');
    }
};
