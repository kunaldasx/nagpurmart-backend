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
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('requires_otp')->default(false)->after('status');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('otp')->nullable()->after('status');
            $table->boolean('otp_verified')->default(false)->after('otp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('requires_otp');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('otp');
            $table->dropColumn('otp_verified');
        });
    }
};
