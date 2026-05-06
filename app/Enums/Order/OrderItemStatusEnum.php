<?php

namespace App\Enums\Order;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

/**
 * @method static PENDING()
 * @method static AWAITING_STORE_RESPONSE()
 * @method static ACCEPTED()
 * @method static REJECTED()
 * @method static PREPARING()
 * @method static COLLECTED()
 * @method static DELIVERED()
 * @method static RETURNED()
 * @method static REFUNDED()
 * @method static CANCELLED()
 * @method static FAILED()
 */
enum OrderItemStatusEnum: string
{
    use InvokableCases, Names, Values;

    case PENDING = 'pending';
    case AWAITING_STORE_RESPONSE = 'awaiting_store_response';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';
    case PREPARING = 'preparing';
    case COLLECTED = 'collected';
    case DELIVERED = 'delivered';
    case RETURNED = 'returned';
    case REFUNDED = 'refunded';
    case CANCELLED = 'cancelled';
    case FAILED = 'failed';
}
