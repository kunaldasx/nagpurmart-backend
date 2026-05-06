<?php

namespace App\Enums\Order;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

/**
 * @method static PENDING()
 * @method static AWAITING_STORE_RESPONSE()
 * @method static PREPARING()
 * @method static COLLECTED()
 * @method static DELIVERED()
 * @method static CANCELLED()
 * @method static FAILED()
 * @method static PARTIALLY_ACCEPTED()
 * @method static REJECTED_BY_SELLER()
 * @method static ACCEPTED_BY_SELLER()
 * @method static READY_FOR_PICKUP()
 * @method static ASSIGNED()
 * @method static OUT_FOR_DELIVERY()
 */
enum OrderStatusEnum: string
{
    use InvokableCases, Names, Values;

    case PENDING = 'pending';
    case AWAITING_STORE_RESPONSE = "awaiting_store_response";
    case PARTIALLY_ACCEPTED = "partially_accepted";
    case REJECTED_BY_SELLER = "rejected_by_seller";
    case ACCEPTED_BY_SELLER = "accepted_by_seller";
    case READY_FOR_PICKUP = 'ready_for_pickup';
    case ASSIGNED = 'assigned';
    case PREPARING = 'preparing';
    case COLLECTED = 'collected';
    case OUT_FOR_DELIVERY = 'out_for_delivery';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
    case FAILED = 'failed';

    public static function orderFilters(): array
    {
        return [
            self::PENDING(),
            self::AWAITING_STORE_RESPONSE(),
            self::PARTIALLY_ACCEPTED(),
            self::REJECTED_BY_SELLER(),
            self::ACCEPTED_BY_SELLER(),
            self::READY_FOR_PICKUP(),
            self::ASSIGNED(),
            self::PREPARING(),
            self::OUT_FOR_DELIVERY(),
            self::DELIVERED(),
            self::CANCELLED(),
            self::FAILED(),
        ];
    }
}
