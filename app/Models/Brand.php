<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @method static create(mixed $validated)
 * @method static find($id)
 * @method static count()
 */
class Brand extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $appends = ['logo'];
    protected $fillable = [
        'uuid',
        'slug',
        'title',
        'description',
        'status',
        'metadata',
        'scope_type',
        'scope_id'
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function setTitleAttribute($value): void
    {
        $this->attributes['title'] = $value;
        $this->attributes['slug'] = generateUniqueSlug(self::class, $value);
        $this->attributes['uuid'] = (string)Str::uuid();
    }

    public function getLogoAttribute(): string
    {
        return $this->getFirstMediaUrl('brand');
    }

    public function scopeCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'scope_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    // Query scopes for filtering
    public function scopeGlobal($query)
    {
        return $query->where('scope_type', 'global');
    }

    public static function scopeByCategory($query, $categoryId = null)
    {
        $query = $query->where('scope_type', 'category');
        if ($categoryId) {
            $query->where('scope_id', $categoryId);
        }
        return $query;
    }

    public function scopeByScopeType($query, $scopeType)
    {
        return $query->where('scope_type', $scopeType);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('brand')->singleFile();
    }

    protected static function booted(): void
    {
        static::deleting(function ($category) {
            $category->clearMediaCollection('brand');
        });
    }
}
