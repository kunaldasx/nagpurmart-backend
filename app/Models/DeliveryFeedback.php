<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeliveryFeedback extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'delivery_feedback';

    protected $fillable = [
        'user_id',
        'order_id',
        'delivery_boy_id',
        'title',
        'slug',
        'description',
        'rating',
    ];

    /**
     * Relationships
     */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function deliveryBoy(): BelongsTo
    {
        return $this->belongsTo(DeliveryBoy::class, 'delivery_boy_id');
    }

    /**
     * Get delivery feedback statistics for a delivery boy.
     *
     * @param int $deliveryBoyId
     * @return DeliveryFeedback|null
     */
    public static function getDeliveryFeedbackStatistics(int $deliveryBoyId): ?DeliveryFeedback
    {
        return self::where('delivery_boy_id', $deliveryBoyId)
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
}
