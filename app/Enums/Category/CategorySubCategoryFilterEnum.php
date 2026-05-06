<?php

namespace App\Enums\Category;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

enum CategorySubCategoryFilterEnum: string
{
    use InvokableCases, Values, Names;

    case RANDOM = 'random';
    case TOP_CATEGORY = 'top_category';
}
