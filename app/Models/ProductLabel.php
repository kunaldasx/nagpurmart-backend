<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductLabel extends Model
{
    protected $fillable = [
        'name',
        'bg_color',
        'font_color',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
