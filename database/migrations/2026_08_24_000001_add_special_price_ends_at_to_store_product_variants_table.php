<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_product_variants', function (Blueprint $table) {
            $table->dateTime('special_price_ends_at')->nullable()->after('special_price');
        });
    }

    public function down(): void
    {
        Schema::table('store_product_variants', function (Blueprint $table) {
            $table->dropColumn('special_price_ends_at');
        });
    }
};