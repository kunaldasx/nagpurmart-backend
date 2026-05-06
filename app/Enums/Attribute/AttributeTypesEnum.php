<?php

namespace App\Enums\Attribute;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

enum AttributeTypesEnum: string
{
    use InvokableCases, Values, Names;

    /**
     * @method static COLOR()
     * @method static IMAGE()
     */
    case TEXT = 'text';
    case COLOR = 'color';
    case IMAGE = 'image';

}
