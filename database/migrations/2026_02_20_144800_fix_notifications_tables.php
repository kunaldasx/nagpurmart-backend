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
        // 1) If an existing custom notifications table is using the name
        //    `notifications` (without a `data` column), rename it to `app_notifications`.
        if (Schema::hasTable('notifications') && !Schema::hasColumn('notifications', 'data')) {
            Schema::rename('notifications', 'app_notifications');
        }

        // 2) Ensure Laravel's default `notifications` table exists
        if (!Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->json('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We won't drop the default notifications table automatically to avoid
        // data loss. If the `app_notifications` table doesn't exist but an
        // old-style `notifications` (without `data`) exists, try to revert the rename.

        if (!Schema::hasTable('app_notifications')
            && Schema::hasTable('notifications')
            && !Schema::hasColumn('notifications', 'data')) {
            Schema::rename('notifications', 'app_notifications');
        }
    }
};
