<?php

namespace App\Enums;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

/**
 * Enum values for active and inactive status.
 * @method static INSTANT()
 * @method static CASHBACK()
 */
enum PromoModeEnum: string
{
    use InvokableCases, Values, Names;


    case INSTANT = 'instant';
    case CASHBACK = 'cashback';
}
