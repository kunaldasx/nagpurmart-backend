<?php

namespace App\Http\Controllers\Api;

use App\Enums\ActiveInactiveStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\DeliveryZoneResource;
use App\Models\DeliveryZone;
use App\Services\DeliveryZoneService;
use App\Types\Api\ApiResponseType;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

#[Group('Delivery Zones')]
class DeliveryZoneApiController extends Controller
{
    /**
     * Get all delivery zones with pagination and search.
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[QueryParameter('page', description: 'Page number for pagination.', type: 'int', default: 1, example: 1)]
    #[QueryParameter('per_page', description: 'Number of items per page.', type: 'int', default: 15, example: 15)]
    #[QueryParameter('search', description: 'Search term to filter delivery zones by name.', type: 'string', example: 'downtown')]
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $searchTerm = $request->input('search');

        $query = DeliveryZone::query();
        $query->where('status', ActiveInactiveStatusEnum::ACTIVE());

        // Add search functionality
        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', '%' . $searchTerm . '%')
                  ->orWhere('slug', 'LIKE', '%' . $searchTerm . '%');
            });
        }

        $zones = $query->orderBy('name')->paginate($perPage);

        $response = [
            'current_page' => $zones->currentPage(),
            'last_page' => $zones->lastPage(),
            'per_page' => $zones->perPage(),
            'total' => $zones->total(),
            'data' => DeliveryZoneResource::collection($zones->items()),
        ];

        return ApiResponseType::sendJsonResponse(
            success: true,
            message: __('messages.delivery_zones_found'),
            data: $response
        );
    }

    /**
     * Get a specific delivery zone by ID.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $zone = DeliveryZone::where('status', ActiveInactiveStatusEnum::ACTIVE())->find($id);

        if (!$zone) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('messages.delivery_zone_not_found'),
                data: []
            );
        }

        return ApiResponseType::sendJsonResponse(
            success: true,
            message: __('messages.delivery_zone_found'),
            data: new DeliveryZoneResource($zone)
        );
    }

    /**
     * Check if a location is deliverable.
     */
    #[QueryParameter('latitude', description: 'Latitude coordinate of the location.', type: 'float', example: 40.7128)]
    #[QueryParameter('longitude', description: 'Longitude coordinate of the location.', type: 'float', example: -74.0060)]
    public function checkDelivery(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ], [
            'latitude.required' => __('messages.latitude_required'),
            'latitude.numeric' => __('messages.latitude_numeric'),
            'latitude.between' => __('messages.latitude_between'),
            'longitude.required' => __('messages.longitude_required'),
            'longitude.numeric' => __('messages.longitude_numeric'),
            'longitude.between' => __('messages.longitude_between'),
        ]);

        $latitude = (float)$request->input('latitude');
        $longitude = (float)$request->input('longitude');

        // Validate coordinates
        if (!DeliveryZoneService::validateCoordinates($latitude, $longitude)) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('messages.invalid_coordinates'),
                data: []
            );
        }

        // Check if delivery exists at the given coordinates
        $isDeliverable = DeliveryZoneService::existsAtPoint($latitude, $longitude);

        // Get additional zone information
        $zoneInfo = DeliveryZoneService::getZonesAtPoint($latitude, $longitude);

        $response = [
            'is_deliverable' => $isDeliverable,
            'zone_count' => $zoneInfo['zone_count'],
            'zone' => $zoneInfo['zone'],
            'zone_id' => $zoneInfo['zone_id'],
            'coordinates' => [
                'latitude' => $latitude,
                'longitude' => $longitude,
            ]
        ];

        $message = $isDeliverable
            ? __('labels.delivery_available')
            : __('labels.delivery_not_available');

        return ApiResponseType::sendJsonResponse(
            success: true,
            message: $message,
            data: $response
        );
    }

    /**
     * Get stores by map bounds
     */
    public function storesByMap(Request $request): JsonResponse
    {
        $request->validate([
            'ne_lat' => 'required|numeric',
            'ne_lng' => 'required|numeric',
            'sw_lat' => 'required|numeric',
            'sw_lng' => 'required|numeric',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);


            $stores = DeliveryZoneService::getStoresByBounds(
                $request->ne_lat,
                $request->ne_lng,
                $request->sw_lat,
                $request->sw_lng,
                $request->latitude,
                $request->longitude
            );

        return ApiResponseType::sendJsonResponse(
            success: $stores['success'],
            message: $stores['message'],
            data: $stores['data']
        );
    }
}
