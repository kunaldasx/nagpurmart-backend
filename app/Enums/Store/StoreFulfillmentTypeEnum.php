<?php

namespace App\Enums\Store;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

/**
 * Enum representing the fulfillment types for a store.
 *
 * This enum defines the different fulfillment types that a store can have,
 * such as hyperlocal, regular, or both.
 * @method static HYPERLOCAL()
 * @method static REGULAR()
 * @method static BOTH()
 */
enum StoreFulfillmentTypeEnum: string
{
    use InvokableCases, Values, Names;
    case HYPERLOCAL = 'hyperlocal';
    case REGULAR = 'regular';
    case BOTH = 'both';
}
