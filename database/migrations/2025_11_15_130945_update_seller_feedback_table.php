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
        Schema::table('seller_feedback', function (Blueprint $table) {
            if (!Schema::hasColumn('seller_feedback', 'order_item_id')) {
                $table->foreignId('order_item_id')->unique()
                    ->after('order_id')
                    ->constrained('order_items')
                    ->cascadeOnDelete();
            }
            if (!Schema::hasColumn('seller_feedback', 'store_id')) {
                $table->foreignId('store_id')
                    ->nullable()
                    ->after('order_item_id')
                    ->constrained('stores')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seller_feedback', function (Blueprint $table) {
            if (Schema::hasColumn('seller_feedback', 'order_item_id')) {
                $table->dropForeign(['order_item_id']);
                $table->dropColumn('order_item_id');
            }
            if (Schema::hasColumn('seller_feedback', 'store_id')) {
                $table->dropForeign(['store_id']);
                $table->dropColumn('store_id');
            }
        });
    }

};
