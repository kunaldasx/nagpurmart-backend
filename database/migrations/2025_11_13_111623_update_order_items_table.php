<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->boolean('return_eligible')->default(false)->after('commission_settled');
            $table->tinyInteger('returnable_days')->default(0)->after('return_eligible');
            $table->date('return_deadline')->nullable()->after('return_eligible');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('return_eligible');
            $table->dropColumn('returnable_days');
            $table->dropColumn('return_deadline');
        });
    }
};
