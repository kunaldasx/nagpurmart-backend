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
        Schema::table('delivery_boy_assignments', function (Blueprint $table) {
            $table->decimal('cod_cash_collected', 10, 2)
                ->default(0.00)
                ->comment('Cash collected by delivery boy for COD orders')->after('payment_status');

            $table->decimal('cod_cash_submitted', 10, 2)
                ->default(0.00)
                ->comment('Cash submitted by delivery boy to admin')->after('cod_cash_collected');

            $table->enum('cod_submission_status', ['pending', 'submitted', 'partially_submitted'])
                ->default('pending')
                ->comment('Status of cash submission to admin')->after('cod_cash_submitted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_boy_assignments', function (Blueprint $table) {
            $table->dropColumn([
                'cod_cash_collected',
                'cod_cash_submitted',
                'cod_submission_status',
            ]);
        });
    }
};
