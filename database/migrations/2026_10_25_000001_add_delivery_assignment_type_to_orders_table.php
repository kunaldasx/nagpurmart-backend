<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('orders', 'delivery_assignment_type')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('delivery_assignment_type')->nullable()->after('delivery_boy_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('orders', 'delivery_assignment_type')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('delivery_assignment_type');
            });
        }
    }
};