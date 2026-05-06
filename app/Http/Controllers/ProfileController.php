<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\ChangePasswordRequest;
use App\Http\Requests\User\UpdateProfileRequest;
use App\Services\ProfileService;
use App\Services\SettingService;
use App\Traits\PanelAware;
use App\Types\Api\ApiResponseType;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Enums\SettingTypeEnum;

class ProfileController extends Controller
{
    use PanelAware;
    protected ProfileService $profileService;
    protected SettingService $settingService;

    public function __construct(ProfileService $profileService, SettingService $settingService)
    {
        $this->profileService = $profileService;
        $this->settingService = $settingService;
    }

    private function isDemoModeEnabled(): bool
    {
        $resource = $this->settingService->getSettingByVariable(SettingTypeEnum::SYSTEM());
        $settings = $resource ? ($resource->toArray(request())['value'] ?? []) : [];
        return (bool)($settings['demoMode'] ?? false);
    }

    /**
     * Show the admin profile page
     *
     * @return View
     */
    public function index(): View
    {
        $user = Auth::user();
        $profileData = $this->profileService->getProfileData($user);

        return view($this->panelView('profile.index'), [
            'user' => $user,
            'profileData' => $profileData,
        ]);
    }

    /**
     * Show the edit profile form
     *
     * @return View
     */
    public function edit(): View
    {
        $user = Auth::user();
        $profileData = $this->profileService->getProfileData($user);

        return view($this->panelView('profile.edit'), [
            'user' => $user,
            'profileData' => $profileData,
        ]);
    }

    /**
     * Update the admin profile
     *
     * @param UpdateProfileRequest $request
     * @return JsonResponse
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        try {
            if ($this->isDemoModeEnabled()) {
                return ApiResponseType::sendJsonResponse(false, __('labels.demo_mode_message_placeholder'), [], 403);
            }
            $user = Auth::user();
            $validated = $request->validated();

            $this->profileService->updateProfile($user, $validated, $request);

            return ApiResponseType::sendJsonResponse(success: true, message: __('labels.profile_updated_successfully'), data: $user);

        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(success: false, message: __('labels.profile_update_failed', ['error' => $e->getMessage()]), data: []);
        }
    }

    /**
     * Change password for the authenticated user (admin/seller)
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        try {
            if ($this->isDemoModeEnabled()) {
                return ApiResponseType::sendJsonResponse(false, __('labels.demo_mode_message_placeholder'), [], 403);
            }
            $user = Auth::user();
            $user->password = Hash::make($request->input('password'));
            $user->save();

            return ApiResponseType::sendJsonResponse(success: true, message: __('labels.password_updated_successfully'), data: []);
        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(success: false, message: __('labels.password_update_failed', ['error' => $e->getMessage()]), data: []);
        }
    }
}
