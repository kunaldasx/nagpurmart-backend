<?php

namespace App\Http\Controllers\Api\User;

use App\Enums\SettingTypeEnum;
use App\Events\Auth\UserLoggedIn;
use App\Events\Auth\UserRegistered;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\VerifyUserRequest;
use App\Http\Resources\User\UserResource;
use App\Models\User;
use App\Services\SettingService;
use App\Traits\AuthTrait;
use App\Types\Api\ApiResponseType;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;
use Kreait\Firebase\Factory;
use App\Services\WalletService;
use Illuminate\Support\Str;

#[Group('Auth')]
class AuthApiController extends Controller
{
    use AuthTrait;

    protected SettingService $settingService;

    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    /**
     * Verify if user exists by email or mobile
     *
     * @param VerifyUserRequest $request
     * @return JsonResponse
     */
    public function verifyUser(VerifyUserRequest $request): JsonResponse
    {
        try {
            $type = $request->input('type');
            $value = $request->input('value');

            $user = null;

            if ($type === 'email') {
                $user = User::where('email', $value)->first();
            } elseif ($type === 'mobile') {
                $user = User::where('mobile', $value)->first();
            }

            $exists = !is_null($user);

            $responseData = [
                'exists' => $exists,
                'type' => $type,
                'value' => $value
            ];

            if ($exists) {
                return ApiResponseType::sendJsonResponse(
                    true,
                    'labels.user_found',
                    $responseData
                );
            } else {
                return ApiResponseType::sendJsonResponse(
                    false,
                    'labels.user_not_found',
                    $responseData
                );
            }

        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(
                false,
                'labels.something_went_wrong',
                ['error' => $e->getMessage()]
            );
        }
    }

    private function getFirebaseAuth(): array
    {
        try {
            $authSetting = $this->settingService->getSettingByVariable(SettingTypeEnum::AUTHENTICATION());
            if (empty($authSetting)) {
                return [
                    'success' => false,
                    'message' => 'labels.setting_not_found',
                    'data' => []
                ];
            }
            $serviceAccount = storage_path('app/private/settings/service-account-file.json');

            $factory = (new Factory)->withServiceAccount($serviceAccount);
            return [
                'success' => true,
                'message' => 'labels.token_generated',
                'data' => $factory->createAuth()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'labels.something_went_wrong',
                'data' => ['error' => $e->getMessage()]
            ];
        }
    }

