<?php

namespace App\Enums;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

enum PoliciesEnum: string
{
    use InvokableCases, Values, Names;

    case REFUND_AND_RETURN = 'refund_and_return';
    case SHIPPING = 'shipping';
    case PRIVACY = 'privacy';
    case TERMS = 'terms';
    case ABOUTUS = 'aboutus';
    case DELIVERY_PRIVACY = 'delivery_privacy';
    case DELIVERY_TERMS = 'delivery_terms';
    case SELLER_PRIVACY = 'seller_privacy';
    case SELLER_TERMS = 'seller_terms';
}
