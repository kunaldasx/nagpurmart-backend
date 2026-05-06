<?php

namespace App\Http\Controllers\Api;

use App\Enums\SettingTypeEnum;
use App\Http\Controllers\Controller;
use App\Services\SettingService;
use App\Types\Api\ApiResponseType;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

#[Group('Settings')]
class SettingApiController extends Controller
{
    use AuthorizesRequests;

    protected SettingService $settingService;

    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    public function index(): JsonResponse
    {
        $transformedSettings = $this->settingService->getAllSettings();

        return ApiResponseType::sendJsonResponse(
            success: true,
            message: 'labels.settings_fetched_successfully',
            data: $transformedSettings
        );
    }

    public function show($variable): JsonResponse
    {
        $setting_variable = SettingTypeEnum::values();
        if (!in_array($variable, $setting_variable)) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.invalid_type',
                data: []
            );
        }

        $transformedSetting = $this->settingService->getSettingByVariable($variable);

        if (!$transformedSetting) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.setting_not_found',
                data: []
            );
        }

        return ApiResponseType::sendJsonResponse(
            success: true,
            message: 'labels.setting_fetched_successfully',
            data: $transformedSetting
        );
    }

    public function settingVariables(): JsonResponse
    {
        return ApiResponseType::sendJsonResponse(
            success: true,
            message: 'labels.setting_variables_fetched_successfully',
            data: SettingTypeEnum::values()
        );
    }

    public function firebaseConfig(): JsonResponse
    {
        $firebase = $this->settingService->getSettingByVariable(SettingTypeEnum::AUTHENTICATION());
        $notification = $this->settingService->getSettingByVariable(SettingTypeEnum::NOTIFICATION());

        return ApiResponseType::sendJsonResponse(
            success: true,
            message: 'labels.firebase_config_fetched_successfully',
            data: [
                'apiKey' => $firebase->value['fireBaseApiKey'] ?? "",
                'authDomain' => $firebase->value['fireBaseAuthDomain'] ?? "",
                'projectId' => $firebase->value['fireBaseProjectId'] ?? "",
                'storageBucket' => $firebase->value['fireBaseStorageBucket'] ?? "",
                'messagingSenderId' => $firebase->value['fireBaseMessagingSenderId'] ?? "",
                'appId' => $firebase->value['fireBaseAppId'] ?? "",
                'vapidKey' => $notification->value['vapIdKey'] ?? ""
            ]
        );
    }
    public function authConfig(): JsonResponse
    {
        $authSetting = $this->settingService->getSettingByVariable(SettingTypeEnum::AUTHENTICATION());

        if (!$authSetting) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.setting_not_found',
                data: []
            );
        }

        $config = $authSetting->value ?? [];

        return ApiResponseType::sendJsonResponse(
            success: true,
            message: 'labels.auth_config_fetched_successfully',
            data: [
                'customSms' => (bool)($config['customSms'] ?? false),
                'firebase' => (bool)($config['firebase'] ?? false),
                'googleLogin' => (bool)($config['googleLogin'] ?? false),
                'appleLogin' => (bool)($config['appleLogin'] ?? false),
                'smsGateway' => $config['smsGateway'] ?? '',
            ]
        );
    }
}
