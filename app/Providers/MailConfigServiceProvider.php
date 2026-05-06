<?php

namespace App\Providers;

use App\Enums\SettingTypeEnum;
use App\Models\Setting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class MailConfigServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if (!file_exists(storage_path('installed'))) {
            return;
        }
        if (!Schema::hasTable('settings')) {
            return;
        }

        $emailSettings = Setting::find(SettingTypeEnum::EMAIL())?->value;
        if (!empty($emailSettings)) {
            Config::set('mail.mailers.smtp.host', $emailSettings['smtpHost']);
            Config::set('mail.mailers.smtp.port', $emailSettings['smtpPort']);
            Config::set('mail.mailers.smtp.username', $emailSettings['smtpEmail']);
            Config::set('mail.mailers.smtp.password', $emailSettings['smtpPassword'] ?? null);
            Config::set('mail.mailers.smtp.encryption', $emailSettings['smtpEncryption']);
            Config::set('mail.from.address', $emailSettings['smtpEmail']);
        }

        $systemSettings = Setting::find(SettingTypeEnum::SYSTEM())?->value;
        $fromName = $systemSettings['appName'] ?? config('mail.from.name', config('app.name'));

        Config::set('mail.from.name', $fromName);
    }
}
