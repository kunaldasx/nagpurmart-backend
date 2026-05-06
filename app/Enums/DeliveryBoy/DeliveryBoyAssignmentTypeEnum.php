<?php

namespace App\Enums\DeliveryBoy;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

/**
 * @method static DELIVERY()
 * @method static RETURN_PICKUP()
 */
enum DeliveryBoyAssignmentTypeEnum: string
{
    use InvokableCases, Values, Names;
    case DELIVERY = 'delivery';
    case RETURN_PICKUP = 'return_pickup';
}
