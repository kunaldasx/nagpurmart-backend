<?php

namespace App\Traits;

use App\Enums\Seller\SellerVerificationStatusEnum;
use App\Enums\SystemVendorTypeEnum;
use App\Events\Auth\UserLoggedIn;
use App\Events\Auth\UserRegistered;
use App\Http\Resources\User\UserResource;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserFcmToken;
use App\Types\Api\ApiResponseType;
use App\Services\SettingService;
use App\Enums\SettingTypeEnum;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth as FacadesAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use App\Enums\SellerPermissionEnum;

trait AuthTrait
{
    protected string $systemType;
    /**
     * Store or update FCM token for a user if provided in the request
     */
    protected function storeFcmToken(Request $request, User $user): void
    {
        try {
            $fcmToken = $request->input('fcm_token');
            $deviceType = $request->input('device_type');

            if (!empty($fcmToken) && !empty($deviceType)) {
                UserFcmToken::updateOrCreate(
                    [
                        'fcm_token' => $fcmToken,
                    ],
                    [
                        'user_id' => $user->id,
                        'device_type' => $deviceType,
                    ]
                );
            }
        } catch (\Throwable $e) {
            Log::error('Error updating or creating FCM token: ' . $e->getMessage());
        }
    }

    /**
     * Build credentials and return identifier meta
     *
     * @return array{credentials: array, field: string, value: mixed}
     */
    protected function buildCredentials(Request $request, array $validated): array
    {
        $identifierField = $request->filled('email') ? 'email' : 'mobile';
        $identifierValue = $request->input($identifierField);

        return [
            'credentials' => [
                $identifierField => $identifierValue,
                'password' => $validated['password'],
            ],
            'field' => $identifierField,
            'value' => $identifierValue,
        ];
    }

