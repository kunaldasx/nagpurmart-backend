<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('wishlists', function (Blueprint $table) {
            // Drop the current unique index on 'slug'
            $table->dropUnique(['slug']);

            // Add a unique composite index on 'user_id' and 'slug'
            $table->unique(['user_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::table('wishlists', function (Blueprint $table) {
            // Drop the composite unique index
            $table->dropUnique(['user_id', 'slug']);

            // Restore the unique index on 'slug'
            $table->unique('slug');
        });
    }
};
