<?php

namespace App\Http\Controllers\Api;

use App\Enums\Store\StoreVerificationStatusEnum;
use App\Enums\Store\StoreVisibilityStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Store\GetStoreByLocation;
use App\Http\Resources\StoreResource;
use App\Models\Store;
use App\Services\DeliveryZoneService;
use App\Types\Api\ApiResponseType;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Stores')]
class StoreApiController extends Controller
{
    /**
     * Get stores based on delivery zone from latitude and longitude.
     */
    #[QueryParameter('latitude', description: 'Latitude coordinate of the location.', type: 'float', example: 23.2420)]
    #[QueryParameter('longitude', description: 'Longitude coordinate of the location.', type: 'float', example: 69.6669)]
    #[QueryParameter('page', description: 'Page number for pagination.', type: 'int', default: 1, example: 1)]
    #[QueryParameter('per_page', description: 'Number of items per page.', type: 'int', default: 15, example: 15)]
    #[QueryParameter('search', description: 'Search term to filter store by name, description, address, or seller name', type: 'string', example: 'Beauty Essentials')]
    public function getStoresByLocation(GetStoreByLocation $request): JsonResponse
    {
        // Validate coordinates
        $validated = $request->validated();

        $latitude = (float)$validated['latitude'];
        $longitude = (float)$validated['longitude'];
        $search = $validated['search'] ?? '';
        $perPage = $validated['per_page'] ?? 15;

        // Validate coordinates
        if (!DeliveryZoneService::validateCoordinates($latitude, $longitude)) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'messages.invalid_coordinates',
                data: []
            );
        }

        // Get zones at the given coordinates
        $zoneInfo = DeliveryZoneService::getZonesAtPoint($latitude, $longitude);

        if (!$zoneInfo['exists']) {
            return ApiResponseType::sendJsonResponse(
                success: true,
                message: 'labels.no_delivery_zone_found',
                data: []
            );
        }

        // Get stores in the delivery zone
        $query = Store::whereHas('zones', function ($q) use ($zoneInfo) {
            $q->where('delivery_zones.id', $zoneInfo['zone_id']);
        })
            ->where('verification_status', StoreVerificationStatusEnum::APPROVED())
            ->where('visibility_status', StoreVisibilityStatusEnum::VISIBLE())
            ->orderBy('name');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhereHas('seller.user', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Paginate results
        $stores = $query->paginate($perPage);

        // Transform using StoreResource and pass coordinates
        $stores->getCollection()->transform(function ($store) use ($latitude, $longitude) {
            $store->user_latitude = $latitude;
            $store->user_longitude = $longitude;
            return new StoreResource($store);
        });

        return ApiResponseType::sendJsonResponse(
            success: true,
            message: 'labels.stores_fetched_successfully',
            data: $stores
        );
    }

    /**
     * Get stores by specific delivery zone ID.
     */
    #[QueryParameter('zone_id', description: 'Delivery zone ID.', type: 'int', example: 1)]
    #[QueryParameter('page', description: 'Page number for pagination.', type: 'int', default: 1, example: 1)]
    #[QueryParameter('per_page', description: 'Number of items per page.', type: 'int', default: 15, example: 15)]
    public function getStoresByZone(Request $request): JsonResponse
    {
        $request->validate([
            'zone_id' => 'required|integer|exists:delivery_zones,id',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'page' => 'sometimes|integer|min:1',
        ]);

        $zoneId = $request->input('zone_id');
        $perPage = $request->input('per_page', 15);

        $query = Store::where('delivery_zone_id', $zoneId)
            ->where('verification_status', StoreVerificationStatusEnum::APPROVED())->where('visibility_status', StoreVisibilityStatusEnum::VISIBLE())
            ->orderBy('name');

        $stores = $query->paginate($perPage);
        $stores->getCollection()->transform(fn($store) => new StoreResource($store));

        return ApiResponseType::sendJsonResponse(
            success: true,
            message: 'labels.stores_fetched_successfully',
            data: $stores
        );
    }

    /**
     * Get Store by Slug
     */
    #[QueryParameter('latitude', description: 'Latitude coordinate of the location.', type: 'float', example: 40.7128)]
    #[QueryParameter('longitude', description: 'Longitude coordinate of the location.', type: 'float', example: -74.0060)]
    public function show($slug, Request $request): JsonResponse
    {
        $store = Store::where('slug', $slug)
            ->where('verification_status', StoreVerificationStatusEnum::APPROVED())->where('visibility_status', StoreVisibilityStatusEnum::VISIBLE())->get()->first();

        if (!empty($store)) {
            $store->user_latitude = $request->input('latitude');
            $store->user_longitude = $request->input('longitude');
        }
        return ApiResponseType::sendJsonResponse(
            success: true,
            message: 'labels.store_fetched_successfully',
            data: new StoreResource($store)
        );
    }

    /**
     * Get all active stores with pagination.
     */
    #[QueryParameter('page', description: 'Page number for pagination.', type: 'int', default: 1, example: 1)]
    #[QueryParameter('per_page', description: 'Number of items per page.', type: 'int', default: 15, example: 15)]
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);

        $query = Store::where('verification_status', StoreVerificationStatusEnum::APPROVED())->where('visibility_status', StoreVisibilityStatusEnum::VISIBLE())
            ->orderBy('name');

        $stores = $query->paginate($perPage);
        $stores->getCollection()->transform(fn($store) => new StoreResource($store));

        return ApiResponseType::sendJsonResponse(
            success: true,
            message: 'labels.stores_fetched_successfully',
            data: $stores,
        );
    }
}
