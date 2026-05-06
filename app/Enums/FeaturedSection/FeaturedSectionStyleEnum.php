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
enum FeaturedSectionStyleEnum: string
{
    use InvokableCases, Values, Names;

    case WITH_BACKGROUND = 'with_background';
    case WITHOUT_BACKGROUND = 'without_background';
}
