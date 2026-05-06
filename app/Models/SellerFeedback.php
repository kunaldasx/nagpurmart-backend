<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SellerFeedback extends Model
{
    protected $fillable = [
        'user_id',
        'seller_id',
        'order_id',
        'order_item_id',
        'store_id',
        'rating',
        'title',
        'slug',
        'description',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
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

    public static function getSellerFeedbackStatistics($sellerId): SellerFeedback
    {
        return SellerFeedback::where('seller_id', $sellerId)
            ->selectRaw('
                COUNT(*) as total_reviews,
                AVG(rating) as average_rating,
                SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star_count,
                SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star_count,
                SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star_count,
                SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star_count,
                SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star_count
            ')
            ->first();
    }

//    public function setTitleAttribute($value): void
//    {
//        $this->attributes['title'] = $value;
//        $slug = Str::slug($value);
//        $baseSlug = $slug;
//        $counter = 1;
//
//        // Ensure slug is unique
//        while (SellerFeedback::where('slug', $slug)->exists()) {
//            $slug = $baseSlug . '-' . $counter++;
//        }
//        $this->attributes['slug'] = $slug;
//    }
}
