<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Makes name and email nullable to support Rapido-like phone+OTP only signup flow
     * where users can create accounts with just phone and complete their profile later.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Make email nullable (currently unique)
            $table->string('email')->nullable()->change();

            // Make name nullable
            $table->string('name')->nullable()->change();

            // Make password nullable to support OTP-only signups
            $table->string('password')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Revert email to non-nullable
            $table->string('email')->nullable(false)->change();

            // Revert name to non-nullable
            $table->string('name')->nullable(false)->change();

            // Revert password to non-nullable
            $table->string('password')->nullable(false)->change();
        });
    }
};
