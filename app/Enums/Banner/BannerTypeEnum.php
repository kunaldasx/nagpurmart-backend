<?php

namespace App\Enums\Banner;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

/**
 * @method static CUSTOM()
 * @method static PRODUCT()
 * @method static CATEGORY()
 * @method static BRAND()
 */
enum BannerTypeEnum: string
{
    use InvokableCases, Values, Names;

    case PRODUCT = 'product';
    case CATEGORY = 'category';
    case BRAND = 'brand';
    case CUSTOM = 'custom';
}
