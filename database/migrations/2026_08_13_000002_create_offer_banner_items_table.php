<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offer_banner_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_banner_id')->constrained('offer_banners')->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->string('item_type')->nullable(); // product|category
            $table->unsignedBigInteger('item_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_banner_items');
    }
};
