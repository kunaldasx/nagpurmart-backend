<?php

namespace App\Models;

use App\Enums\SpatieMediaCollectionName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class GiftSection extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'heading',
        'sub_heading',
        'bg_color',
        'font_color',
        'icon_image',
    ];

    /**
     * Get the singleton instance of the gift section
     */
    public static function getInstance(): self
    {
        return self::firstOrCreate([], [
            'heading' => 'Special Offers',
            'sub_heading' => 'Special Offers',
            'bg_color' => '#F5E6C8',
            'font_color' => '#222222',
            'icon_image' => null,
        ]);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(SpatieMediaCollectionName::GIFT_SECTION_ICON())->singleFile();
    }

    protected static function booted(): void
    {
        static::deleting(function ($giftSection) {
            $giftSection->clearMediaCollection(SpatieMediaCollectionName::GIFT_SECTION_ICON());
        });
    }
}
