<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seller_order_popup_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained()->cascadeOnDelete();
            $table->foreignId('seller_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique('seller_order_id');
            $table->index(['user_id', 'status'], 'seller_popup_offer_user_status_index');
            $table->index(['seller_id', 'status'], 'seller_popup_offer_seller_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_order_popup_offers');
    }
};
