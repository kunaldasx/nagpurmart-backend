<?php

namespace App\Http\Controllers\Api\DeliveryBoy;

use App\Enums\ActiveInactiveStatusEnum;
use App\Enums\DeliveryBoy\DeliveryBoyVerificationStatusEnum;
use App\Enums\SpatieMediaCollectionName;
use App\Events\DeliveryBoy\DeliveryBoyRegistered;
use App\Events\DeliveryBoy\DeliveryBoyStatusUpdatedEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeliveryBoy\RegisterDeliveryBoyRequest;
use App\Http\Resources\DeliveryBoy\DeliveryBoyLocationResource;
use App\Http\Resources\DeliveryBoy\DeliveryBoyResource;
use App\Http\Resources\User\UserResource;
use App\Models\DeliveryBoy;
use App\Models\DeliveryBoyLocation;
use App\Models\User;
use App\Services\DeliveryBoyService;
use App\Services\DeliveryZoneService;
use App\Services\SpatieMediaService;
use App\Traits\AuthTrait;
use App\Types\Api\ApiResponseType;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth as FacadesAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

#[Group('DeliveryBoy Auth')]
class DeliveryBoyAuthApiController extends Controller
{
    use AuthTrait;

    public DeliveryBoyService $deliveryBoyService;

    public function __construct(DeliveryBoyService $deliveryBoyService)
    {
        $this->deliveryBoyService = $deliveryBoyService;
    }

