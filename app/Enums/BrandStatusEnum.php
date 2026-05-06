<?php

namespace App\Enums;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

enum BrandStatusEnum: string
{
    use InvokableCases, Values, Names;

    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}