    /**
     * Perform optional role-based gate checks; returns a response if blocked, or null if allowed
     */
    protected function checkRoleAccess(?string $role, string $identifierField, $identifierValue): ?JsonResponse
    {
        if (!$role) {
            return null;
        }

        $userForRoleCheck = User::where($identifierField, $identifierValue)->first();
        if (!$userForRoleCheck) {
            return response()->json([
                'success' => false,
                'message' => __('labels.invalid_credentials'),
                'data' => []
            ]);
        }

        if ($role === 'seller' && $this->systemType == SystemVendorTypeEnum::MULTIPLE()) {
            if (!empty($userForRoleCheck->access_panel?->value) && $userForRoleCheck->access_panel->value !== 'seller') {
                return response()->json([
                    'success' => false,
                    'message' => __('labels.invalid_credentials'),
                    'data' => []
                ]);
            }

            $seller = $userForRoleCheck->seller();
            if (!$seller) {
                return response()->json([
                    'success' => false,
                    'message' => __('labels.not_a_seller') ?? 'Not a seller account.',
                    'data' => []
                ], 403);
            }

            if ($seller->verification_status !== SellerVerificationStatusEnum::Approved()) {
                return response()->json([
                    'success' => false,
                    'message' => __('labels.account_not_verified') ?? 'Your seller account is not approved yet.',
                    'data' => [
                        'verification_status' => $seller->verification_status,
                    ]
                ], 403);
            }
        }

        if ($role === 'admin' && !empty($userForRoleCheck->access_panel?->value) && $userForRoleCheck->access_panel->value !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => __('labels.invalid_credentials'),
                'data' => []
            ]);
        }

        return null;
    }

    /** Attempt to authenticate with given credentials */
    protected function attemptAuthentication(array $credentials): bool
    {
        return FacadesAuth::attempt($credentials);
    }

    /** Finalize successful login */
    protected function finalizeLogin(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->storeFcmToken($request, $user);
        $token = $user->createToken($user->email ?? ($user->mobile ?? 'api-token'))->plainTextToken;
        event(new UserLoggedIn($user));

        // Determine assigned permissions for seller logins
        $assignedPermissions = [];
        try {
            if (!empty($user->access_panel?->value) && $user->access_panel->value === 'seller') {
                $seller = $user->seller();
                if ($seller) {
                    // If the logged-in user is the main seller (owner of the seller record), grant all permissions
                    if ((int) $seller->user_id === (int) $user->id) {
                        $assignedPermissions = SellerPermissionEnum::values();
                    } else {
                        // For system users under a seller, fetch permissions assigned within the seller team context
                        if (function_exists('setPermissionsTeamId')) {
                            setPermissionsTeamId($seller->id);
                        }
                        $assignedPermissions = $user->getAllPermissions()->pluck('name')->toArray();
                    }
                }
            }
        } catch (\Throwable $e) {
            // Fail silently; permissions are best-effort and should not break login
            Log::warning('Failed determining seller permissions on login: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => __('labels.login_successful'),
            'access_token' => $token,
            'token_type' => 'Bearer',
            'data' => new UserResource($user),
            // Pass assigned permissions (array of names). For main seller, this will be the full SellerPermissionEnum values
            'assigned_permissions' => $assignedPermissions,
        ]);
    }

    /**
     * Login
     */
    public function login(Request $request): JsonResponse
    {
        try {
            // 1) Validate input
            $validated = $request->validate([
                'email' => 'required_without:mobile|email',
                'mobile' => 'required_without:email|numeric',
                'password' => 'required',
            ]);

            $this->systemType = Setting::systemType();

            // 2) Build credentials and identifier
            $meta = $this->buildCredentials($request, $validated);
            $credentials = $meta['credentials'];
            $identifierField = $meta['field'];
            $identifierValue = $meta['value'];

            // 3) Optional role-based access check (admin/seller)
            $role = property_exists($this, 'role') ? $this->role : null;
            if ($response = $this->checkRoleAccess($role, $identifierField, $identifierValue)) {
                return $response;
            }

            // 4) Attempt authentication
            if (!$this->attemptAuthentication($credentials)) {
                return response()->json([
                    'success' => false,
                    'message' => __('labels.invalid_credentials'),
                    'data' => []
                ]);
            }

            // 5) Finalize login response
            return $this->finalizeLogin($request);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('labels.validation_error') . ":- " . $e->getMessage(),
                'data' => []
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('labels.login_failed', ['error' => $e->getMessage()]),
                'data' => []
            ], 500);
        }
    }

    public function register(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|unique:users',
                'mobile' => 'required|unique:users|numeric',
                'password' => 'required|string|min:6|confirmed',
                'country' => 'nullable|string|max:255',
                'iso_2' => 'nullable|string|max:2'
            ]);
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'mobile' => $validated['mobile'],
                'country' => $validated['country'] ?? null,
                'iso_2' => $validated['iso_2'] ?? null,
                'password' => Hash::make($validated['password'])
            ]);
            // Grant welcome wallet balance if enabled in system settings
            try {
                $settingService = app(SettingService::class);
                $systemSettingsResource = $settingService->getSettingByVariable(SettingTypeEnum::SYSTEM());
                $systemSettings = $systemSettingsResource?->toArray(request())['value'] ?? [];
//                $enableWallet = (bool)($systemSettings['enableWallet'] ?? false);
                $welcomeAmount = (float)($systemSettings['welcomeWalletBalanceAmount'] ?? 0);

                if ($welcomeAmount > 0) {
                    $walletService = app(WalletService::class);
                    $walletService->addBalance($user->id, [
                        'amount' => $welcomeAmount,
                        'payment_method' => 'system',
                        'description' => __('labels.welcome_wallet_bonus') ?? 'Welcome bonus added to wallet',
                    ]);
                }
            } catch (\Throwable $th) {
                // Do not block registration if wallet credit fails; log and continue
                Log::error('Welcome wallet credit failed for user ' . $user->id . ': ' . $th->getMessage());
            }
            $this->storeFcmToken($request, $user);
            event(new UserRegistered($user));
            return response()->json([
                'success' => true,
                'message' => __('labels.registration_successful'),
                'access_token' => $user->createToken($validated['email'])->plainTextToken,
                'token_type' => 'Bearer',
                'data' => [
                    'user' => $user,
                ]
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('labels.validation_error') . ":- " . $e->getMessage(),
                'data' => []
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('labels.registration_failed', ['error' => $e->getMessage()]),
                'data' => []
            ], 500);
        }
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        try {
            $request->validate(['email' => 'required|email']);

            $status = Password::sendResetLink($request->only('email'));
            if ($status === Password::RESET_LINK_SENT) {
                return response()->json([
                    'success' => true,
                    'message' => __($status),
                    'data' => []
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => __($status),
                'data' => []
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('labels.password_reset_failed', ['error' => $e->getMessage()]),
                'data' => []
            ], 500);
        }
    }

    /**
     * Logout user
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $request->user()->currentAccessToken()->delete();
            return ApiResponseType::sendJsonResponse(
                success: true,
                message: 'labels.logout_successful',
                data: []
            );
        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.logout_failed',
            );
        }
    }
}
