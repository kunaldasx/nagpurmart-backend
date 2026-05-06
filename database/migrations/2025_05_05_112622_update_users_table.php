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
        Schema::table('users', function (Blueprint $table) {
            $table->string('mobile', 20)->unique()->after('id');
            $table->string('referral_code', 32)->nullable()->after('mobile');
            $table->string('friends_code', 32)->nullable()->after('referral_code');
            $table->decimal('reward_points', 10)->default(0.00)->after('friends_code');
            $table->enum('status', ['active', 'inactive'])->default('active')->after('reward_points');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'mobile',
                'referral_code',
                'friends_code',
                'reward_points',
                'status'
            ]);
        });
    }
};
