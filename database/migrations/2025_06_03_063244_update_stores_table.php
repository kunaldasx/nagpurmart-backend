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
        Schema::table('stores', function (Blueprint $table) {
            // Remove the store_url column
            if (Schema::hasColumn('stores', 'store_url')) {
                $table->dropColumn('store_url');
            }

            // Remove the carrier_partner column
            if (Schema::hasColumn('stores', 'carrier_partner')) {
                $table->dropColumn('carrier_partner');
            }

            // Remove the shipping_preference column
            if (Schema::hasColumn('stores', 'shipping_preference')) {
                $table->dropColumn('shipping_preference');
            }
            // Remove the address_proof column
            if (Schema::hasColumn('stores', 'address_proof')) {
                $table->dropColumn('address_proof');
            }
            // Remove the voided_check column
            if (Schema::hasColumn('stores', 'voided_check')) {
                $table->dropColumn('voided_check');
            }
            $table->decimal('latitude', 10, 8)->nullable()->change();
            $table->decimal('longitude', 11, 8)->nullable()->change();
            $table->text('about_us')->nullable()->change();
            $table->json('metadata')->nullable()->change();
            $table->string('currency_code')->nullable()->change();
            $table->string('country_code')->nullable()->change();
            if (Schema::hasColumn('stores', 'permissions')) {
                $table->dropColumn('permissions');
            }
            if (Schema::hasColumn('stores', 'shipping_charge_priority')) {
                $table->dropColumn('shipping_charge_priority');
            }
            if (Schema::hasColumn('stores', 'shipping_min_free_delivery_amount')) {
                $table->dropColumn('shipping_min_free_delivery_amount');
            }
            if (Schema::hasColumn('stores', 'allowed_order_per_time_slot')) {
                $table->dropColumn('allowed_order_per_time_slot');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            // Re-add the store_url column
            if (!Schema::hasColumn('stores', 'store_url')) {
                $table->string('store_url')->nullable();
            }

            // Re-add the carrier_partner column
            if (!Schema::hasColumn('stores', 'carrier_partner')) {
                $table->string('carrier_partner')->nullable();
            }

            // Re-add the shipping_preference column
            if (!Schema::hasColumn('stores', 'shipping_preference')) {
                $table->string('shipping_preference')->nullable();
            }
            // Re-add the address_proof column
            if (!Schema::hasColumn('stores', 'address_proof')) {
                $table->string('address_proof')->nullable();
            }
            // Re-add the voided_check column
            if (!Schema::hasColumn('stores', 'voided_check')) {
                $table->string('voided_check')->nullable();
            }
            // Re-add the permissions column
            if (!Schema::hasColumn('stores', 'permissions')) {
                $table->string('permissions')->nullable();
            }
            // Re-add the shipping_charge_priority column
            if (!Schema::hasColumn('stores', 'shipping_charge_priority')) {
                $table->string('shipping_charge_priority')->nullable();
            }
            // Re-add the shipping_min_free_delivery_amount column
            if (!Schema::hasColumn('stores', 'shipping_min_free_delivery_amount')) {
                $table->string('shipping_min_free_delivery_amount')->nullable();
            }
            // Re-add the allowed_order_per_time_slot column
            if (!Schema::hasColumn('stores', 'allowed_order_per_time_slot')) {
                $table->string('allowed_order_per_time_slot')->nullable();
            }
            $table->json('metadata')->nullable()->change();
            $table->decimal('latitude', 10, 8)->nullable()->change();
            $table->decimal('longitude', 11, 8)->nullable()->change();
            $table->text('about_us')->nullable()->change();
            $table->string('currency_code')->change();
            $table->string('country_code')->change();
        });
    }
};
