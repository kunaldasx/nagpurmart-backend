<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_boys', function (Blueprint $table) {
            $table->foreignId('seller_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->string('login_approval_status')->default('disapproved')->after('verification_remark');
            $table->timestamp('login_approval_requested_at')->nullable()->after('login_approval_status');
            $table->timestamp('login_approved_at')->nullable()->after('login_approval_requested_at');
            $table->timestamp('login_approved_until')->nullable()->after('login_approved_at');
            $table->char('qr_login_token_hash', 64)->nullable()->unique()->after('login_approved_until');
            $table->text('qr_login_token_encrypted')->nullable()->after('qr_login_token_hash');
            $table->timestamp('qr_login_expires_at')->nullable()->after('qr_login_token_encrypted');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_boys', function (Blueprint $table) {
            $table->dropUnique(['qr_login_token_hash']);
            $table->dropForeign(['seller_id']);
            $table->dropColumn([
                'seller_id',
                'login_approval_status',
                'login_approval_requested_at',
                'login_approved_at',
                'login_approved_until',
                'qr_login_token_hash',
                'qr_login_token_encrypted',
                'qr_login_expires_at',
            ]);
        });
    }
};
