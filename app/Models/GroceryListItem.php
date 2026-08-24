<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class GroceryListItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'grocery_list_id', 'product_id', 'extracted_name', 'normalized_name',
        'quantity', 'unit', 'confidence',
    ];

    protected function casts(): array
    {
        return ['quantity' => 'float', 'confidence' => 'float'];
    }

    public function groceryList(): BelongsTo
    {
        return $this->belongsTo(GroceryList::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}