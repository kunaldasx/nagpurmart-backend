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
        Schema::table('sellers', function (Blueprint $table) {
            $table->json('metadata')->nullable()->change();
            $table->dropColumn('business_license');
            $table->dropColumn('articles_of_incorporation');
            $table->dropColumn('national_identity_card');
            $table->dropColumn('authorized_signature');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sellers', function (Blueprint $table) {
            $table->json('metadata')->change();
            $table->text('business_license');
            $table->text('articles_of_incorporation');
            $table->text('national_identity_card');
            $table->text('authorized_signature');
        });
    }
};
