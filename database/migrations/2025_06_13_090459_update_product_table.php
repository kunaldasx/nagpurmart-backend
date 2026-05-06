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
        Schema::table('products', function (Blueprint $table) {
            // Ensure column exists and types are correct
            $table->unsignedBigInteger('product_condition_id')->nullable()->change();
            $table->string('hsn_code')->nullable();
            $table->enum('type', ['single', 'variant', 'digital'])->change();

            // Drop existing foreign key if any
            $table->dropForeign(['product_condition_id']);
        });

        // Add foreign key separately
        Schema::table('products', function (Blueprint $table) {
            $table->foreign('product_condition_id')
                ->references('id')
                ->on('product_conditions')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('product_condition_id')->constrained('product_conditions')->onDelete('cascade');
            $table->dropColumn('hsn_code');
            $table->enum('type', ['physical', 'digital']);
        });
    }
};
