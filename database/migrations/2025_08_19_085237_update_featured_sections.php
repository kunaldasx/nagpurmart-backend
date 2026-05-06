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
            $table->enum('scope_type', ['global', 'category'])->default('global')->after('id');
            $table->unsignedBigInteger('scope_id')->nullable()->after('scope_type');
            $table->enum('background_type', ['image', 'color'])->nullable()->after('style');
            $table->string('background_color')->nullable()->after('background_type');
            $table->foreign('scope_id')->references('id')->on('categories');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('featured_sections', function (Blueprint $table) {
            $table->dropColumn(['scope_type', 'scope_id', 'background_type', 'background_color']);
        });
    }
};