    /**
     * Handle Firebase authentication callback
     */
    public function googleCallback(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'idToken' => 'required|string',
            ]);
            // Check if Google login is enabled in settings
            $authSetting = $this->settingService->getSettingByVariable(SettingTypeEnum::AUTHENTICATION());
            $authConfig = $authSetting?->value ?? [];
            if (empty($authConfig['googleLogin'])) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: 'labels.google_login_not_enabled',
                    data: []
                );
            }
            $auth = $this->getFirebaseAuth();
            if ($auth['success'] === false) {
                return ApiResponseType::sendJsonResponse(
                    $auth['success'],
                    $auth['message'],
                    $auth['data']
                );
            }
            $auth = $auth['data'];
            // Verify the Firebase ID token
            $verifiedIdToken = $auth->verifyIdToken($request->idToken);
            $uid = $verifiedIdToken->claims()->get('sub');

            // Get user info from Firebase
            $firebaseUser = $auth->getUser($uid);
            $user = User::where('email', $firebaseUser->email)->first();
            if ($user) {
                $user->update([
                    'email_verified_at' => $firebaseUser->emailVerified ? now() : null,
                ]);
                // Store FCM token if provided
                $this->storeFcmToken($request, $user);
                $token = $user->createToken($firebaseUser->email)->plainTextToken;
                event(new UserLoggedIn($user));
                return response()->json([
                    'success' => true,
                    'message' => __('labels.login_successful'),
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                    'data' => new UserResource($user)
                ]);
            }
            if (!$request->has('mobile')) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: 'labels.new_user',
                    data: [
                        'new_user' => true,
                        'name' => $firebaseUser->displayName,
                        'email' => $firebaseUser->email,
                    ]
                );
            }

            $validated = $request->validate([
                'mobile' => 'required|unique:users|numeric',
                'password' => 'required|string|min:6|confirmed',
                'country' => 'nullable|string|max:255',
                'iso_2' => 'nullable|string|max:2'
            ]);
            $user = User::create([
                'name' => $firebaseUser->displayName ?? $firebaseUser->email,
                'email' => $firebaseUser->email,
                'mobile' => $validated['mobile'],
                'country' => $validated['country'] ?? null,
                'iso_2' => $validated['iso_2'] ?? null,
                'password' => Hash::make($validated['password'])
            ]);
            // Grant welcome wallet balance if configured
            try {
                $systemSettingsResource = $this->settingService->getSettingByVariable(SettingTypeEnum::SYSTEM());
                $systemSettings = $systemSettingsResource?->toArray($request)['value'] ?? [];
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
                Log::error('Welcome wallet credit failed for user ' . $user->id . ': ' . $th->getMessage());
            }
            event(new UserRegistered($user));
            // Store FCM token if provided
            $this->storeFcmToken($request, $user);
            return response()->json([
                'success' => true,
                'message' => __('labels.registration_successful'),
                'access_token' => $user->createToken($firebaseUser->email)->plainTextToken,
                'token_type' => 'Bearer',
                'data' => new UserResource($user)
            ]);
        } catch (AuthenticationException $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.authentication_error',
                data: ['error' => $e->getMessage()],
            );
        } catch (FailedToVerifyToken $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.invalid_firebase_token',
                data: ['error' => $e->getMessage()],
            );
        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.something_went_wrong',
                data: ['error' => $e->getMessage()],
            );
        }
    }

    /**
     * Handle Firebase Apple authentication callback
     */
    public function appleCallback(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'idToken' => 'required|string',
            ]);

            // Check if Apple login is enabled in settings
            $authSetting = $this->settingService->getSettingByVariable(SettingTypeEnum::AUTHENTICATION());
            $authConfig = $authSetting?->value ?? [];
            if (empty($authConfig['appleLogin'])) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: 'labels.apple_login_not_enabled',
                    data: []
                );
            }

            $auth = $this->getFirebaseAuth();
            if ($auth['success'] === false) {
                return ApiResponseType::sendJsonResponse(
                    $auth['success'],
                    $auth['message'],
                    $auth['data']
                );
            }
            $auth = $auth['data'];

            // Verify the Firebase ID token
            $verifiedIdToken = $auth->verifyIdToken($request->idToken);
            $uid = $verifiedIdToken->claims()->get('sub');

            // Get user info from Firebase
            $firebaseUser = $auth->getUser($uid);

            // Apple may not always return email; try to get it from claims
            $claims = $verifiedIdToken->claims()->all();
            $email = $firebaseUser->email ?? ($claims['email'] ?? null);
            $displayName = $firebaseUser->displayName ?? ($claims['name'] ?? null);

            if ($email) {
                $user = User::where('email', $email)->first();
                if ($user) {
                    $user->update([
                        'email_verified_at' => ($firebaseUser->emailVerified ?? ($claims['email_verified'] ?? false)) ? now() : null,
                    ]);
                    // Store FCM token if provided
                    $this->storeFcmToken($request, $user);
                    $token = $user->createToken($email)->plainTextToken;
                    event(new UserLoggedIn($user));
                    return response()->json([
                        'success' => true,
                        'message' => __('labels.login_successful'),
                        'access_token' => $token,
                        'token_type' => 'Bearer',
                        'data' => new UserResource($user)
                    ]);
                }
            }

            // New user flow
            if (!$request->has('mobile')) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: 'labels.new_user',
                    data: [
                        'new_user' => true,
                        'name' => $displayName,
                        'email' => $email,
                    ]
                );
            }

            // If email was not provided by Apple/Firebase, require it from client during registration
            $rules = [
                'mobile' => 'required|unique:users|numeric',
                'password' => 'required|string|min:6|confirmed',
                'country' => 'nullable|string|max:255',
                'iso_2' => 'nullable|string|max:2',
            ];
            if (!$email) {
                $rules['email'] = 'required|email|unique:users';
            }

            $validated = $request->validate($rules);
            $finalEmail = $email ?? $validated['email'];

            $user = User::create([
                'name' => $displayName ?? $finalEmail,
                'email' => $finalEmail,
                'mobile' => $validated['mobile'],
                'country' => $validated['country'] ?? null,
                'iso_2' => $validated['iso_2'] ?? null,
                'password' => Hash::make($validated['password'])
            ]);
            // Grant welcome wallet balance if configured
            try {
                $systemSettingsResource = $this->settingService->getSettingByVariable(SettingTypeEnum::SYSTEM());
                $systemSettings = $systemSettingsResource?->toArray($request)['value'] ?? [];
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
                Log::error('Welcome wallet credit failed for user ' . $user->id . ': ' . $th->getMessage());
            }
            event(new UserRegistered($user));
            // Store FCM token if provided
            $this->storeFcmToken($request, $user);
            return response()->json([
                'success' => true,
                'message' => __('labels.registration_successful'),
                'access_token' => $user->createToken($finalEmail)->plainTextToken,
                'token_type' => 'Bearer',
                'data' => new UserResource($user)
            ]);
        } catch (AuthenticationException $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.authentication_error',
                data: ['error' => $e->getMessage()],
            );
        } catch (FailedToVerifyToken $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.invalid_firebase_token',
                data: ['error' => $e->getMessage()],
            );
        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.something_went_wrong',
                data: ['error' => $e->getMessage()],
            );
        }
    }

    /**
     * Handle Firebase Phone (Mobile OTP) authentication callback
     */
    public function phoneCallback(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'idToken' => 'required|string'
            ]);

            $auth = $this->getFirebaseAuth();
            if ($auth['success'] === false) {
                return ApiResponseType::sendJsonResponse(
                    $auth['success'],
                    $auth['message'],
                    $auth['data']
                );
            }
            $auth = $auth['data'];

            // Verify the Firebase ID token issued after successful phone OTP verification
            $verifiedIdToken = $auth->verifyIdToken($request->idToken);
            $uid = $verifiedIdToken->claims()->get('sub');

            // Get user info from Firebase (should include phoneNumber)
            $firebaseUser = $auth->getUser($uid);
            $phone = $firebaseUser->phoneNumber ?? null;

            if (empty($phone)) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: 'labels.something_went_wrong',
                    data: ['error' => 'Phone number not found in Firebase user']
                );
            }

            $digitsOnly = preg_replace('/\D+/', '', $phone);

            $possibleMobiles = [
                $phone,
                "+{$digitsOnly}",
                $digitsOnly,
            ];

            for ($i = 1; $i <= 3; $i++) {
                if (strlen($digitsOnly) > $i + 6) { // keep sane minimum length
                    $local = substr($digitsOnly, $i);

                    $possibleMobiles[] = $local;
                    $possibleMobiles[] = "+{$local}";
                }
            }

            $possibleMobiles = array_values(
                array_unique(
                    array_filter($possibleMobiles)
                )
            );

            $user = User::whereIn('mobile', $possibleMobiles)->first();

            if (!$user) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: 'labels.user_not_found',
                    data: []
                );
            }

            // Successful login for existing user
            // Store FCM token if provided
            $this->storeFcmToken($request, $user);
            $token = $user->createToken($phone)->plainTextToken;
            event(new UserLoggedIn($user));
            return response()->json([
                'success' => true,
                'message' => __('labels.login_successful'),
                'access_token' => $token,
                'token_type' => 'Bearer',
                'data' => new UserResource($user)
            ]);

        } catch (AuthenticationException $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.authentication_error',
                data: ['error' => $e->getMessage()],
            );
        } catch (FailedToVerifyToken $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.invalid_firebase_token',
                data: ['error' => $e->getMessage()],
            );
        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.something_went_wrong',
                data: ['error' => $e->getMessage()],
            );
        }
    }
}
