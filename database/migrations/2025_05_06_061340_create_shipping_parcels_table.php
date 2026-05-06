<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shipping_parcels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->foreignId('delivery_boy_id')->nullable()->constrained()->onDelete('set null');
            $table->unsignedBigInteger('shipment_id')->nullable();
            $table->unsignedBigInteger('external_shipment_id')->nullable();
            $table->unsignedBigInteger('carrier_id')->nullable();
            $table->unsignedBigInteger('manifest_id')->nullable();
            $table->string('manifest_url')->nullable();
            $table->string('service_code')->nullable();
            $table->unsignedBigInteger('label_id')->nullable();
            $table->string('label_url')->nullable();
            $table->string('invoice_url')->nullable();
            $table->unsignedBigInteger('tracking_id');
            $table->string('tracking_url')->nullable();
            $table->string('shipment_cost_currency', 10);
            $table->decimal('shipment_cost', 10, 2);
            $table->float('weight')->nullable();
            $table->float('height')->nullable();
            $table->float('breadth')->nullable();
            $table->float('length')->nullable();
            $table->enum('status', ['pending', 'shipped', 'out_for_delivery', 'delivered'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_parcels');
    }
};
