<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class TaxClassTaxRate extends Pivot
{
    protected $table = 'tax_class_tax_rate';

    protected $fillable = [
        'tax_class_id',
        'tax_rate_id',
    ];
}
