<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offer_banner_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('preview_path');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });

        $now = now();
        $templates = [];
        for ($index = 1; $index <= 8; $index++) {
            $templates[] = [
                'code' => 'T_' . $index,
                'name' => 'Template ' . $index,
                'preview_path' => 'assets/templates/T_' . $index . '.jpg',
                'is_active' => true,
                'display_order' => $index,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('offer_banner_templates')->insert($templates);
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_banner_templates');
    }
};