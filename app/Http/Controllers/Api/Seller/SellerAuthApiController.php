<?php

namespace App\Http\Controllers\Api\Seller;

use App\Enums\Seller\SellerVerificationStatusEnum;
use App\Enums\Seller\SellerVisibilityStatusEnum;
use App\Http\Requests\Seller\StoreSellerRequest;
use App\Services\SellerService;
use App\Models\User;
use App\Traits\AuthTrait;
use App\Types\Api\ApiResponseType;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use App\Models\Setting;

#[Group('Seller Authentication')]
class SellerAuthApiController
{
    use AuthTrait;

    protected string $role = 'seller';

    protected $sellerService;

    public function __construct(SellerService $sellerService)
    {
        $this->sellerService = $sellerService;
    }

    /**
     * creating sellers API
     */
    public function createSeller(StoreSellerRequest $request): JsonResponse
    {
        // Restrict seller self-registration in Single Vendor mode
        if (Setting::isSystemVendorTypeSingle()) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.seller_registration_disabled'),
                status: 403
            );
        }

        try {
            $validated = $request->validated();
            $validated['verification_status'] = SellerVisibilityStatusEnum::Draft();
            $validated['visibility_status'] = SellerVerificationStatusEnum::NotApproved();
            $seller = $this->sellerService->createSeller(
                $validated,
                $request->allFiles()
            );

            return ApiResponseType::sendJsonResponse(
                true,
                'labels.seller_created_successfully',
                $seller,
                201
            );
        } catch (ValidationException $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.validation_failed' . $e->getMessage(),
                data: $e->errors(),
            );
        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(
                success: true,
                message: 'labels.seller_created_successfully',
                status: 500
            );
        }

        public function registerFcmToken(Request $request): JsonResponse
        {
            $validated = $request->validate([
                'fcm_token' => ['required', 'string', 'max:4096'],
                'device_type' => ['required', 'string', 'in:android,ios,web'],
            ]);

            $user = $request->user();
            if (!$user instanceof User || !$user->seller()) {
                return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found'), [], 404);
            }

            $this->storeFcmToken($request, $user);

            return ApiResponseType::sendJsonResponse(true, 'FCM token registered successfully.', [
                'device_type' => $validated['device_type'],
            ]);
        }
    }
}
