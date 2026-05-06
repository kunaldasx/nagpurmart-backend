<?php

namespace App\Enums\Email;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

enum SmtpContentTypeEnum: string
{
    use InvokableCases, Values, Names;
    case Html = 'html';
    case Text = 'text';
}
