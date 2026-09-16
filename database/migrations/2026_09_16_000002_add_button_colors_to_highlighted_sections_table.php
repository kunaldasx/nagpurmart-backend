<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('highlighted_sections', function (Blueprint $table) {
            $table->string('button_color')->nullable()->after('font_color');
            $table->string('button_text_color')->nullable()->after('button_color');
        });
    }

    public function down(): void
    {
        Schema::table('highlighted_sections', function (Blueprint $table) {
            $table->dropColumn(['button_color', 'button_text_color']);
        });
    }
};