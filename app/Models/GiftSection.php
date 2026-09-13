<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GiftSection extends Model
{
    use HasFactory;

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
}
