<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryProductCondition extends Model
{
    protected $fillable = ['category_id', 'product_condition_id'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function condition(): BelongsTo
    {
        return $this->belongsTo(ProductCondition::class, 'product_condition_id');
    }
}

