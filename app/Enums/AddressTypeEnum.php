<?php

namespace App\Enums;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

/**
 * Enum values for active and inactive status.
 * @method static HOME()
 * @method static OFFICE()
 * @method static OTHER()
 */
enum AddressTypeEnum: string
{
    use InvokableCases, Values, Names;

    case HOME = 'home';
    case OFFICE = 'office';
    case OTHER = 'other';
}
