<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seller_user', function (Blueprint $table) {
            $table->string('status')->default('inactive')->index();
            $table->index(
                ['login_approval_status', 'login_approved_until'],
                'seller_user_approval_expiry_index',
            );
        });
    }

    public function down(): void
    {
        Schema::table('seller_user', function (Blueprint $table) {
            $table->dropIndex('seller_user_approval_expiry_index');
            $table->dropIndex('seller_user_status_index');
            $table->dropColumn('status');
        });
    }
};
