<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('seller_user', 'login_approved_until')) {
            Schema::table('seller_user', function (Blueprint $table) {
                $table->timestamp('login_approved_until')->nullable();
            });
        }

        DB::table('seller_user')->update([
            'login_approval_status' => 'disapproved',
            'login_approved_at' => null,
            'login_approved_until' => null,
        ]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('seller_user', 'login_approved_until')) {
            Schema::table('seller_user', function (Blueprint $table) {
                $table->dropColumn('login_approved_until');
            });
        }
    }
};