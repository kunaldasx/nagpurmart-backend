<?php

use App\Enums\Order\OrderItemStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->enum('cancelable_till', [OrderItemStatusEnum::PENDING(), OrderItemStatusEnum::AWAITING_STORE_RESPONSE(), OrderItemStatusEnum::ACCEPTED(), OrderItemStatusEnum::PREPARING()])->nullable()->after('is_cancelable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('cancelable_till');
        });
    }
};
