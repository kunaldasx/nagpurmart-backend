<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static create(mixed $validated)
 * @method static count()
 * @method static find($id)
 */
class TaxRate extends Model
{
    protected $table = 'tax_rates';

    protected $fillable = [
        'title',
        'rate',
    ];

    /**
     * The tax classes that belong to the tax rate.
     */
    public function taxClasses()
    {
        return $this->belongsToMany(
            TaxClass::class,
            'tax_class_tax_rate',
            'tax_rate_id',
            'tax_class_id'
        )->withTimestamps();
    }
}
