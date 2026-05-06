<?php

namespace App\Enums\Seller;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

/**
 * @method static self CREDIT()
 * @method static self DEBIT()
 */
enum SellerSettlementTypeEnum: string
{
    use InvokableCases, Values, Names;

    case CREDIT = 'credit';
    case DEBIT = 'debit';
}
