<?php

namespace App\Enums\Order;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

/**
 * @method static CANCELLED()
 * @method static REQUESTED()
 * @method static SELLER_APPROVED()
 * @method static SELLER_REJECTED()
 * @method static PICKUP_ASSIGNED()
 * @method static PICKED_UP()
 * @method static RECEIVED_BY_SELLER()
 * @method static REFUND_PROCESSED()
 * @method static COMPLETED()
 */
enum OrderItemReturnStatusEnum: string
{
    use InvokableCases, Names, Values;

    case CANCELLED = 'cancelled';
   case REQUESTED = 'requested';
   case SELLER_APPROVED = 'seller_approved';
   case SELLER_REJECTED = 'seller_rejected';
   case PICKUP_ASSIGNED = 'pickup_assigned';
   case PICKED_UP = 'picked_up';
   case RECEIVED_BY_SELLER = 'received_by_seller';
   case REFUND_PROCESSED = 'refund_processed';
   case COMPLETED = 'completed';
}
