<?php

namespace App\Enums\DeliveryBoy;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

/**
 * @method static ASSIGNED()
 * @method static IN_PROGRESS()
 * @method static COMPLETED()
 * @method static CANCELED()
 */
enum DeliveryBoyAssignmentStatusEnum: string
{
    use InvokableCases, Values, Names;
    case ASSIGNED = 'assigned';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELED = 'canceled';
}
