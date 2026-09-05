<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class OfferBannerTemplate extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'preview_path', 'is_active', 'display_order'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $appends = ['preview_url'];

    public function getPreviewUrlAttribute(): string
    {
        if (str_starts_with($this->preview_path, 'assets/')) {
            return asset($this->preview_path);
        }

        return Storage::disk('public')->url($this->preview_path);
    }
}