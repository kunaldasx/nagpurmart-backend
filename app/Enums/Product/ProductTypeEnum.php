<?php

namespace App\Enums\Product;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

/**
 * @method static VARIANT()
 */
enum ProductTypeEnum: string
{
    use InvokableCases, Values, Names;

    case SIMPLE = 'simple';
    case VARIANT = 'variant';
}
