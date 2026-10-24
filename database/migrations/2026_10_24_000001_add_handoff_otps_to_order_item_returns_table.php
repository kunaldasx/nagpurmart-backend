<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_item_returns', function (Blueprint $table) {
            $table->string('customer_pickup_otp_hash')->nullable();
            $table->timestamp('customer_pickup_otp_sent_at')->nullable();
            $table->string('seller_handover_otp_hash')->nullable();
            $table->timestamp('seller_handover_otp_sent_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('order_item_returns', function (Blueprint $table) {
            $table->dropColumn([
                'customer_pickup_otp_hash',
                'customer_pickup_otp_sent_at',
                'seller_handover_otp_hash',
                'seller_handover_otp_sent_at',
            ]);
        });
    }
};
