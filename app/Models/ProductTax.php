<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductTax extends Model
{
    use HasFactory;

    protected $table = 'product_taxes';

    protected $fillable = [
        'product_id',
        'tax_class_id',
    ];

    /**
     * Get the product that owns this ProductTax.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the tax class that owns this ProductTax.
     */
    public function taxClass(): BelongsTo
    {
        return $this->belongsTo(TaxClass::class);
    }
}
