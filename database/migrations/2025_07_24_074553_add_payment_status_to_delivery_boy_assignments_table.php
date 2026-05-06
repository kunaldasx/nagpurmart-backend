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
        Schema::table('delivery_boy_assignments', function (Blueprint $table) {
            $table->enum('payment_status', ['pending', 'paid'])->default('pending')->after('total_earnings');
            $table->timestamp('paid_at')->nullable()->after('payment_status');
            $table->unsignedBigInteger('transaction_id')->nullable()->after('paid_at');
            $table->foreign('transaction_id')->references('id')->on('wallet_transactions')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_boy_assignments', function (Blueprint $table) {
            $table->dropForeign(['transaction_id']);
            $table->dropColumn(['payment_status', 'paid_at', 'transaction_id']);
        });
    }
};
