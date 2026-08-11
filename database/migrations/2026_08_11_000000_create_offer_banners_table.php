<?php

use App\nEnums\Banner\BannerPositionEnum;
use App\Enums\Banner\BannerVisibilityStatusEnum;
use App\Enums\HomePageScopeEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('offer_banners', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->enum('scope_type', HomePageScopeEnum::values())->default(HomePageScopeEnum::GLOBAL());
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->enum('position', BannerPositionEnum::values())->default(BannerPositionEnum::TOP());
            $table->enum('visibility_status', BannerVisibilityStatusEnum::values())->default(BannerVisibilityStatusEnum::DRAFT());
            $table->unsignedInteger('display_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('scope_id')->references('id')->on('categories')->onDelete('cascade');
            $table->index(['scope_type', 'scope_id']);
            $table->index('position');
            $table->index('visibility_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offer_banners');
    }
};
