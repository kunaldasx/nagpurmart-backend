<?php

namespace App\Enums\HighlightedSection;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

enum HighlightedSectionTemplateEnum: string
{
    use InvokableCases, Values, Names;

    case TRUSTED_BRANDS = 'trusted_brands';
    case WARM_AND_COSY = 'warm_and_cozy';
    case CURATED_PICKS = 'curated_picks';
    case RAIN_READY = 'rain_ready';
    case SPOTLIGHT = 'spotlight';
}