<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bags', function (Blueprint $table) {
            $table->foreignId('picked_up_by_delivery_boy_id')
                ->nullable()
                ->after('assigned_at')
                ->constrained('delivery_boys')
                ->nullOnDelete();
            $table->timestamp('picked_up_at')->nullable()->after('picked_up_by_delivery_boy_id');
        });
    }

    public function down(): void
    {
        Schema::table('bags', function (Blueprint $table) {
            $table->dropConstrainedForeignId('picked_up_by_delivery_boy_id');
            $table->dropColumn('picked_up_at');
        });
    }
};