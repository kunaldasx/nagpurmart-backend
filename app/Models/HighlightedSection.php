<?php

namespace App\Models;

use App\Enums\HighlightedSection\HighlightedSectionTemplateEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HighlightedSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'slug', 'subtitle', 'template', 'scope_type', 'scope_id',
        'background_color', 'font_color', 'sort_order', 'status',
    ];

    protected $casts = [
        'template' => HighlightedSectionTemplateEnum::class,
        'scope_id' => 'integer',
        'sort_order' => 'integer',
    ];

    public function setTitleAttribute($value): void
    {
        $this->attributes['title'] = $value;
        $this->attributes['slug'] = generateUniqueSlug(self::class, $value);
    }

    public function items(): HasMany
    {
        return $this->hasMany(HighlightedSectionItem::class)->orderBy('sort_order');
    }

    public function scopeCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'scope_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}