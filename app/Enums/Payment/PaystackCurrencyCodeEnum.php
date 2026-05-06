<?php

namespace App\Enums\Payment;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;
enum PaystackCurrencyCodeEnum: string
{
    use InvokableCases, Names, Values;

    case NGN = 'NGN';
    case GHS = 'GHS';
    case ZAR = 'ZAR';
    case USD = 'USD';
}
