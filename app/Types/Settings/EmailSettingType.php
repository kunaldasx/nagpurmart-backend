<?php

namespace App\Types\Settings;

use App\Enums\Email\SmtpContentType;
use App\Enums\Email\SmtpContentTypeEnum;
use App\Enums\Email\SmtpEncryptionEnum;
use App\Interfaces\SettingInterface;
use App\Traits\SettingTrait;
use Illuminate\Validation\Rules\Enum;

class EmailSettingType implements SettingInterface
{
    use SettingTrait;

    public string $smtpHost = '';
    public string $smtpPort = '';
    public string $smtpEmail = '';
    public string $smtpPassword = '';
    public string $smtpEncryption = '';
    public string $smtpContentType = '';
    protected static function getValidationRules(): array
    {
        return [
            'smtpHost' => 'required',
            'smtpPort' => 'required',
            'smtpEmail' => 'required',
            'smtpPassword' => 'required',
            'smtpEncryption' => ['required',new Enum(SmtpEncryptionEnum::class)],
            'smtpContentType' => ['required',new Enum(SmtpContentTypeEnum::class)],
        ];
    }
}
