<?php

namespace App\Enums\FeaturedSection;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

/**
 * @method static NEWLY_ADDED()
 * @method static TOP_RATED()
 * @method static FEATURED()
 * @method static BEST_SELLER()
 * @method static BEST_PRICE()
 * @method static LOWEST_PRICE()
 */
enum FeaturedSectionTypeEnum: string
{
    use InvokableCases, Values, Names;

    case NEWLY_ADDED = 'newly_added';
    case TOP_RATED = 'top_rated';
    case BEST_SELLER = 'best_seller';
    case FEATURED = 'featured';
    case BEST_PRICE = 'best_price';
    case LOWEST_PRICE = 'lowest_price';
}