    /**
     * Register a new delivery boy
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function register(RegisterDeliveryBoyRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            DB::beginTransaction();

            // Create user
            $user = User::create([
                'name' => $validated['full_name'],
                'email' => $validated['email'],
                'mobile' => $validated['mobile'],
                'country' => $validated['country'] ?? null,
                'iso_2' => $validated['iso_2'] ?? null,
                'password' => Hash::make($validated['password'])
            ]);

            // Create delivery boy
            $deliveryBoy = DeliveryBoy::create([
                'user_id' => $user->id,
                'delivery_zone_id' => $validated['delivery_zone_id'],
                'status' => 'inactive', // Default to inactive until verified
                'full_name' => $validated['full_name'],
                'address' => $validated['address'],
                'driver_license_number' => $validated['driver_license_number'],
                'vehicle_type' => $validated['vehicle_type'],
                'verification_status' => DeliveryBoyVerificationStatusEnum::PENDING(),
            ]);
            $this->handleMediaUploads($deliveryBoy, $request);
            $this->storeFcmToken($request, $user);
            DB::commit();
            // Dispatch the event
            event(new DeliveryBoyRegistered($user, $deliveryBoy));

            // Revoke all existing tokens
            $user->tokens()->delete();

            return response()->json([
                'success' => true,
                'message' => __('labels.registration_successful'),
                'access_token' => $user->createToken($validated['email'])->plainTextToken,
                'token_type' => 'Bearer',
                'data' => [
                    'user' => $user,
                    'delivery_boy' => $deliveryBoy,
                ]
            ]);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => __('labels.validation_error') . ":- " . $e->getMessage(),
                'data' => []
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => __('labels.registration_failed', ['error' => $e->getMessage()]),
                'data' => []
            ], 500);
        }
    }

    /**
     * Login a delivery boy
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $credentials = $request->validate([
                'email' => 'required|email',
                'password' => 'required'
            ]);

            // Check if user exists and has a delivery boy profile
            if (!FacadesAuth::attempt($credentials)) {
                return response()->json([
                    'success' => false,
                    'message' => __('labels.invalid_credentials'),
                    'data' => []
                ]);
            }

            $user = $request->user();
            if (!$user) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: __('labels.invalid_credentials'),
                    data: []
                );
            }
            $deliveryBoy = DeliveryBoy::where('user_id', $user->id)->first();

            if (!$deliveryBoy) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: __('labels.not_a_delivery_boy'),
                    data: []
                );
            }

            // Check if delivery boy is verified
            if ($deliveryBoy->verification_status !== DeliveryBoyVerificationStatusEnum::VERIFIED) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: __('labels.account_not_verified'),
                    data: [
                        'verification_status' => $deliveryBoy->verification_status,
                        'verification_remark' => $deliveryBoy->verification_remark
                    ]
                );
            }
            $this->storeFcmToken($request, $user);

            // Revoke all existing tokens
            $user->tokens()->delete();

            $token = $user->createToken($user->email)->plainTextToken;

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: __('labels.login_successful'),
                data: [
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                    'user' => $user,
                    'delivery_boy' => $deliveryBoy
                ]
            );
        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.login_failed', ['error' => $e->getMessage()]),
                data: []
            );
        }
    }

    /**
     * Get the authenticated delivery boy's profile
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getProfile(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $deliveryBoy = DeliveryBoy::with('deliveryZone')->where('user_id', $user->id)->first();

            if (!$deliveryBoy) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: __('labels.not_a_delivery_boy'),
                    data: []
                );
            }

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: __('labels.profile_retrieved_successfully'),
                data: [
                    'user' => new UserResource($user),
                    'delivery_boy' => new DeliveryBoyResource($deliveryBoy),
                ]
            );
        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.something_went_wrong'),
                data: ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Update the authenticated delivery boy's profile
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $deliveryBoy = DeliveryBoy::where('user_id', $user->id)->first();

            if (!$deliveryBoy) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: __('labels.not_a_delivery_boy'),
                    data: []
                );
            }
            $validated = $request->validate([
                'full_name' => 'sometimes|string|max:255',
                'address' => 'sometimes|string',
                'driver_license_number' => 'nullable|string|max:255',
                'vehicle_type' => 'nullable|string|max:255',
                'driver_license.*' => 'nullable|image|max:2048',
                'vehicle_registration.*' => 'nullable|image|max:2048',
                'profile_image' => [
                    'nullable',
                    'image',
                    'mimes:jpeg,png,jpg,webp',
                    'max:2048', // 2MB
                ],
            ]);

            DB::beginTransaction();
            // Update delivery boy
            $deliveryBoy->update(array_filter([
                'full_name' => $validated['full_name'] ?? null,
                'address' => $validated['address'] ?? null,
                'driver_license_number' => $validated['driver_license_number'] ?? null,
                'vehicle_type' => $validated['vehicle_type'] ?? null,
            ]));

            $this->handleMediaUploads($deliveryBoy, $request);
            if (!empty($validated['profile_image'])) {
                SpatieMediaService::update($request, $user, SpatieMediaCollectionName::PROFILE_IMAGE());
            }
            DB::commit();

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: __('labels.profile_updated_successfully'),
                data: [
                    'user' => new UserResource($user),
                    'delivery_boy' => new DeliveryBoyResource($deliveryBoy),
                ]
            );
        } catch (ValidationException $e) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.validation_error') . ": " . $e->getMessage(),
                data: []
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.something_went_wrong'),
                data: ['error' => $e->getMessage()]
            );
        }
    }


    /**
     * Handle media uploads for delivery boy (similar to ProductService handleMediaUploads)
     *
     * @param DeliveryBoy $deliveryBoy
     * @param Request $request
     * @return void
     */
    private function handleMediaUploads(DeliveryBoy $deliveryBoy, Request $request): void
    {
        // Handle driver license uploads (array support)
        if ($request->hasFile('driver_license')) {
            // Remove existing driver license files
            $deliveryBoy->clearMediaCollection(SpatieMediaCollectionName::DRIVER_LICENSE());

            // Upload new driver license files
            if (is_array($request->file('driver_license'))) {
                foreach ($request->file('driver_license') as $file) {
                    SpatieMediaService::uploadFromRequest($deliveryBoy, $file, SpatieMediaCollectionName::DRIVER_LICENSE());
                }
            } else {
                // Single file upload
                $deliveryBoy->addMediaFromRequest('driver_license')
                    ->toMediaCollection(SpatieMediaCollectionName::DRIVER_LICENSE());
            }
        }

        // Handle vehicle registration uploads (array support)
        if ($request->hasFile('vehicle_registration')) {
            // Remove existing vehicle registration files
            $deliveryBoy->clearMediaCollection(SpatieMediaCollectionName::VEHICLE_REGISTRATION());

            // Upload new vehicle registration files
            if (is_array($request->file('vehicle_registration'))) {
                foreach ($request->file('vehicle_registration') as $file) {
                    SpatieMediaService::uploadFromRequest($deliveryBoy, $file, SpatieMediaCollectionName::VEHICLE_REGISTRATION());
                }
            } else {
                // Single file upload
                $deliveryBoy->addMediaFromRequest('vehicle_registration')
                    ->toMediaCollection(SpatieMediaCollectionName::VEHICLE_REGISTRATION());
            }
        }
    }

