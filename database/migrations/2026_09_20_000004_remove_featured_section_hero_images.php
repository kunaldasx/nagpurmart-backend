<?php

use App\Models\FeaturedSection;
use Illuminate\Database\Migrations\Migration;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

return new class extends Migration {
    public function up(): void
    {
        Media::query()
            ->where('model_type', (new FeaturedSection())->getMorphClass())
            ->whereIn('collection_name', [
                'featured_section_hero_image',
                'featured_section_powered_by_image',
            ])
            ->get()
            ->each(fn (Media $media) => $media->delete());
    }

    public function down(): void
    {
        // Removed media cannot be restored by a migration rollback.
    }
};