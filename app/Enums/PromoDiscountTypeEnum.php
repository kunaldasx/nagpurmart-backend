<?php

namespace App\Enums;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;
enum PromoDiscountTypeEnum: string
{
    use InvokableCases, Values, Names;

    /**
     * Enum values for active and inactive status.
     * @method static PERCENTAGE()
     * @method static FIXED()
     */
    case PERCENTAGE = 'percent';
    case FREE_SHIPPING = 'free_shipping';
    case FIXED = 'flat';
}
