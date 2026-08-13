<?php

namespace App\Http\Controllers\Api;

use App\Enums\ActiveInactiveStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\DeliveryZoneResource;
use App\Models\DeliveryZone;
use App\Models\Store;
use App\Models\Address;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

    /**
     * Estimate delivery time for a given store and user coordinates.
     *
     * Formula used: 5 minutes fixed preparation + 3 minutes per km (rounded up by km).
     * Returns time only if the store can deliver to the provided coordinates.
     */
    #[QueryParameter('latitude', description: 'Latitude coordinate of the customer.', type: 'float', example: 23.11684540)]
    #[QueryParameter('longitude', description: 'Longitude coordinate of the customer.', type: 'float', example: 70.02805670)]
    #[QueryParameter('store_id', description: 'Optional store id. If omitted, nearest available store will be picked.', type: 'int', example: 1)]
    public function estimateDeliveryTime(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'address_id' => 'nullable|integer|exists:addresses,id',
            'store_id' => 'nullable|integer|exists:stores,id',
        ], [
            'latitude.numeric' => __('messages.latitude_numeric'),
            'longitude.numeric' => __('messages.longitude_numeric'),
            'store_id.required' => __('messages.store_required'),
            'store_id.exists' => __('messages.store_not_found'),
            'address_id.exists' => __('labels.address_not_found'),
        ]);

        $storeId = $request->input('store_id');

        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');
        $addressId = $request->input('address_id');

        // If address_id provided, get coordinates from address (ensures it's the user's address)
        if (empty($latitude) || empty($longitude)) {
            if (!empty($addressId) && Auth::check()) {
                $address = Address::where(['user_id' => Auth::id(), 'id' => $addressId])->first();
                if ($address) {
                    $latitude = $address->latitude;
                    $longitude = $address->longitude;
                }
            }
        }

        if (empty($latitude) || empty($longitude)) {
            return ApiResponseType::sendJsonResponse(success: false, message: __('messages.missing_coordinates'), data: []);
        }

        $latitude = (float)$latitude;
        $longitude = (float)$longitude;

        if (!DeliveryZoneService::validateCoordinates($latitude, $longitude)) {
            return ApiResponseType::sendJsonResponse(success: false, message: __('messages.invalid_coordinates'), data: []);
        }

        $selectedStore = null;

        // If store_id provided, use it
        if (!empty($storeId)) {
            $selectedStore = Store::find((int)$storeId);
        } else {
            // Find nearest stores (limit 10) and pick the first that can deliver
            $raw = "(6371 * acos( cos(radians($latitude)) * cos(radians(latitude)) * cos(radians(longitude) - radians($longitude)) + sin(radians($latitude)) * sin(radians(latitude)) )) AS distance_from_customer";
            $candidates = Store::select(['id','latitude','longitude','name', DB::raw($raw)])
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->orderBy('distance_from_customer','asc')
                ->limit(10)
                ->get();

            foreach ($candidates as $candidate) {
                if (empty($candidate->latitude) || empty($candidate->longitude)) {
                    continue;
                }
                try {
                    if (DeliveryZoneService::canStoreDeliverToLocation($candidate, $latitude, $longitude)) {
                        $selectedStore = $candidate;
                        break;
                    }
                } catch (\Throwable $e) {
                    // ignore and continue
                }
            }
        }

        if (!$selectedStore || !$selectedStore->latitude || !$selectedStore->longitude) {
            return ApiResponseType::sendJsonResponse(success: true, message: __('labels.delivery_not_available'), data: ['is_deliverable' => false, 'coordinates' => ['latitude' => $latitude, 'longitude' => $longitude]]);
        }

        // Check delivery availability (defensive)
        $canDeliver = DeliveryZoneService::canStoreDeliverToLocation($selectedStore, $latitude, $longitude);

        $response = [
            'is_deliverable' => $canDeliver,
            'store_id' => $selectedStore->id,
            'store_name' => $selectedStore->name ?? null,
            'coordinates' => [
                'latitude' => $latitude,
                'longitude' => $longitude,
            ],
        ];

        if (!$canDeliver) {
            return ApiResponseType::sendJsonResponse(success: true, message: __('labels.delivery_not_available'), data: $response);
        }

        // Calculate distance in km between store and user
        $distance = DeliveryZoneService::calculateDistance((float)$selectedStore->latitude, (float)$selectedStore->longitude, $latitude, $longitude);

        // Use mapping: 0-1km -> 3 mins, 1.1-2km -> 6 mins, etc. (3 mins per rounded-up km)
        $distanceKmRounded = max(1, (int)ceil($distance));
        $distanceMinutes = $distanceKmRounded * 3;

        $fixedPrep = 5; // fixed preparation time in minutes
        $estimatedTotalMinutes = $fixedPrep + $distanceMinutes;

        $response['distance_km'] = round($distance, 2);
        $response['distance_minutes'] = $distanceMinutes;
        $response['estimated_time_minutes'] = (int)$estimatedTotalMinutes;

        return ApiResponseType::sendJsonResponse(success: true, message: __('labels.estimated_delivery_time'), data: $response);
    }
}
