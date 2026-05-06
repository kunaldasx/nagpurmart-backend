<?php

namespace App\Models;

use App\Enums\SpatieMediaCollectionName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Review extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $appends = ['review_images'];
    protected $fillable = [
        'user_id',
        'product_id',
        'order_id',
        'order_item_id',
        'store_id',
        'rating',
        'title',
        'slug',
        'comment',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function getReviewImagesAttribute()
    {
        return $this->getMedia(SpatieMediaCollectionName::REVIEW_IMAGES())
            ->map(function ($media) {
                return $media->getUrl();
            })->toArray();
    }

    public function setTitleAttribute($value): void
    {
        $this->attributes['title'] = $value;
        $this->attributes['slug'] = generateUniqueSlug(self::class, $value);
    }

    public static function scopeProductRatingStats(int $id)
    {
        return self::query()->where('product_id', $id)
            ->selectRaw('
            COUNT(*) as total_reviews,
            ROUND(AVG(rating), 1) as average_rating,
            SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star_count,
            SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star_count,
            SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star_count,
            SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star_count,
            SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star_count
        ')
            ->first();
    }

    protected static function booted(): void
    {
        static::deleting(function ($review) {
            $review->clearMediaCollection('review_images');
        });
    }
}
