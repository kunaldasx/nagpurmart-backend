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
        Schema::table('product_variants', function (Blueprint $table) {
            $table->float('weight')->nullable()->change();
            $table->float('height')->nullable()->change();
            $table->float('breadth')->nullable()->change();
            $table->float('length')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->float('weight')->change();
            $table->float('height')->change();
            $table->float('breadth')->change();
            $table->float('length')->change();
        });
    }
};
