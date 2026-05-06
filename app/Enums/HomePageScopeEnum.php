<?php

namespace App\Enums;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

/**
 * Enum values for active and inactive status.
 * @method static GLOBAL()
 * @method static CATEGORY()
 */
enum HomePageScopeEnum: string
{
    use InvokableCases, Values, Names;

    case GLOBAL = 'global';
    case CATEGORY = 'category';
}
