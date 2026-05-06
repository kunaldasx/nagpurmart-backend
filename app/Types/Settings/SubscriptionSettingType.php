<?php

namespace App\Types\Settings;

use App\Interfaces\SettingInterface;
use App\Traits\SettingTrait;

class SubscriptionSettingType implements SettingInterface
{
    use SettingTrait;
    public ?bool $enableSubscription = null;
    public string $subscriptionHeading = '';
    public string $subscriptionDescription = '';
    protected static function getValidationRules(): array
    {
        return [
            'enableSubscription' => 'nullable|boolean',
            'subscriptionHeading' => 'required',
            'subscriptionDescription' => 'required',
        ];
    }
}
