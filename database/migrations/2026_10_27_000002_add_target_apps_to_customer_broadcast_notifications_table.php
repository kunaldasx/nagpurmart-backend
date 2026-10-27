<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_broadcast_notifications', function (Blueprint $table) {
            $table->json('target_apps')->nullable()->after('target_categories');
        });
    }

    public function down(): void
    {
        Schema::table('customer_broadcast_notifications', function (Blueprint $table) {
            $table->dropColumn('target_apps');
        });
    }
};
