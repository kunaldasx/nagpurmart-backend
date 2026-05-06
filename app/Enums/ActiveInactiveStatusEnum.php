<?php

namespace App\Enums;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

/**
 * @method static ACTIVE()
 */
enum ActiveInactiveStatusEnum: string
{
    use InvokableCases, Values, Names;

    /**
     * Enum values for active and inactive status.
     * @method static ACTIVE()
     * @method static INACTIVE()
     */
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}
