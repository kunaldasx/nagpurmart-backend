<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerSubscriptionSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_subscription_id',
        'plan_name',
        'price',
        'duration_days',
        'limits_json'
    ];

    protected $casts = [
        'limits_json' => 'array',
        'price' => 'decimal:2'
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(SellerSubscription::class, 'seller_subscription_id');
    }
}
