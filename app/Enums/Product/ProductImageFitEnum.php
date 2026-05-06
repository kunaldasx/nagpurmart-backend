<?php

namespace App\Enums\Product;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

enum ProductImageFitEnum: string
{
    use InvokableCases, Values, Names;

    case COVER = 'cover';
    case CONTAIN = 'contain';
}
