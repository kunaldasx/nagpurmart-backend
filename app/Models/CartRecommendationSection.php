<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CartRecommendationSection extends Model
{
    protected $fillable = ['heading', 'is_tabular', 'status', 'sort_order'];

    protected $casts = [
        'is_tabular' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'cart_recommendation_section_product')
            ->withPivot('sort_order')
            ->orderBy('cart_recommendation_section_product.sort_order');
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
