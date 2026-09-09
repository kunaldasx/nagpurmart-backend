<?php

namespace App\Enums\HighlightedSection;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

enum HighlightedSectionItemTypeEnum: string
{
    use InvokableCases, Values, Names;

    case PRODUCT = 'product';
    case CATEGORY = 'category';
    case BRAND = 'brand';
}