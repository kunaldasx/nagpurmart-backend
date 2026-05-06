<?php

namespace App\Enums;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

enum StockInventoryTypeEnum: string
{
    use InvokableCases, Values, Names;

    /**
     * Enum values for active and inactive status.
     * @method static ADD()
     * @method static REMOVE()
     * @method static ADJUST()
     */
    case ADD = 'add';
    case REMOVE = 'remove';
    case ADJUST = 'adjust';
}
