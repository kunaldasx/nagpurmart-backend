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
 */
enum FeaturedSectionTypeEnum: string
{
    use InvokableCases, Values, Names;

    case NEWLY_ADDED = 'newly_added';
    case TOP_RATED = 'top_rated';
    case BEST_SELLER = 'best_seller';
    case FEATURED = 'featured';
}
