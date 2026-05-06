<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CustomProductSection extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'custom_product_sections';

    protected $fillable = [
        'uuid',
        'product_id',
        'title',
        'description',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function setTitleAttribute($value): void
    {
        $this->attributes['title'] = $value;
        if (empty($this->attributes['uuid'])) {
            $this->attributes['uuid'] = (string) Str::uuid();
        }
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function fields(): BelongsToMany
    {
        return $this->belongsToMany(CustomProductField::class, 'custom_product_section_field')
            ->withPivot('sort_order')
            ->withTimestamps();
    }
}
