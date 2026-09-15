<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('offer_banner_templates')) {
            return;
        }

        DB::table('offer_banner_templates')->updateOrInsert(
            ['code' => 'T_9'],
            [
                'name' => 'Save Big Weekend',
                'preview_path' => 'assets/templates/T_9.svg',
                'is_active' => true,
                'display_order' => 9,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('offer_banner_templates')->where('code', 'T_9')->delete();
    }
};
