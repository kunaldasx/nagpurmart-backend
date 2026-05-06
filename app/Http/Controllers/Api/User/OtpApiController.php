<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use App\Services\WalletService;
use App\Services\SettingService;
use App\Enums\SettingTypeEnum;
use App\Types\Api\ApiResponseType;
use App\Http\Resources\User\UserResource;
use App\Events\Auth\UserLoggedIn;
use App\Events\Auth\UserRegistered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class OtpApiController extends Controller
{
    protected OtpService $otpService;
    protected SettingService $settingService;

    public function __construct(OtpService $otpService, SettingService $settingService)
    {
        $this->otpService = $otpService;
        $this->settingService = $settingService;
    }

    /**
     * Send OTP to mobile number
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'mobile' => 'required|string',
        ]);

        if ($validator->fails()) {
            return ApiResponseType::sendJsonResponse(
                false,
                'Validation failed',
                ['errors' => $validator->errors()]
            );
        }

        $mobile = $request->input('mobile');

        // Send OTP
        $result = $this->otpService->sendOtp($mobile);

        Log::info('Send OTP Response', [
            'input_mobile' => $mobile,
            'result' => $result
        ]);

        if ($result['success']) {
            Log::info('Send OTP Success Response', [
                'mobile' => $mobile,
                'expires_in' => $result['expires_in'],
                'message' => $result['message']
            ]);

            return ApiResponseType::sendJsonResponse(
                true,
                $result['message'],
                [
                    'mobile' => $mobile,
                    'expires_in' => $result['expires_in']
                ]
            );
        }

        Log::error('Send OTP Failed Response', [
            'mobile' => $mobile,
            'error_message' => $result['message'],
            'debug_info' => $result['debug'] ?? []
        ]);

        return ApiResponseType::sendJsonResponse(
            false,
            $result['message'],
            $result['debug'] ?? []
        );
    }

    /**
     * Verify OTP and authenticate user
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'mobile' => 'required|string',
            'otp' => 'required|string|size:6',
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users',
            'password' => 'nullable|string|min:6|confirmed',
            'country' => 'nullable|string|max:255',
            'iso_2' => 'nullable|string|max:2',
        ]);

        if ($validator->fails()) {
            return ApiResponseType::sendJsonResponse(
                false,
                'Validation failed',
                ['errors' => $validator->errors()]
            );
        }

        $mobile = $request->input('mobile');
        $otp = $request->input('otp');

        // Sanitize mobile for consistent lookup
        $sanitizedMobile = $this->otpService->sanitizeMobile($mobile);

        // Verify OTP
        $verificationResult = $this->otpService->verifyOtp($sanitizedMobile, $otp);

        Log::info('OTP Verification Response', [
            'mobile' => $sanitizedMobile,
            'otp' => $otp,
            'verification_result' => $verificationResult
        ]);

        if (!$verificationResult['success']) {
            Log::error('OTP Verification Failed Response', [
                'mobile' => $sanitizedMobile,
                'otp' => $otp,
                'error_message' => $verificationResult['message'],
                'full_response' => $verificationResult
            ]);

            return ApiResponseType::sendJsonResponse(
                false,
                $verificationResult['message'],
                $verificationResult
            );
        }

        // OTP verified - now handle user authentication/registration
        try {
            // Check if user exists (using sanitized mobile)
            $user = User::withTrashed()->where('mobile', $sanitizedMobile)->first();

            if ($user) {
                if ($user->trashed()) {
                    $user->restore();
                }
                // Existing user - log them in
                $token = $user->createToken($sanitizedMobile)->plainTextToken;
                event(new UserLoggedIn($user));

                return response()->json([
                    'success' => true,
                    'message' => __('labels.verified_successfully'),
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                    'data' => new UserResource($user)
                ]);
            }

            // New user - check if registration data provided
            if (!$request->has('name') || !$request->has('password')) {
                return ApiResponseType::sendJsonResponse(
                    true,
                     __('labels.verified_successfully'),
                    [
                        'new_user' => true,
                        'mobile' => $mobile,
                        'otp_verified' => true
                    ]
                );
            }

            // Create new user
            $user = User::create([
                'name' => $request->input('name'),
                'email' => $request->input('email') ?? $mobile . '@zunof.app',
                'mobile' => $sanitizedMobile,
                'iso_2' => $request->input('iso_2'),
                'password' => Hash::make($request->input('password')),
                'country' => $request->input('country'),
                'mobile_verified_at' => now(),
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

            $token = $user->createToken($mobile)->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => __('labels.registration_successful'),
                'access_token' => $token,
                'token_type' => 'Bearer',
                'data' => new UserResource($user)
            ]);

        } catch (\Exception $e) {
            Log::error('OTP authentication failed', [
                'mobile' => $mobile,
                'error' => $e->getMessage()
            ]);

            return ApiResponseType::sendJsonResponse(
                false,
                'Authentication failed: ' . $e->getMessage(),
                []
            );
        }
    }
    /**
     * Web Registration: Capture details first, then send OTP
     */
    public function webRegister(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'mobile' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
            'country' => 'nullable|string|max:255',
            'iso_2' => 'nullable|string|max:2',
        ]);

        if ($validator->fails()) {
            return ApiResponseType::sendJsonResponse(
                false,
                'Validation failed',
                ['errors' => $validator->errors()]
            );
        }

        // Sanitize mobile number for consistent lookup
        $mobile = $this->otpService->sanitizeMobile($request->input('mobile'));
        $email = $request->input('email');

        // Check if user exists with this mobile
        $user = User::where('mobile', $mobile)->withTrashed()->first();

        if ($user) {
            if ($user->trashed()) {
                return ApiResponseType::sendJsonResponse(
                    false,
                    'Account has been deleted. Please contact support.',
                    []
                );
            }
            // If user exists and mobile is verified, they should login instead
            if ($user->mobile_verified_at) {
                return ApiResponseType::sendJsonResponse(
                    false,
                    'User already registered and verified. Please login.',
                    []
                );
            }

            // User exists but not verified - update details
            $user->update([
                'name' => $request->input('name'),
                'email' => $email,
                'password' => Hash::make($request->input('password')),
                'country' => $request->input('country'),
                'iso_2' => $request->input('iso_2'),
            ]);
        } else {
            // Check if email is taken by another user
             $emailUser = User::where('email', $email)->first();
             if ($emailUser) {
                 return ApiResponseType::sendJsonResponse(
                     false,
                     'Email is already in use by another account.',
                     []
                 );
             }

            // Create new inactive user
            $user = User::create([
                'name' => $request->input('name'),
                'email' => $email,
                'mobile' => $mobile,
                'password' => Hash::make($request->input('password')),
                'country' => $request->input('country'),
                'iso_2' => $request->input('iso_2'),
                'status' => 0, // Inactive until OTP verified
                'mobile_verified_at' => null,
            ]);
        }

        // Send OTP
        $result = $this->otpService->sendOtp($mobile);

        if ($result['success']) {
            return ApiResponseType::sendJsonResponse(
                true,
                'Registration details saved. ' . $result['message'],
                [
                    'mobile' => $mobile,
                    'expires_in' => $result['expires_in']
                ]
            );
        }

        return ApiResponseType::sendJsonResponse(
            false,
            'Failed to send OTP: ' . $result['message'],
            $result['debug'] ?? []
        );
    }

    /**
     * Web Verify OTP: Verify and Activate User
     */
    public function webVerifyOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'mobile' => 'required|string',
            'otp' => 'required|string',
        ]);

        if ($validator->fails()) {
            return ApiResponseType::sendJsonResponse(
                false,
                'Validation failed',
                ['errors' => $validator->errors()]
            );
        }

        $mobile = $this->otpService->sanitizeMobile($request->input('mobile'));
        $otp = $request->input('otp');

        // Verify OTP via service
        $verificationResult = $this->otpService->verifyOtp($mobile, $otp);

        if (!$verificationResult['success']) {
            return ApiResponseType::sendJsonResponse(
                false,
                $verificationResult['message'],
                $verificationResult
            );
        }

        // OTP Valid - Activate User
        try {
            $user = User::where('mobile', $mobile)->first();

            if (!$user) {
                return ApiResponseType::sendJsonResponse(
                    false,
                    'User not found.',
                    []
                );
            }

            // Activate user if not already active
            if ($user->status == 0 || $user->mobile_verified_at == null) {
                $user->update([
                    'status' => 1,
                    'mobile_verified_at' => now(),
                ]);

                 // Grant welcome wallet balance if configured (First time verification)
                try {
                    $systemSettingsResource = $this->settingService->getSettingByVariable(SettingTypeEnum::SYSTEM());
                    $systemSettings = $systemSettingsResource?->toArray($request)['value'] ?? [];
                    $welcomeAmount = (float)($systemSettings['welcomeWalletBalanceAmount'] ?? 0);

                    if ($welcomeAmount > 0) {
                        $walletService = app(WalletService::class);
                        // Check if wallet balance already exists to avoid double credit if they retry
                         if ($user->wallet && $user->wallet->balance == 0) {
                            $walletService->addBalance($user->id, [
                                'amount' => $welcomeAmount,
                                'payment_method' => 'system',
                                'description' => __('labels.welcome_wallet_bonus') ?? 'Welcome bonus added to wallet',
                            ]);
                         }
                    }
                } catch (\Throwable $th) {
                    Log::error('Welcome wallet credit failed for user ' . $user->id . ': ' . $th->getMessage());
                }

                event(new UserRegistered($user));
            }

            // Login
            $token = $user->createToken($mobile)->plainTextToken;
            event(new UserLoggedIn($user));

            return response()->json([
                'success' => true,
                'message' => __('labels.registration_successful'),
                'access_token' => $token,
                'token_type' => 'Bearer',
                'data' => new UserResource($user)
            ]);

        } catch (\Exception $e) {
            Log::error('Web OTP verification failed', [
                'mobile' => $mobile,
                'error' => $e->getMessage()
            ]);

             return ApiResponseType::sendJsonResponse(
                false,
                'Authentication failed: ' . $e->getMessage(),
                []
            );
        }
    }
}
