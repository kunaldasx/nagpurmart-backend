<?php

namespace App\Enums\Product;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

enum ProductVideoTypeEnum: string
{
    use InvokableCases, Values, Names;

    case YOUTUBE = 'youtube';
    case LOCAL = 'self_hosted';
}
