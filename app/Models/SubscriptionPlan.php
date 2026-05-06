<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'duration_type',
        'duration_days',
        'is_free',
        'is_default',
        'is_recommended',
        'status'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_free' => 'boolean',
        'is_default' => 'boolean',
        'is_recommended' => 'boolean',
        'status' => 'boolean'
    ];

    public function limits(): HasMany
    {
        return $this->hasMany(SubscriptionPlanLimit::class, 'plan_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(SellerSubscription::class, 'plan_id');
    }
}
