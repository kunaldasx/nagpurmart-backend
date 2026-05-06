<?php

namespace App\Enums;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

/**
 * Enum values for active and inactive status.
 * @method static SINGLE()
 * @method static MULTIPLE()
 */
enum SystemVendorTypeEnum: string
{
    use InvokableCases, Values, Names;

    case SINGLE = 'single';
    case MULTIPLE = 'multiple';
}
