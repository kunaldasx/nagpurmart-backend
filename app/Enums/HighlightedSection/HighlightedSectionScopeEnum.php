<?php

namespace App\Enums\HighlightedSection;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

enum HighlightedSectionScopeEnum: string
{
    use InvokableCases, Values, Names;

    case GLOBAL = 'global';
    case CATEGORY = 'category';
}