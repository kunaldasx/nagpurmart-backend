<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @method static where(string $string, $id)
 * @method static create(array $array)
 */
class WalletTransaction extends Model
{
    protected $fillable = [
        'wallet_id', 'user_id', 'order_id', 'store_id', 'transaction_type',
        'payment_method', 'amount', 'currency_code', 'status',
        'transaction_reference', 'description'
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'wallet_id' => 'integer',
            'user_id' => 'integer',
            'order_id' => 'integer',
            'store_id' => 'integer',
            'transaction_type' => 'string',
            'payment_method' => 'string',
            'amount' => 'decimal:2',
            'currency_code' => 'string',
            'status' => 'string',
            'transaction_reference' => 'string',
            'description' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }


    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
