<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('delivery_zones', function (Blueprint $table) {
            $table->text('comment')->nullable()->after('buffer_time');
            $table->unsignedInteger('delay')->default(0)->after('comment');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_zones', function (Blueprint $table) {
            $table->dropColumn(['comment', 'delay']);
        });
    }
};