    /**
     * Update the delivery boy's status
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateStatus(Request $request): JsonResponse
    {
        try {
            DB::beginTransaction();
            $user = $request->user();

            if (!$user->deliveryBoy) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: __('labels.not_a_delivery_boy'),
                    data: []
                );
            }
            $validated = $request->validate([
                'status' => ['required', new Enum(ActiveInactiveStatusEnum::class)],
                'latitude' => 'required_if:status,' . ActiveInactiveStatusEnum::ACTIVE() . '|numeric',
                'longitude' => 'required_if:status,' . ActiveInactiveStatusEnum::ACTIVE() . '|numeric',
            ]);

            if ($validated['status'] === ActiveInactiveStatusEnum::ACTIVE()) {
                // Validate coordinates range
                $areCoordsValid = DeliveryZoneService::validateCoordinates(
                    latitude: (float)$validated['latitude'],
                    longitude: (float)$validated['longitude']
                );
                if (!$areCoordsValid) {
                    return ApiResponseType::sendJsonResponse(
                        success: false,
                        message: __('labels.invalid_coordinates'),
                        data: []
                    );
                }

                // Find zone by current coordinates and update delivery boy's zone
                $zoneInfo = DeliveryZoneService::getZonesAtPoint((float)$validated['latitude'], (float)$validated['longitude']);
                if (!$zoneInfo['exists'] || empty($zoneInfo['zone_id'])) {
                    return ApiResponseType::sendJsonResponse(
                        success: false,
                        message: __('labels.location_not_in_delivery_zone'),
                        data: []
                    );
                }
            }

            $validateOrders = $this->deliveryBoyService->validatePendingOrders(delivery_boy_id: $user->deliveryBoy->id);
            if ($validateOrders['success'] === false) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: __('labels.you_cant_offline_with_pending_orders'),
                    data: $validateOrders['data'] ?? []
                );
            }
            // Update status (and zone when going active)
            if ($validated['status'] === ActiveInactiveStatusEnum::ACTIVE()) {
                // Safe to reference $zoneInfo here because we validated in the ACTIVE branch above
                $user->deliveryBoy->update([
                    'status' => $validated['status'],
                    'delivery_zone_id' => $zoneInfo['zone_id'],
                ]);
            } else {
                $user->deliveryBoy->update(['status' => $validated['status']]);
            }

            // Dispatch the event
            event(new DeliveryBoyStatusUpdatedEvent(
                deliveryBoy: $user->deliveryBoy,
                updatedBy: $user,
                newStatus: $validated['status'],
                latitude: $validated['latitude'] ?? null,
                longitude: $validated['longitude'] ?? null
            ));
            DB::commit();

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: __('labels.status_updated_successfully'),
                data: [
                    'delivery_boy' => $user->deliveryBoy
                ]
            );
        } catch (ValidationException $e) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.validation_error') . ": " . $e->getMessage(),
                data: []
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.something_went_wrong'),
                data: ['error' => $e->getMessage()]
            );
        }
    }


    /**
     * Get Delivery boy last location
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getLastLocation(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user->deliveryBoy) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: __('labels.not_a_delivery'),
                    data: []
                );
            }
            $location = $this->deliveryBoyService->getLastLocation($user->deliveryBoy->id);
            return ApiResponseType::sendJsonResponse(
                success: $location['success'],
                message: $location['message'],
                data: $location['data']
            );

        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.something_went_wrong'),
                data: ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Update Delivery boy location
     *
     * @param Request $request
     * @return JsonResponse
     */

    public function updateCurrentLocation(Request $request): JsonResponse
    {
        try {

            $user = $request->user();

            if (!$user->deliveryBoy) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: __('labels.not_a_delivery_boy'),
                    data: []
                );
            }
            $validated = $request->validate([
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric',
            ]);
            $validateCoordinates = DeliveryZoneService::validateCoordinates(latitude: $validated['latitude'], longitude: $validated['longitude']);
            if (!$validateCoordinates) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: __('labels.invalid_coordinates'),
                    data: []
                );
            }
            $data = DeliveryBoyLocation::updateOrCreate(
                ['delivery_boy_id' => $user->deliveryBoy->id],
                [
                    'latitude' => $validated['latitude'],
                    'longitude' => $validated['longitude'],
                    'recorded_at' => now(),
                ]
            );
            return ApiResponseType::sendJsonResponse(
                success: true,
                message: __('labels.current_location_updated_successfully'),
                data: $data
            );
        } catch (ValidationException $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.validation_error') . ": " . $e->getMessage(),
                data: []
            );
        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.something_went_wrong'),
                data: ['error' => $e->getMessage()]
            );
        }
    }
}
