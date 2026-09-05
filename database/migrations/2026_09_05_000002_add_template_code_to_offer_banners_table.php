<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offer_banners', function (Blueprint $table) {
            $table->string('template_code')->default('T_1')->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('offer_banners', function (Blueprint $table) {
            $table->dropColumn('template_code');
        });
    }
};