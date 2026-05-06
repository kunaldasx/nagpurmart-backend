<?php

namespace App\Enums\DeliveryBoy;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

/**
 * @method static PENDING()
 * @method static PAID()
 */
enum EarningPaymentStatusEnum: string
{
    use InvokableCases, Values, Names;

    case PENDING = 'pending';
    case PAID = 'paid';
}
