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
     * Complete user profile after OTP signup
     * 
     * Allows users who signed up with phone+OTP to complete their profile
     * by providing name, email, and optionally setting a password.
     * This is part of the Rapido-like simplified signup flow.
     */
    public function completeProfile(Request $request): JsonResponse
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

            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'name' => 'nullable|string|max:255',
                'email' => 'nullable|email|unique:users,email,' . $user->id,
                'password' => 'nullable|string|min:6|confirmed',
            ]);

            if ($validator->fails()) {
                return ApiResponseType::sendJsonResponse(
                    false,
                    'Validation failed',
                    ['errors' => $validator->errors()]
                );
            }

            // Update name if provided
            if ($request->has('name') && !empty($request->input('name'))) {
                $user->name = $request->input('name');
            }

            // Update email if provided
            if ($request->has('email') && !empty($request->input('email'))) {
                $user->email = $request->input('email');
            }

            // Set password if provided (for users who didn't set it during signup)
            if ($request->has('password') && !empty($request->input('password'))) {
                $user->password = Hash::make($request->input('password'));
            }

            $user->save();

            return ApiResponseType::sendJsonResponse(
                true,
                'labels.profile_updated_successfully',
                new UserResource($user)
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
