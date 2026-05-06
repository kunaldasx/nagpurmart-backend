<?php

namespace App\Models;

use App\Enums\PromoStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Promo extends Model
{
    use SoftDeletes;

    protected $table = 'promo';

    protected $appends = ['status'];

    protected $fillable = [
        'code',
        'description',
        'start_date',
        'end_date',
        'discount_type',
        'discount_amount',
        'promo_mode',
        'usage_count',
        'individual_use',
        'max_total_usage',
        'max_usage_per_user',
        'min_order_total',
        'max_discount_value',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'discount_amount' => 'decimal:2',
        'min_order_total' => 'decimal:2',
        'max_discount_value' => 'decimal:2',
        'usage_count' => 'integer',
        'individual_use' => 'integer',
        'max_total_usage' => 'integer',
        'max_usage_per_user' => 'integer',
    ];

    /**
     * Get the order promo lines for this promo.
     */
    public function orderPromoLines(): HasMany
    {
        return $this->hasMany(OrderPromoLine::class);
    }

    public function getStatusAttribute(): string
    {
        $now = now();

        // Check date validity
        $isDateValid = (is_null($this->start_date) || $this->start_date <= $now) &&
            (is_null($this->end_date) || $this->end_date >= $now);

        // Check usage limits
        $isUsageLimitValid = is_null($this->max_total_usage) ||
            $this->usage_count < $this->max_total_usage;

        return ($isDateValid && $isUsageLimitValid) ? PromoStatusEnum::ACTIVE() : PromoStatusEnum::EXPIRED();
    }
}
