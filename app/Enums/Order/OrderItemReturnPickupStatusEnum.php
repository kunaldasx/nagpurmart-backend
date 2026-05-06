<?php

namespace App\Enums\Order;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

/**
 * @method static PENDING()
 * @method static ASSIGNED()
 * @method static PICKED_UP()
 * @method static DELIVERED_TO_SELLER()
 * @method static CANCELLED()
 */
enum OrderItemReturnPickupStatusEnum: string
{
    use InvokableCases, Names, Values;

    case PENDING = 'pending';
    case ASSIGNED = 'assigned';
    case PICKED_UP = 'picked_up';
    case DELIVERED_TO_SELLER = 'delivered_to_seller';
    case CANCELLED = 'cancelled';
}
