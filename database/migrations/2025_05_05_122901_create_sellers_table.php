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
        Schema::create('sellers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('address', 255);
            $table->string('city', 100);
            $table->string('landmark', 100);
            $table->string('state', 100);
            $table->string('zipcode', 20);
            $table->string('country', 100);
            $table->string('country_code', 10);
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->text('business_license');
            $table->text('articles_of_incorporation');
            $table->text('national_identity_card');
            $table->text('authorized_signature');
            $table->enum('verification_status', ["approved","not_approved"]);
            $table->json('metadata');
            $table->enum('visibility_status', ["visible","draft"]);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sellers');
    }
};
