<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offer_banners', function (Blueprint $table) {
            $table->string('background_color', 7)->default('#ffffff')->after('template_code');
            $table->string('font_color', 7)->default('#000000')->after('background_color');
        });
    }

    public function down(): void
    {
        Schema::table('offer_banners', function (Blueprint $table) {
            $table->dropColumn(['background_color', 'font_color']);
        });
    }
};