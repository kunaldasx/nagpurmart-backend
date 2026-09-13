<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('gift_sections', function (Blueprint $table) {
            $table->id();
            $table->string('heading')->default('Special Offers');
            $table->string('sub_heading')->default('Special Offers');
            $table->string('bg_color')->default('#F5E6C8');
            $table->string('font_color')->default('#222222');
            $table->string('icon_image')->nullable();
            $table->timestamps();
        });

        // Insert default record
        DB::table('gift_sections')->insert([
            'heading' => 'Special Offers',
            'sub_heading' => 'Special Offers',
            'bg_color' => '#F5E6C8',
            'font_color' => '#222222',
            'icon_image' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('gift_sections');
    }
};
