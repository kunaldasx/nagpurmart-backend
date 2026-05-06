<?php

namespace App\Enums\Product;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

enum ProductStatusEnum: string
{
    use InvokableCases, Values, Names;

    case ACTIVE = 'active';
    case DRAFT = 'draft';
}
