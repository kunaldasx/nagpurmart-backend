<?php

namespace App\Enums\Seller;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

/**
 * @method static self PENDING()
 * @method static self SETTLED()
 */
enum SellerSettlementStatusEnum: string
{
    use InvokableCases, Values, Names;

    case PENDING = 'pending';
    case SETTLED = 'settled';
}
