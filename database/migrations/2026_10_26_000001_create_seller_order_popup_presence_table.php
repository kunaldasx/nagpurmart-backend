<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seller_order_popup_presence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->boolean('active')->default(false);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('last_offer_at')->nullable();
            $table->timestamps();

            $table->unique(['seller_id', 'user_id']);
            $table->index(['seller_id', 'active', 'last_seen_at'], 'seller_popup_presence_eligibility_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_order_popup_presence');
    }
};
