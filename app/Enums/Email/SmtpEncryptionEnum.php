<?php

namespace App\Enums\Email;

use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Names;
use ArchTech\Enums\Values;

enum SmtpEncryptionEnum: string
{
    use InvokableCases, Values, Names;
    case Ssl = 'ssl';
    case Tls = 'tls';
}
