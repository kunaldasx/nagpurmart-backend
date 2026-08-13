<?php

namespace App\Enums\Product;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

enum ProductLabelEnum: string
{
    use InvokableCases, Values, Names;

    case BEST_SELLER = 'Best Seller';
    case TOP_RATED = 'Top Rated';
    case LOWEST_PRICE = 'Lowest Price';
    case NEW_ARRIVAL = 'New Arrival';
    case HOT_DEAL = 'Hot Deal';
    case LIMITED_STOCK = 'Limited Stock';
    case FRESH_PICK = 'Fresh Pick';
    case VALUE_PICK = 'Value Pick';
    case PREMIUM = 'Premium';
}
