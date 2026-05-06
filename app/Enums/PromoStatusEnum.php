<?php

namespace App\Enums;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

/**
 * Enum values for active and inactive status.
 * @method static ACTIVE()
 * @method static EXPIRED()
 */
enum PromoStatusEnum: string
{
    use InvokableCases, Values, Names;


    case ACTIVE = 'active';
    case EXPIRED = 'expired';
}
