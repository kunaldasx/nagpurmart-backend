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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('slug', 100)->unique();
            $table->string('email');
            $table->string('ip_address', 45);
            $table->string('currency_code', 3)->default('USD');
            $table->decimal('currency_rate', 10, 6);
            $table->string('payment_method');
            $table->string('payment_status');
            $table->enum('fulfillment_type', ['hyperlocal', 'regular'])->default('hyperlocal');
            $table->integer('estimated_delivery_time')->nullable();
            $table->foreignId('delivery_time_slot_id')->nullable()->constrained();
            $table->foreignId('delivery_boy_id')->nullable()->constrained();
            $table->decimal('wallet_balance', 12, 2);
            $table->string('promo_code', 50)->nullable();
            $table->decimal('promo_discount', 10, 2)->default(0.00);
            $table->string('gift_card', 50)->nullable();
            $table->decimal('gift_card_discount', 10, 2)->default(0.00);
            $table->decimal('delivery_charge', 10, 2)->default(0.00);
            $table->decimal('subtotal', 12, 2);
            $table->decimal('total_payable', 12, 2);
            $table->decimal('final_total', 12, 2);

            // Billing Info
            $table->string('billing_name');
            $table->text('billing_address_1');
            $table->text('billing_address_2')->nullable();
            $table->string('billing_landmark');
            $table->string('billing_zip', 20);
            $table->string('billing_phone', 20);
            $table->enum('billing_address_type', ['home', 'office', 'other']);
            $table->decimal('billing_latitude', 10, 8);
            $table->decimal('billing_longitude', 11, 8);
            $table->string('billing_city');
            $table->string('billing_state');
            $table->string('billing_country');
            $table->string('billing_country_code', 3);

            // Shipping Info
            $table->string('shipping_name');
            $table->text('shipping_address_1');
            $table->text('shipping_address_2')->nullable();
            $table->string('shipping_landmark');
            $table->string('shipping_zip', 20);
            $table->string('shipping_phone', 20);
            $table->enum('shipping_address_type', ['home', 'office', 'other']);
            $table->decimal('shipping_latitude', 10, 8);
            $table->decimal('shipping_longitude', 11, 8);
            $table->string('shipping_city');
            $table->string('shipping_state');
            $table->string('shipping_country');
            $table->string('shipping_country_code', 3);

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
