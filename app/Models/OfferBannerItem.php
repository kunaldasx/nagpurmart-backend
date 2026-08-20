<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferBannerItem extends Model
{
    use HasFactory;

    protected $fillable = ['offer_banner_id', 'title', 'subtitle', 'item_type', 'item_id', 'metadata'];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function offerBanner(): BelongsTo
    {
        return $this->belongsTo(OfferBanner::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'item_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'item_id');
    }
}
