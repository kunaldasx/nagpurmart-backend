<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('featured_sections', function (Blueprint $table) {
            $table->string('text_color')->after('background_color')->default('#000000');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('featured_sections', function (Blueprint $table) {
            $table->dropColumn('text_color');
        });
    }
};
