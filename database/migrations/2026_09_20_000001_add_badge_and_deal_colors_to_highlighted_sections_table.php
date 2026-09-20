<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('highlighted_sections', function (Blueprint $table) {
            $table->string('badge_color')->nullable()->after('button_text_color');
            $table->string('badge_text_color')->nullable()->after('badge_color');
            $table->string('deal_badge_color')->nullable()->after('badge_text_color');
            $table->string('deal_price_color')->nullable()->after('deal_badge_color');
        });
    }

    public function down(): void
    {
        Schema::table('highlighted_sections', function (Blueprint $table) {
            $table->dropColumn([
                'badge_color',
                'badge_text_color',
                'deal_badge_color',
                'deal_price_color',
            ]);
        });
    }
};