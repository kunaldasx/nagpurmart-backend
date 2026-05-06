<?php

namespace App\Enums\Product;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

enum ProductIndicatorEnum: string
{
    use InvokableCases, Values, Names;

    case VEG = 'veg';
    case NON_VEG = 'non_veg';
}
