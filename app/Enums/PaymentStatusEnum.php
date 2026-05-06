<?php

namespace App\Enums;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

/**
 * @method static PENDING()
 * @method static COMPLETED()
 * @method static FAILED()
 * @method static REFUNDED()
 * @method static PARTIALLY_REFUNDED()
 */
enum PaymentStatusEnum: string
{
    use InvokableCases, Values, Names;

    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';
    case PARTIALLY_REFUNDED = 'partially_refunded';
}
