<?php

namespace App\Models;

use App\Enums\DefaultSystemRolesEnum;
use App\Enums\SettingTypeEnum;
use App\Enums\SystemVendorTypeEnum;
use App\Services\SettingService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Setting extends Model
{
    protected $primaryKey = 'variable';  // Define the primary key

    public $incrementing = false;  // Tell Laravel it's NOT an auto-incrementing key
    protected $keyType = 'string';
    protected $fillable = ['variable', 'value'];

    public function getValueAttribute($value)
    {
        return json_decode($value, true);
    }

    public function getAllowedCountriesAttribute()
    {
        if (!empty($this->value['allowedCountries'])) {
            return Country::whereIn('name', $this->value['allowedCountries'])->pluck('iso2')->toArray();
        }
    }

    public static function systemType(): string
    {
        $settingService = app(SettingService::class);
        $resource = $settingService->getSettingByVariable('system');
        $systemSettings = $resource ? ($resource->toArray(request())['value'] ?? []) : [];
        return !empty($systemSettings['systemVendorType']) ? $systemSettings['systemVendorType'] : SystemVendorTypeEnum::MULTIPLE();
    }

    public static function isSystemVendorTypeMultiple(): bool
    {
        $systemVendorType = self::systemType();
        return $systemVendorType === SystemVendorTypeEnum::MULTIPLE();
    }

    public static function isSystemVendorTypeSingle(): bool
    {
        $systemVendorType = self::systemType();
        return $systemVendorType === SystemVendorTypeEnum::SINGLE();
    }

    public static function isSubscriptionEnabled(): bool
    {
        try {
            $service = app(SettingService::class);
            $resource = $service->getSettingByVariable(SettingTypeEnum::SUBSCRIPTION());
            $settings = $resource ? ($resource->toArray(request())['value'] ?? []) : [];
            return ($settings['enableSubscription'] ?? null) === true;
        } catch (\Throwable) {
            // On any error, fail open (do not enforce limits)
            return false;
        }
    }

    public static function adminSeller()
    {
        try {
            $userAuth = Auth::user();
            $seller = SellerUser::where('user_id', $userAuth->id)->with('seller');
            if (Setting::isSystemVendorTypeSingle()
                && $userAuth
                && $userAuth->hasRole(DefaultSystemRolesEnum::SUPER_ADMIN())
                && $seller->exists()) {
                return $seller->first()->seller;
            }
            return null;
        } catch (\Exception) {
            return null;
        }
    }

    public static function canImpersonate(): bool
    {
        $userAuth = Auth::user();
        return Setting::isSystemVendorTypeSingle()
            && $userAuth
            && $userAuth->hasRole(DefaultSystemRolesEnum::SUPER_ADMIN())
            && SellerUser::where('user_id', $userAuth->id)->exists();
    }
}
