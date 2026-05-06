<?php

namespace App\Types\Settings;

use App\Interfaces\SettingInterface;
use App\Traits\SettingTrait;

class DeliveryBoySettingType implements SettingInterface
{
    use SettingTrait;

    public string $termsCondition = '';
    public string $privacyPolicy = '';

    protected static function getValidationRules(): array
    {
        return [
            'termsCondition' => 'nullable|string',
            'privacyPolicy' => 'nullable|string',
        ];
    }
}
