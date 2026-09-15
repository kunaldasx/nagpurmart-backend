<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('tabular_subtitle')->nullable()->after('is_tabular');
            $table->string('tabular_subtitle_color', 7)->nullable()->after('tabular_subtitle');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['tabular_subtitle', 'tabular_subtitle_color']);
        });
    }
};
