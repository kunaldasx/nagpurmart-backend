<?php

namespace App\Services;

use App\Enums\SettingTypeEnum;
use App\Http\Resources\Setting\DeliveryBoySettingResource;
use App\Http\Resources\Setting\SellerSettingResource;
use App\Http\Resources\Setting\AppSettingResource;
use App\Http\Resources\Setting\AuthenticationSettingResource;
use App\Http\Resources\Setting\HomeGeneralSettingResource;
use App\Http\Resources\Setting\EmailSettingResource;
use App\Http\Resources\Setting\NotificationSettingResource;
use App\Http\Resources\Setting\PaymentSettingResource;
use App\Http\Resources\Setting\StorageSettingResource;
use App\Http\Resources\Setting\SubscriptionSettingResource;
use App\Http\Resources\Setting\SystemSettingResource;
use App\Http\Resources\Setting\WebSettingResource;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use ReflectionClass;
use App\Services\CurrencyService;

class SettingService
{
    /**
     * Get all settings with their appropriate resource transformations
     */
    public function getAllSettings(): Collection
    {
        $settingVariables = SettingTypeEnum::values();
        $transformedSettings = collect();

        foreach ($settingVariables as $variable) {
            $setting = $this->getOrCreateDefaultSetting($variable);
            $transformedSettings->push($this->transformSetting($setting));
        }

        return $transformedSettings;
    }

    /**
     * Get a specific setting by variable with resource transformation
     */
    public function getSettingByVariable(string $variable): ?JsonResource
    {
        if (!in_array($variable, SettingTypeEnum::values())) {
            return null;
        }

        // Retrieve cached values (or compute defaults) and avoid DB hit on every call
        $values = $this->getCachedSettingValues($variable);

        // Build a transient Setting model instance from cached values for resource transformation
        $setting = new Setting();
        $setting->variable = $variable;
        $setting->setAttribute('value', json_encode($values));

        return $this->transformSetting($setting);
    }

    /**
     * Build cache key for a given setting variable
     */
    private function cacheKey(string $variable): string
    {
        return "settings:" . $variable;
    }

    /**
     * Fetch setting values from cache or compute and store defaults if missing.
     */
    private function getCachedSettingValues(string $variable): array
    {
        $key = $this->cacheKey($variable);
        return Cache::rememberForever($key, function () use ($variable) {
            // Try database first
            $existing = Setting::where('variable', $variable)->first();
            if ($existing) {
                // $existing->value is already decoded via accessor
                return is_array($existing->value) ? $existing->value : [];
            }
            // Fallback to default values if not present in DB
            return $this->getDefaultValuesFromType($variable) ?: $this->getDefaultValuesFromResource($variable);
        });
    }

    /**
     * Clear cache for a specific setting variable.
     */
    public function clearSettingCache(string $variable): void
    {
        Cache::forget($this->cacheKey($variable));
    }

    /**
     * Get setting from database or create default if not exists
     */
    public function getOrCreateDefaultSetting(string $variable): Setting
    {
        $setting = Setting::where('variable', $variable)->first();

        if (!$setting) {
            $setting = $this->createDefaultSetting($variable);
        }
        return $setting;
    }

    /**
     * Transform setting using appropriate resource based on setting variable
     */
    public function transformSetting(Setting $setting): JsonResource
    {
        return match ($setting->variable) {
            'system' => new SystemSettingResource($setting),
            'web' => new WebSettingResource($setting),
            'app' => new AppSettingResource($setting),
            'email' => new EmailSettingResource($setting),
            'payment' => new PaymentSettingResource($setting),
            'storage' => new StorageSettingResource($setting),
            'notification' => new NotificationSettingResource($setting),
            'authentication' => new AuthenticationSettingResource($setting),
            'delivery_boy' => new DeliveryBoySettingResource($setting),
            'seller' => new SellerSettingResource($setting),
            'home_general_settings' => new HomeGeneralSettingResource($setting),
            'subscription' => new SubscriptionSettingResource($setting),
            default => throw new \InvalidArgumentException("Unsupported setting type: {$setting->variable}")
        };
    }

