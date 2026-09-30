<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $duplicates = DB::table('product_variants')
            ->select('barcode')
            ->selectRaw('COUNT(*) AS barcode_count')
            ->groupBy('barcode')
            ->havingRaw('COUNT(*) > 1')
            ->limit(10)
            ->pluck('barcode');

        if ($duplicates->isNotEmpty()) {
            throw new RuntimeException(
                'Cannot add the unique product variant barcode index. Resolve duplicate barcodes first: '
                . $duplicates->implode(', ')
            );
        }

        Schema::table('product_variants', function (Blueprint $table) {
            $table->unique('barcode');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropUnique(['barcode']);
        });
    }
};