<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SettingTypeEnum;
use App\Enums\SystemVendorTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\SettingService;
use App\Types\Api\ApiResponseType;
use App\Types\Settings\AppSettingType;
use App\Types\Settings\AuthenticationSettingType;
use App\Types\Settings\HomeGeneralSettingType;
use App\Types\Settings\DeliveryBoySettingType;
use App\Types\Settings\SellerSettingType;
use App\Types\Settings\EmailSettingType;
use App\Types\Settings\NotificationSettingType;
use App\Types\Settings\PaymentSettingType;
use App\Types\Settings\StorageSettingType;
use App\Types\Settings\SubscriptionSettingType;
use App\Types\Settings\SystemSettingType;
use App\Types\Settings\WebSettingType;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SettingController extends Controller
{
    use AuthorizesRequests;

    protected SettingService $settingService;

    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }


    public function index(): View
    {
        try {
            $this->authorize('viewAny', Setting::class);
        } catch (AuthorizationException $e) {
            abort(403, __('labels.unauthorized_access'));
        }
        return view('admin.settings.all_settings');
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'type' => ['required', new Enum(SettingTypeEnum::class)],
            ]);

            $type = $request->input('type');

            // Map setting type to the corresponding class
            $method = match ($type) {
                SettingTypeEnum::SYSTEM() => SystemSettingType::class,
                SettingTypeEnum::STORAGE() => StorageSettingType::class,
                SettingTypeEnum::EMAIL() => EmailSettingType::class,
                SettingTypeEnum::PAYMENT() => PaymentSettingType::class,
                SettingTypeEnum::AUTHENTICATION() => AuthenticationSettingType::class,
                SettingTypeEnum::NOTIFICATION() => NotificationSettingType::class,
                SettingTypeEnum::WEB() => WebSettingType::class,
                SettingTypeEnum::APP() => AppSettingType::class,
                SettingTypeEnum::DELIVERY_BOY() => DeliveryBoySettingType::class,
                SettingTypeEnum::SELLER() => SellerSettingType::class,
                SettingTypeEnum::HOME_GENERAL_SETTINGS() => HomeGeneralSettingType::class,
                SettingTypeEnum::SUBSCRIPTION() => SubscriptionSettingType::class,
                default => null,
            };

            if (!$method) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: __('labels.invalid_type'),
                    data: []
                );
            }

            // Initialize settings object from request data
            $settings = $method::fromArray($request->all());

            // Handle media uploads
            $this->handleMediaUploads($request, $settings);

            // Prepare values for storage as array (service will handle encoding + cache)
            $values = json_decode($settings->toJson(), true);

            switch ($type) {
                case 'authentication':
                    // Custom validation for mutually exclusive settings
                    $customErrors = AuthenticationSettingType::validateMutuallyExclusiveSettings($request->all());
                    if (!empty($customErrors)) {
                        return ApiResponseType::sendJsonResponse(
                            success: false,
                            message: __('labels.validation_failed') . ': ' . collect($customErrors)->flatten()->first(),
                            data: $customErrors
                        );
                    }
                    // Automatically set SMS gateway based on enabled services
                    $smsGateway = AuthenticationSettingType::getSmsGateway($request->all());
                    $request->merge(['smsGateway' => $smsGateway]);
                    break;
                case 'system':
                    // Prevent overwriting existing systemVendorType
                    $currentSystem = Setting::where('variable', 'system')->first();
                    $currentValue = $currentSystem?->value ?? [];
                    if (!empty($currentValue['systemVendorType'])) {
                        $values['systemVendorType'] = $currentValue['systemVendorType'];
                    } else {
                        $values['systemVendorType'] = 'multiple';
                    }
                    // Allow override only in dev mode
                    if (file_exists(storage_path('dev')) && $request->has('systemVendorType')) {
                        $values['systemVendorType'] = $request->input('systemVendorType');
                    }
                    break;
                case 'subscription':
                    // Enforce one-way enabling rule
                    $currentSubscription = Setting::where('variable', 'subscription')->first();
                    $currentEnable = $currentSubscription->value['enableSubscription'] ?? null;
                    if ($currentEnable === true) {
                        // Once enabled, always enabled
                        $values['enableSubscription'] = true;
                    }
                    break;
            }

            // Authorize the module-wise update action
            try {
                $this->authorize('updateSetting', [Setting::class, $type]);
            } catch (AuthorizationException $e) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: __('labels.unauthorized_access'),
                    data: []
                );
            }

            // Update or create setting using service (handles cache refresh)
            $existing = Setting::find($type);
            $saved = $this->settingService->saveSetting($type, $values ?? []);
            $saved['redirect_url'] = $request->input('redirect_url') ?? '';

            if ($existing) {
                return ApiResponseType::sendJsonResponse(
                    success: true,
                    message: __('labels.setting_updated_successfully', ['type' => ucfirst(Str::replace('_', ' ', $type))]),
                    data: $saved
                );
            }

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: __('labels.setting_created_successfully', ['type' => $type]),
                data: $saved
            );
        } catch (ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first();
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.validation_failed') . ': ' . $firstError,
                data: $e->errors()
            );
        }
    }

    /**
     * Handle media file uploads and assign paths to the settings object.
     *
     * @param Request $request
     * @param mixed $settings
     * @return void
     */
    private function handleMediaUploads(Request $request, $settings): void
    {
        $mediaFields = [
            'logo' => [
                'name' => fn($file) => 'logo-' . time() . '.' . $file->getClientOriginalExtension(),
                'path' => 'settings'
            ],

            'favicon' => [
                'name' => fn($file) => 'favicon-' . time() . '.' . $file->getClientOriginalExtension(),
                'path' => 'settings'
            ],

            'siteHeaderDarkLogo' => [
                'name' => fn($file) => 'site-header-dark-logo-' . time() . '.' . $file->getClientOriginalExtension(),
                'path' => 'settings'
            ],

            'siteHeaderLogo' => [
                'name' => fn($file) => 'site-header-logo-' . time() . '.' . $file->getClientOriginalExtension(),
                'path' => 'settings'
            ],

            'siteFooterLogo' => [
                'name' => fn($file) => 'site-footer-logo-' . time() . '.' . $file->getClientOriginalExtension(),
                'path' => 'settings'
            ],

            'siteFavicon' => [
                'name' => fn($file) => 'site-favicon-' . time() . '.' . $file->getClientOriginalExtension(),
                'path' => 'settings'
            ],

            'backgroundImage' => [
                'name' => fn($file) => uniqid() . '-' . $file->getClientOriginalName(),
                'path' => 'settings'
            ],

            'icon' => [
                'name' => fn($file) => uniqid() . '-' . $file->getClientOriginalName(),
                'path' => 'settings'
            ],

            'activeIcon' => [
                'name' => fn($file) => uniqid() . '-' . $file->getClientOriginalName(),
                'path' => 'settings'
            ],

            'serviceAccountFile' => ['name' => 'service-account-file.json', 'path' => 'settings', 'disk' => 'local'],
            'pwaLogo192x192' => [
                'name' => fn($file) => 'pwa-logo-192x192-' . time() . '.png',
                'path' => 'pwa_logos'
            ],

            'pwaLogo512x512' => [
                'name' => fn($file) => 'pwa-logo-512x512-' . time() . '.png',
                'path' => 'pwa_logos'
            ],

            'pwaLogo144x144' => [
                'name' => fn($file) => 'pwa-logo-144x144-' . time() . '.png',
                'path' => 'pwa_logos'
            ],

            'adminSignature' => [
                'name' => fn($file) => 'admin-signature-' . time() . '.' . $file->getClientOriginalExtension(),
                'path' => 'settings'
            ],
        ];

        foreach ($mediaFields as $field => $config) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $fileName = is_callable($config['name']) ? $config['name']($file) : $config['name'];
                $disk = $config['disk'] ?? 'public';
                $path = $file->storeAs($config['path'], $fileName, $disk);
                $settings->$field = $path;
            }
        }
    }

    public function show($variable): View
    {
        try {

            $setting_variable = SettingTypeEnum::values();
            if (!in_array($variable, $setting_variable)) {
                abort(404, __('labels.invalid_type'));
            }

            $transformedSetting = $this->settingService->getSettingByVariable($variable);

            if (!$transformedSetting) {
                abort(404, __('labels.setting_not_found'));
            }
            // Authorize module-wise view access
            $this->authorize('viewSetting', [Setting::class, $variable]);
            $settings = $transformedSetting->toArray(request())['value'] ?? [];

            // Ensure smsGateway field exists for authentication settings
            if ($variable === 'authentication') {
                // Calculate smsGateway based on current settings if not present
                if (!isset($settings['smsGateway'])) {
                    $customSmsEnabled = isset($settings['customSms']) && $settings['customSms'];
                    $firebaseEnabled = isset($settings['firebase']) && $settings['firebase'];

                    if ($customSmsEnabled) {
                        $settings['smsGateway'] = 'custom';
                    } elseif ($firebaseEnabled) {
                        $settings['smsGateway'] = 'firebase';
                    } else {
                        $settings['smsGateway'] = '';
                    }
                }
            }

            $setting = Setting::find('authentication');
            $googleApiKey = $setting->value['googleApiKey'] ?? null;
            return view('admin.settings.' . $variable, [
                'settings' => $settings,
                'googleApiKey' => $googleApiKey
            ]);
        } catch (AuthorizationException $e) {
            abort(403, __('labels.unauthorized_access'));
        } catch (\Exception $e) {
            abort(500, __('labels.something_went_wrong'));
        }
    }

}
