<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('customer_gstin', 15)->nullable();
            $table->string('customer_gstin_legal_name')->nullable();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('customer_gstin', 15)->nullable();
            $table->string('customer_gstin_legal_name')->nullable();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('supplier_tax_name')->nullable();
            $table->string('supplier_tax_number')->nullable();
            $table->string('hsn_code', 20)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['supplier_tax_name', 'supplier_tax_number', 'hsn_code']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['customer_gstin', 'customer_gstin_legal_name']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['customer_gstin', 'customer_gstin_legal_name']);
        });
    }
};
