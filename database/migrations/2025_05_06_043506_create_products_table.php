<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('seller_id')->constrained('sellers')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->foreignId('brand_id')->nullable()->constrained('brands')->onDelete('set null');
            $table->foreignId('product_condition_id')->constrained('product_conditions')->onDelete('cascade');
            $table->string('provider')->nullable();
            $table->unsignedBigInteger('provider_product_id')->nullable();
            $table->string('slug', 500)->unique();
            $table->string('title');
            $table->integer('product_identity')->unique()->nullable();
            $table->enum('type', ['physical', 'digital']);
            $table->string('short_description');
            $table->text('description');
            $table->enum('indicator', ['veg', 'non_veg'])->nullable();
            $table->enum('download_allowed', ['0', '1'])->default('0');
            $table->string('download_link')->nullable();
            $table->integer('minimum_order_quantity')->default(1);
            $table->integer('quantity_step_size')->default(1);
            $table->integer('total_allowed_quantity')->default(1);
            $table->enum('is_inclusive_tax', ['0', '1'])->default('0');
            $table->enum('is_returnable', ['0', '1'])->default('0');
            $table->integer('returnable_days')->nullable();
            $table->enum('is_cancelable', ['0', '1'])->default('0');
            $table->enum('is_attachment_required', ['0', '1'])->default('0');
            $table->enum('status', ['active', 'draft'])->default('active');
            $table->enum('featured', ['0', '1'])->default('0');
            $table->enum('video_type', ['self_hosted', 'youtube', 'vimeo'])->nullable();
            $table->string('video_link')->nullable();
            $table->unsignedBigInteger('cloned_from_id')->nullable();
            $table->text('tags');
            $table->string('warranty_period')->nullable();
            $table->string('guarantee_period')->nullable();
            $table->string('made_in')->nullable();
            $table->json('metadata');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
