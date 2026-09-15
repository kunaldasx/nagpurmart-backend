<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('highlighted_sections', function (Blueprint $table) {
            $table->boolean('is_bgimage')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('highlighted_sections', function (Blueprint $table) {
            $table->dropColumn('is_bgimage');
        });
    }
};
