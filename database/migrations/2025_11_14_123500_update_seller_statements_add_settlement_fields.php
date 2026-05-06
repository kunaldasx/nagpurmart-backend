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
        Schema::table('seller_statements', function (Blueprint $table) {
            $table->enum('settlement_status', ['pending', 'settled'])->default('pending')->after('posted_at');
            $table->timestamp('settled_at')->nullable()->after('settlement_status');
            $table->string('settlement_reference')->nullable()->after('settled_at')->comment('payment reference / batch ID');

            $table->index(['seller_id', 'settlement_status']);
            $table->index(['settlement_status', 'posted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seller_statements', function (Blueprint $table) {
            $table->dropIndex('seller_statements_seller_id_settlement_status_index');
            $table->dropIndex('seller_statements_settlement_status_posted_at_index');
            $table->dropColumn(['settlement_status', 'settled_at', 'settlement_reference']);
        });
    }
};
