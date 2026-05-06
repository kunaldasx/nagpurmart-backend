<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class CustomProductField extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $table = 'custom_product_fields';

    protected $appends = ['image'];

    protected $fillable = [
        'uuid',
        'title',
        'description',
    ];

    public function setTitleAttribute($value): void
    {
        $this->attributes['title'] = $value;
        if (empty($this->attributes['uuid'])) {
            $this->attributes['uuid'] = (string) Str::uuid();
        }
    }

    public function getImageAttribute(): ?string
    {
        return $this->getFirstMediaUrl('image');
    }

    public function sections(): BelongsToMany
    {
        return $this->belongsToMany(CustomProductSection::class, 'custom_product_section_field')
            ->withPivot('sort_order')
            ->withTimestamps();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
    }
}
