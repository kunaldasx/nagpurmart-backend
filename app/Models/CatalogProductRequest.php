<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatalogProductRequest extends Model
{
    protected $fillable = [
        'seller_id',
        'user_id',
        'product_name',
        'brand_name',
        'category_name',
        'description',
        'reference_url',
        'request_note',
        'status',
        'admin_remark',
        'processed_by',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'seller_id' => 'integer',
            'user_id' => 'integer',
            'processed_by' => 'integer',
            'processed_at' => 'datetime',
        ];
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}