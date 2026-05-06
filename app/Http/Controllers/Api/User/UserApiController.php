<?php

namespace App\Http\Controllers\Api\User;

use App\Enums\SettingTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\ChangePasswordRequest;
use App\Http\Requests\User\UpdateProfileRequest;
use App\Http\Resources\User\UserResource;
use App\Services\ProfileService;
use App\Services\SettingService;
use App\Types\Api\ApiResponseType;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Enums\SellerPermissionEnum;
use Illuminate\Support\Facades\Log;

#[Group('Users')]
class UserApiController extends Controller
{
    protected ProfileService $profileService;
    protected SettingService $settingService;

    public function __construct(ProfileService $profileService,SettingService $settingService)
    {
        $this->profileService = $profileService;
        $this->settingService = $settingService;
    }
    /**
     * Update user profile
     *
     * @param UpdateProfileRequest $request
     * @return JsonResponse
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return ApiResponseType::sendJsonResponse(
                    false,
                    'labels.user_not_authenticated',
                    []
                );
            }

            $validated = $request->validated();
            $updatedUser = $this->profileService->updateProfile($user, $validated, $request);

            return ApiResponseType::sendJsonResponse(
                true,
                'labels.profile_updated_successfully',
                new UserResource($updatedUser)
            );

        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(
                false,
                'labels.something_went_wrong',
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Get user profile
     *
     * @return JsonResponse
     */
    public function getProfile(): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return ApiResponseType::sendJsonResponse(
                    false,
                    'labels.user_not_authenticated',
                    []
                );
            }

            // Determine assigned permissions for seller users (same logic as login)
            $assignedPermissions = [];
            try {
                if (!empty($user->access_panel?->value) && $user->access_panel->value === 'seller') {
                    $seller = $user->seller();
                    if ($seller) {
                        if ((int) $seller->user_id === (int) $user->id) {
                            // Main seller gets all permissions
                            $assignedPermissions = SellerPermissionEnum::values();
                        } else {
                            // Team/system user gets assigned permissions within seller team context
                            if (function_exists('setPermissionsTeamId')) {
                                setPermissionsTeamId($seller->id);
                            }
                            $assignedPermissions = $user->getAllPermissions()->pluck('name')->toArray();
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Failed determining seller permissions on getProfile: ' . $e->getMessage());
            }

            // Return response with assigned_permissions as a top-level key (to match login response shape)
            return response()->json([
                'success' => true,
                'message' => __('labels.profile_retrieved_successfully'),
                'data' => new UserResource($user),
                'assigned_permissions' => $assignedPermissions,
            ]);

        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(
                false,
                'labels.something_went_wrong',
                ['error' => $e->getMessage()]
            );
        }
    }


    public function deleteAccount(Request $request): JsonResponse
    {
        try {
            if ($this->isDemoModeEnabled()){
                return ApiResponseType::sendJsonResponse(false, 'labels.delete_account_not_allowed_in_demo_mode', []);
            }
            $user = $request->user();
            $user->delete();
            return ApiResponseType::sendJsonResponse(true, __('labels.account_deleted_successfully'), []);
        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(false, __('labels.account_deletion_failed', ['error' => $e->getMessage()]), []);
        }
    }

    /**
     * Change password for the authenticated user via API
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        try {
            if ($this->isDemoModeEnabled()){
                return ApiResponseType::sendJsonResponse(false, 'labels.change_password_not_allowed_in_demo_mode', []);
            }
            $user = Auth::user();
            if (!$user) {
                return ApiResponseType::sendJsonResponse(false, 'labels.user_not_authenticated', []);
            }

            // Validation is already handled by ChangePasswordRequest (includes current_password rule)
            $user->password = Hash::make($request->input('password'));
            $user->save();

            return ApiResponseType::sendJsonResponse(true, __('labels.password_updated_successfully'), []);
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(false, __('labels.password_update_failed', ['error' => $e->getMessage()]), []);
        }
    }

    protected function isDemoModeEnabled(): bool
    {
        try {
            $resource = $this->settingService->getSettingByVariable(SettingTypeEnum::SYSTEM());
            $settings = $resource ? ($resource->toArray(request())['value'] ?? []) : [];
            return (bool)($settings['demoMode'] ?? false);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
