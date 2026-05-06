<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductFaq extends Model
{
    protected $fillable = ['product_id', 'question', 'answer', 'status'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
    public function seller()
    {
        return Product::find($this->product_id)->seller();
    }
}
