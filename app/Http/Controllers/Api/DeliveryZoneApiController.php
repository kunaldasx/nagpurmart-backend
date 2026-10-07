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
            'active_hours' => $zoneInfo['active_hours'],
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
    * Estimate delivery time using the same route and ETA formula as checkout.
     */
    #[QueryParameter('latitude', description: 'Latitude coordinate of the customer.', type: 'float', example: 23.11684540)]
    #[QueryParameter('longitude', description: 'Longitude coordinate of the customer.', type: 'float', example: 70.02805670)]
    #[QueryParameter('store_id', description: 'Optional store id. If omitted, nearest available store will be picked.', type: 'int', example: 1)]
    #[QueryParameter('store_ids[]', description: 'Optional cart store IDs. Pass all selected cart stores to match the checkout ETA for a multi-store cart.', type: 'array', example: [1, 2])]
    public function estimateDeliveryTime(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'address_id' => 'nullable|integer|exists:addresses,id',
            'store_id' => 'nullable|integer|exists:stores,id',
            'store_ids' => 'nullable|array|min:1',
            'store_ids.*' => 'required|integer|distinct|exists:stores,id',
        ], [
            'latitude.numeric' => __('messages.latitude_numeric'),
            'longitude.numeric' => __('messages.longitude_numeric'),
            'store_id.required' => __('messages.store_required'),
            'store_id.exists' => __('messages.store_not_found'),
            'address_id.exists' => __('labels.address_not_found'),
        ]);

        $storeId = $request->input('store_id');
        $requestedStoreIds = array_map('intval', (array) $request->input('store_ids', []));

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

        // Cart store IDs take precedence so the result can match checkout exactly.
        if (!empty($requestedStoreIds)) {
            $requestedStores = Store::whereIn('id', $requestedStoreIds)->get();
            if ($requestedStores->count() === count(array_unique($requestedStoreIds))) {
                $selectedStore = $requestedStores->first();
            }
        } elseif (!empty($storeId)) {
            // If store_id provided, use it
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
            $pausedZone = DeliveryZoneService::getPausedZoneAtPoint($latitude, $longitude);
            return ApiResponseType::sendJsonResponse(success: true, message: __('labels.delivery_not_available'), data: [
                'is_deliverable' => false,
                'delivery_paused' => (bool) $pausedZone,
                'delivery_pause_until' => $pausedZone?->delivery_paused_until?->toISOString(),
                'delivery_pause_comment' => $pausedZone?->delivery_pause_comment,
                'coordinates' => ['latitude' => $latitude, 'longitude' => $longitude],
            ]);
        }

        $routeStoreIds = !empty($requestedStoreIds)
            ? array_values(array_unique($requestedStoreIds))
            : [(int) $selectedStore->id];
        $routeStores = Store::whereIn('id', $routeStoreIds)->get();
        $canDeliver = count($routeStoreIds) === $routeStores->count()
            && $routeStores->every(fn (Store $store) =>
                !empty($store->latitude)
                && !empty($store->longitude)
                && DeliveryZoneService::canStoreDeliverToLocation($store, $latitude, $longitude)
            );

        $response = [
            'is_deliverable' => $canDeliver,
            'store_id' => $selectedStore->id,
            'store_name' => $selectedStore->name ?? null,
            'store_ids' => $routeStoreIds,
            'coordinates' => [
                'latitude' => $latitude,
                'longitude' => $longitude,
            ],
        ];

        if (!$canDeliver) {
            $pausedZone = DeliveryZoneService::getPausedZoneAtPoint($latitude, $longitude);
            $response['delivery_paused'] = (bool) $pausedZone;
            $response['delivery_pause_until'] = $pausedZone?->delivery_paused_until?->toISOString();
            $response['delivery_pause_comment'] = $pausedZone?->delivery_pause_comment;
            return ApiResponseType::sendJsonResponse(success: true, message: __('labels.delivery_not_available'), data: $response);
        }

        $routeInfo = DeliveryZoneService::calculateDeliveryRoute($latitude, $longitude, $routeStoreIds);
        $distance = (float) ($routeInfo['total_distance'] ?? 0);
        $zoneInfo = DeliveryZoneService::getZonesAtPoint($latitude, $longitude);
        $basePrepTime = 5;
        $deliveryTimePerKm = (float) ($zoneInfo['delivery_time_per_km'] ?? 0);
        $bufferTime = (int) ($zoneInfo['buffer_time'] ?? 0);
        $distanceMinutes = (int) ceil($distance * $deliveryTimePerKm);
        $estimatedTotalMinutes = DeliveryZoneService::calculateEstimatedDeliveryMinutes(
            $distance,
            $deliveryTimePerKm,
            $bufferTime,
            $basePrepTime,
            (int) ($zoneInfo['delivery_wait_minutes'] ?? 0),
        );

        $response['distance_km'] = round($distance, 2);
        $response['distance_minutes'] = $distanceMinutes;
        $response['base_prep_time_minutes'] = $basePrepTime;
        $response['delivery_time_per_km'] = $deliveryTimePerKm;
        $response['buffer_time_minutes'] = $bufferTime;
        $response['buffer_comment'] = $zoneInfo['buffer_comment'] ?? null;
        $response['active_hours'] = $zoneInfo['active_hours'] ?? null;
        $response['delivery_paused'] = $zoneInfo['delivery_paused'] ?? false;
        $response['delivery_pause_until'] = $zoneInfo['delivery_paused_until'] ?? null;
        $response['delivery_pause_comment'] = $zoneInfo['delivery_pause_comment'] ?? null;
        $response['delivery_start_at'] = $zoneInfo['delivery_start_at'] ?? null;
        $response['delivery_wait_minutes'] = $zoneInfo['delivery_wait_minutes'] ?? 0;
        $response['calculation'] = [
            'base_prep_time_minutes' => $basePrepTime,
            'delivery_time_per_km' => $deliveryTimePerKm,
            'buffer_time_minutes' => $bufferTime,
            'distance_minutes' => $distanceMinutes,
            'delivery_wait_minutes' => $zoneInfo['delivery_wait_minutes'] ?? 0,
            'estimated_time_minutes' => (int) $estimatedTotalMinutes,
        ];
        $response['estimated_time_minutes'] = (int)$estimatedTotalMinutes;

        return ApiResponseType::sendJsonResponse(success: true, message: __('labels.estimated_delivery_time'), data: $response);
    }
}
