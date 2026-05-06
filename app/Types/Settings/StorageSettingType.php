<?php

namespace App\Types\Settings;

use App\Interfaces\SettingInterface;
use App\Traits\SettingTrait;

class StorageSettingType implements SettingInterface
{
    use SettingTrait;

    public string $awsAccessKeyId = '';
    public string $awsSecretAccessKey = '';
    public string $awsRegion = '';
    public string $awsBucket = '';
    public string $awsAssetUrl = '';

    protected static function getValidationRules(): array
    {
        return [
            'awsAccessKeyId' => 'required',
            'awsSecretAccessKey' => 'required',
            'awsRegion' => 'required',
            'awsBucket' => 'required',
            'awsAssetUrl' => 'required|url',
        ];
    }
}
