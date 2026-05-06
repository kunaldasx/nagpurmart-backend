<?php

namespace App\Enums\Product;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

/**
 * Context type for Sidebar Filters API
 * - featured_section: limit filters by categories assigned to the featured section (by slug)
 * - category: limit filters to a specific category (by slug)
 * - brand: limit filters to a specific brand (by slug)
 * - store: limit filters to a specific store (by slug)
 * - search: limit filters to a search term
 * @method static STORE()
 * @method static SEARCH()
 * @method static FEATURED_SECTION()
 * @method static CATEGORY()
 * @method static BRAND()
 */
enum SidebarFilterContextTypeEnum: string
{
    use InvokableCases, Values, Names;

    case FEATURED_SECTION = 'featured_section';
    case CATEGORY = 'category';
    case BRAND = 'brand';
    case STORE = 'store';
    case SEARCH = 'search';

    public static function values(): array
    {
        return array_map(fn(self $e) => $e->value, self::cases());
    }
}
