<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BackfillWholesalePriceSeeder extends Seeder
{
    /**
     * Populate missing wholesale prices from the existing cost price.
     */
    public function run(): void
    {
        $updatedRows = DB::table('store_product_variants')
            ->whereNull('wholesale_price')
            ->whereNotNull('cost')
            ->update([
                'wholesale_price' => DB::raw('cost'),
            ]);

        $this->command?->info("Backfilled wholesale prices for {$updatedRows} store product variants.");
    }
}
