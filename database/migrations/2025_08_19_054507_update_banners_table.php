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
        Schema::table('banners', function (Blueprint $table) {
            $table->enum('scope_type', ['global', 'category'])->default('global')->after('type'); // scope type
            $table->unsignedBigInteger('scope_id')->nullable()->after('scope_type'); // category_id if scope=category
            $table->foreign('scope_id')->references('id')->on('categories')->onDelete('cascade');
            $table->index(['scope_type', 'scope_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn(['scope_type', 'scope_id']);
        });
    }
};
