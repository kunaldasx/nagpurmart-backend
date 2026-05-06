<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('reviews')->delete();
        Schema::table('reviews', function (Blueprint $table) {
            $table->foreignId('order_id')->after('product_id');
            $table->foreignId('order_item_id')->after('order_id')->unique();
            $table->foreignId('store_id')->after('order_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn('order_id');
            $table->dropColumn('order_item_id');
            $table->dropColumn('store_id');
        });
    }
};
