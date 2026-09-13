<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('promo', function (Blueprint $table) {
            $table->string('heading')->nullable()->after('description');
            $table->string('sub_heading')->nullable()->after('heading');
            $table->string('banner_image')->nullable()->after('sub_heading');
            $table->string('bg_color', 20)->nullable()->after('banner_image');
            $table->string('font_color', 20)->nullable()->after('bg_color');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promo', function (Blueprint $table) {
            $table->dropColumn(['heading', 'sub_heading', 'banner_image', 'bg_color', 'font_color']);
        });
    }
};
