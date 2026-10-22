<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('delivery_boy_order_offers', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('delivery_boy_order_offers')
            ->whereNull('expires_at')
            ->update(['expires_at' => now()]);

        Schema::table('delivery_boy_order_offers', function (Blueprint $table) {
            $table->timestamp('expires_at')->useCurrent()->nullable(false)->change();
        });
    }
};