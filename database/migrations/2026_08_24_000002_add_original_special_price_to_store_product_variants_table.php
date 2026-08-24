<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_product_variants', function (Blueprint $table) {
            $table->decimal('original_special_price', 10, 2)->nullable()->after('special_price');
        });

        DB::table('store_product_variants')->update([
            'original_special_price' => DB::raw('special_price'),
        ]);
    }

    public function down(): void
    {
        Schema::table('store_product_variants', function (Blueprint $table) {
            $table->dropColumn('original_special_price');
        });
    }
};