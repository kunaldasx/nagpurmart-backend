<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seller_user', function (Blueprint $table) {
            $table->string('login_approval_status')->default('approved');
            $table->timestamp('login_approval_requested_at')->nullable();
            $table->timestamp('login_approved_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('seller_user', function (Blueprint $table) {
            $table->dropColumn([
                'login_approval_status',
                'login_approval_requested_at',
                'login_approved_at',
            ]);
        });
    }
};