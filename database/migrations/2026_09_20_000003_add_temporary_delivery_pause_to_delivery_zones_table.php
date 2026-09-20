<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('delivery_zones', function (Blueprint $table) {
            $table->boolean('delivery_paused')->default(false)->after('delay');
            $table->dateTime('delivery_paused_until')->nullable()->after('delivery_paused');
            $table->text('delivery_pause_comment')->nullable()->after('delivery_paused_until');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_zones', function (Blueprint $table) {
            $table->dropColumn(['delivery_paused', 'delivery_paused_until', 'delivery_pause_comment']);
        });
    }
};
