<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('offer_banner_templates')
            ->where('code', 'T_SAVE_BIG_WEEKEND')
            ->update([
                'code' => 'T_9',
                'preview_path' => 'assets/templates/T_9.svg',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('offer_banner_templates')
            ->where('code', 'T_9')
            ->update([
                'code' => 'T_SAVE_BIG_WEEKEND',
                'preview_path' => 'assets/templates/T_SAVE_BIG_WEEKEND.svg',
                'updated_at' => now(),
            ]);
    }
};