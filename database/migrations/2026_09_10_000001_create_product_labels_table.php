<?php

use App\Enums\Product\ProductLabelEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_labels', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('bg_color', 7)->default('#E5E7EB');
            $table->string('font_color', 7)->default('#111827');
            $table->timestamps();
        });

        foreach (ProductLabelEnum::values() as $name) {
            DB::table('product_labels')->insert([
                'name' => $name,
                'bg_color' => '#E5E7EB',
                'font_color' => '#111827',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('product_label_id')
                ->nullable()
                ->after('label')
                ->constrained('product_labels')
                ->nullOnDelete();
        });

        DB::table('products')
            ->whereNotNull('label')
            ->orderBy('id')
            ->get(['id', 'label'])
            ->each(function (object $product): void {
                $labelId = DB::table('product_labels')
                    ->where('name', $product->label)
                    ->value('id');

                if ($labelId) {
                    DB::table('products')
                        ->where('id', $product->id)
                        ->update(['product_label_id' => $labelId]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_label_id');
        });

        Schema::dropIfExists('product_labels');
    }
};
