<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('categories', 'is_tabular')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->boolean('is_tabular')->default(false)->after('requires_approval');
            });
        }

        if (!Schema::hasColumn('categories', 'tabular_subtitle')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->string('tabular_subtitle')->nullable()->after('is_tabular');
            });
        }

        if (!Schema::hasColumn('categories', 'tabular_subtitle_color')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->string('tabular_subtitle_color', 7)->nullable()->after('tabular_subtitle');
            });
        }
    }

    public function down(): void
    {
        $columns = array_filter([
            Schema::hasColumn('categories', 'tabular_subtitle_color') ? 'tabular_subtitle_color' : null,
            Schema::hasColumn('categories', 'tabular_subtitle') ? 'tabular_subtitle' : null,
            Schema::hasColumn('categories', 'is_tabular') ? 'is_tabular' : null,
        ]);

        if ($columns) {
            Schema::table('categories', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};
