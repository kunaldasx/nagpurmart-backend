<?php

namespace App\Enums\Category;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;
use Exception;

enum CategoryBackgroundTypeEnum: string
{
    use InvokableCases, Values, Names;

    case IMAGE = 'image';
    case COLOR = 'color';
}
