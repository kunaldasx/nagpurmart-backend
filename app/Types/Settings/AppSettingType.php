<?php

namespace App\Types\Settings;

use App\Interfaces\SettingInterface;
use App\Traits\SettingTrait;

class AppSettingType implements SettingInterface
{
    use SettingTrait;
    public string $customerAppstoreLink = '';
    public string $customerPlaystoreLink = '';
    public string $customerAppScheme = '';
    public string $sellerAppstoreLink = '';
    public string $sellerPlaystoreLink = '';
    public string $sellerAppScheme = '';
    protected static function getValidationRules(): array
    {
        return [
            'customerAppstoreLink' => 'nullable|url',
            'customerPlaystoreLink' => 'nullable|url',
            'customerAppScheme' => ['nullable', 'regex:/^\S+$/'],
            'sellerAppstoreLink' => 'nullable|url',
            'sellerPlaystoreLink' => 'nullable|url',
            'sellerAppScheme' => ['nullable', 'regex:/^\S+$/'],
        ];
    }
}
