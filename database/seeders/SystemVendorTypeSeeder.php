<?php

namespace Database\Seeders;

use App\Enums\SettingTypeEnum;
use App\Enums\SystemVendorTypeEnum;
use App\Models\Setting;
use App\Services\SettingService;
use Illuminate\Database\Seeder;

class SystemVendorTypeSeeder extends Seeder
{
    /**
     * Seed the system vendor type to multiple without overwriting other keys.
     */
    public function run(): void
    {
        // Ensure a system setting record exists
        $setting = Setting::firstOrCreate(
            ['variable' => SettingTypeEnum::SYSTEM()],
            ['value' => json_encode([])]
        );

        // $setting->value is decoded via accessor on the model
        $values = is_array($setting->value) ? $setting->value : [];

        // Only update if it's not already multiple
        $values['systemVendorType'] = SystemVendorTypeEnum::MULTIPLE();

        $setting->update(['value' => json_encode($values)]);

        // Clear cache so new value is reflected immediately where caching is used
        try {
            /** @var SettingService $service */
            $service = app(SettingService::class);
            $service->clearSettingCache(SettingTypeEnum::SYSTEM());
        } catch (\Throwable) {
            // fail silently during seeding
        }
    }
}
