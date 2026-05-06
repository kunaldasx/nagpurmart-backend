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
        Schema::table('delivery_boy_assignments', function (Blueprint $table) {
            $table->unsignedBigInteger('order_item_id')->nullable()->after('delivery_boy_id');
            $table->unsignedBigInteger('return_id')->nullable()->after('order_item_id');
            $table->enum('assignment_type', ['delivery', 'return_pickup'])->default('delivery')->after('return_id')->comment('delivery, pickup');
            $table->foreign('order_item_id')->references('id')->on('order_items')->onDelete('cascade');
            $table->foreign('return_id')->references('id')->on('order_item_returns')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_boy_assignments', function (Blueprint $table) {
            $table->dropForeign(['order_item_id']);
            $table->dropColumn(['order_item_id', 'order_type']);
        });
    }
};
