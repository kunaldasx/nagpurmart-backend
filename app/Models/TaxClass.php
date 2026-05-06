<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @method static create(array $array)
 * @method static count()
 * @method static find($id)
 */
class TaxClass extends Model
{
//    use SoftDeletes;

    protected $table = 'tax_classes';

    protected $fillable = [
        'title',
    ];

    /**
     * The tax rates that belong to the tax class.
     */
    public function taxRates()
    {
        return $this->belongsToMany(
            TaxRate::class,
            'tax_class_tax_rate',
            'tax_class_id',
            'tax_rate_id'
        )->withTimestamps();
    }
}
