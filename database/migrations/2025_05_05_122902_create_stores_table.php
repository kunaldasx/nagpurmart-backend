<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('sellers')->onDelete('cascade');
            $table->foreignId('delivery_zone_id')->nullable()->constrained('delivery_zones')->onDelete('set null');
            $table->string('name');
            $table->string('slug', 300)->unique();
            $table->string('address');
            $table->string('city', 100);
            $table->string('landmark', 100);
            $table->string('state', 100);
            $table->string('zipcode', 20);
            $table->string('country', 100);
            $table->string('country_code', 10);
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->string('contact_email', 50);
            $table->string('contact_number', 20);
            $table->text('description')->nullable();
            $table->string('store_url')->nullable();
            $table->string('timing', 500)->nullable();
            $table->text('address_proof');
            $table->text('voided_check');
            $table->string('tax_name', 250);
            $table->string('tax_number', 250);
            $table->string('bank_name', 250);
            $table->string('bank_branch_code', 250);
            $table->string('account_holder_name', 250);
            $table->string('account_number', 250);
            $table->string('routing_number', 250);
            $table->enum('bank_account_type', ['checking', 'savings']);
            $table->string('currency_code', 3);
            $table->text('permissions')->nullable();
            $table->json('time_slot_config')->nullable();
            $table->double('max_delivery_distance')->default(10.0);
            $table->double('shipping_min_free_delivery_amount')->default(0);
            $table->string('shipping_charge_priority')->nullable();
            $table->integer('allowed_order_per_time_slot')->nullable();
            $table->integer('order_preparation_time')->default(15);
            $table->text('carrier_partner')->nullable();
            $table->string('promotional_text', 1024)->nullable();
            $table->text('about_us');
            $table->text('return_replacement_policy')->nullable();
            $table->text('refund_policy')->nullable();
            $table->text('terms_and_conditions')->nullable();
            $table->text('delivery_policy')->nullable();
            $table->text('shipping_preference')->nullable();
            $table->decimal('domestic_shipping_charges', 10)->nullable();
            $table->decimal('international_shipping_charges', 10)->nullable();
            $table->json('metadata');
            $table->enum('verification_status', ['approved', 'not_approved']);
            $table->enum('visibility_status', ['visible', 'draft'])->default('draft');
            $table->enum('fulfillment_type', ['hyperlocal', 'regular', 'both'])->default('hyperlocal');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('stores');
    }
};