    /**
     * Create a default setting instance (not saved to DB)
     */
    private function createDefaultSetting(string $variable): Setting
    {
        $defaultValues = $this->getDefaultValuesFromType($variable);

        // If reflection fails, get defaults from resource
        if (empty($defaultValues)) {
            $defaultValues = $this->getDefaultValuesFromResource($variable);
        }

        $setting = new Setting();
        $setting->variable = $variable;
        $setting->setAttribute('value', json_encode($defaultValues));

        return $setting;
    }

    /**
     * Get default values from setting type class using reflection
     */
    private function getDefaultValuesFromType(string $variable): array
    {
        $className = "App\\Types\\Settings\\" . ucfirst($variable) . "SettingType";

        if (!class_exists($className)) {
            return [];
        }

        try {
            $reflection = new ReflectionClass($className);
            $instance = $reflection->newInstance();
            $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);

            $defaultValues = [];
            foreach ($properties as $property) {
                $defaultValues[$property->getName()] = $property->getValue($instance);
            }

            return $defaultValues;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Extract default values from resource by creating a temporary setting with empty value
     * and then parsing the resource's toArray method to get the default values
     */
    private function getDefaultValuesFromResource(string $variable): array
    {
        try {
            // Create a temporary setting with empty value
            $tempSetting = new Setting();
            $tempSetting->variable = $variable;
            $tempSetting->setAttribute('value', json_encode([]));

            // Get the appropriate resource
            $resource = $this->createResourceInstance($variable, $tempSetting);

            if (!$resource) {
                return [];
            }

            // Get the resource array and extract the 'value' part
            $request = new Request();
            $resourceArray = $resource->toArray($request);

            if (isset($resourceArray['value']) && is_array($resourceArray['value'])) {
                return $resourceArray['value'];
            }

            return [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Create a resource instance based on variable
     */
    private function createResourceInstance(string $variable, Setting $setting): ?JsonResource
    {
        return match ($variable) {
            'system' => new SystemSettingResource($setting),
            'web' => new WebSettingResource($setting),
            'app' => new AppSettingResource($setting),
            'email' => new EmailSettingResource($setting),
            'payment' => new PaymentSettingResource($setting),
            'storage' => new StorageSettingResource($setting),
            'notification' => new NotificationSettingResource($setting),
            'authentication' => new AuthenticationSettingResource($setting),
            'delivery_boy' => new DeliveryBoySettingResource($setting),
            'seller' => new SellerSettingResource($setting),
            'subscription' => new SubscriptionSettingResource($setting),
            'home_general_settings' => new HomeGeneralSettingResource($setting),
            default => null
        };
    }

    /**
     * Save or update a setting
     */
    public function saveSetting(string $variable, array $values): Setting
    {
        $setting = Setting::updateOrCreate(
            ['variable' => $variable],
            ['value' => json_encode($values)]
        );

        // Update cache immediately so subsequent reads are fresh
        Cache::forever($this->cacheKey($variable), $values);

        // Clear currency cache if system settings are updated
        if ($variable === 'system' && app()->has(CurrencyService::class)) {
            app(CurrencyService::class)->clearCache();
        }

        return $setting;
    }

    /**
     * Check if a setting variable is valid
     */
    public function isValidSettingVariable(string $variable): bool
    {
        return in_array($variable, SettingTypeEnum::values());
    }

    /**
     * Get raw setting value without resource transformation
     */
    public function getRawSetting(string $variable): ?Setting
    {
        return Setting::where('variable', $variable)->first();
    }

    /**
     * Get all settings without transformation
     */
    public function getAllRawSettings(): Collection
    {
        return Setting::all();
    }
}
