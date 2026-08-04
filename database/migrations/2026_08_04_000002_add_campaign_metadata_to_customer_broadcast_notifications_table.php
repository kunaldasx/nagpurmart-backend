<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_broadcast_notifications', function (Blueprint $table) {
            $table->string('deep_link')->nullable()->after('action_url');
            $table->json('target_categories')->nullable()->after('deep_link');
            $table->timestamp('expires_at')->nullable()->after('target_categories');
            $table->unsignedTinyInteger('priority')->default(0)->after('expires_at');
            $table->boolean('is_active')->default(true)->after('priority');
        });
    }

    public function down(): void
    {
        Schema::table('customer_broadcast_notifications', function (Blueprint $table) {
            $table->dropColumn(['deep_link', 'target_categories', 'expires_at', 'priority', 'is_active']);
        });
    }
};
