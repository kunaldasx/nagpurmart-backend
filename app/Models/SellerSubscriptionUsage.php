<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerSubscriptionUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'key',
        'used'
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    public static function recordUsage(int $sellerId, string $key, int $count = 1): self
    {
        $usage = self::firstOrCreate(
            [
                'seller_id' => $sellerId,
                'key' => $key
            ],
            [
                'used' => 0
            ]
        );

        $usage->increment('used', $count);

        return $usage;
    }

    public static function reduceUsage(int $sellerId, string $key, int $count = 1): self
    {
        $usage = self::firstOrCreate(
            [
                'seller_id' => $sellerId,
                'key' => $key
            ],
            [
                'used' => 0
            ]
        );

        $usage->decrement('used', $count);

        if ($usage->used < 0) {
            $usage->used = 0;
            $usage->save();
        }

        return $usage;
    }

    public static function getUsage(int $sellerId, string $key): int
    {
        return self::where('seller_id', $sellerId)
            ->where('key', $key)
            ->value('used') ?? 0;
    }
}
