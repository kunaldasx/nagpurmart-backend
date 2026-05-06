<?php

namespace App\Services;

use App\Enums\ActiveInactiveStatusEnum;
use App\Enums\Product\ProductVarificationStatusEnum;
use App\Enums\Order\OrderItemStatusEnum;
use App\Enums\Store\StoreVerificationStatusEnum;
use App\Enums\Store\StoreVisibilityStatusEnum;
use App\Http\Resources\StoreResource;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\DeliveryZone;
use App\Models\Store;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use function PHPUnit\Framework\isString;

class DeliveryZoneService
{
    /**
     * Check if a point exists inside a delivery zone
     */
    public static function containsPoint(DeliveryZone $zone, float $latitude, float $longitude): bool
    {
        if (!empty($zone->boundary_json)) {
            return self::isPointInPolygon($zone, $latitude, $longitude);
        }

        return self::isPointInRadius($zone, $latitude, $longitude);
    }

    /**
     * Check if point is within radius
     */
    public static function isPointInRadius(DeliveryZone $zone, float $latitude, float $longitude): bool
    {
        $distance = self::calculateDistance(
            $zone->center_latitude,
            $zone->center_longitude,
            $latitude,
            $longitude
        );

        return $distance <= $zone->radius_km;
    }

    /**
     * Point in polygon check using ray casting algorithm
     */
    public static function isPointInPolygon(DeliveryZone $zone, float $latitude, float $longitude): bool
    {
        $polygon = $zone->boundary_json;

        if (isset($polygon['coordinates'][0])) {
            $coordinates = $polygon['coordinates'][0];
        } elseif (isset($polygon[0]['lat']) || isset($polygon[0]['latitude'])) {
            $coordinates = $polygon;
        } else {
            return self::isPointInRadius($zone, $latitude, $longitude);
        }

        $intersections = 0;
        $vertexCount = count($coordinates);

        for ($i = 0; $i < $vertexCount; $i++) {
            $j = ($i + 1) % $vertexCount;

            $xi = $coordinates[$i]['lat'] ?? $coordinates[$i]['latitude'] ?? $coordinates[$i][1] ?? 0;
            $yi = $coordinates[$i]['lng'] ?? $coordinates[$i]['longitude'] ?? $coordinates[$i][0] ?? 0;
            $xj = $coordinates[$j]['lat'] ?? $coordinates[$j]['latitude'] ?? $coordinates[$j][1] ?? 0;
            $yj = $coordinates[$j]['lng'] ?? $coordinates[$j]['longitude'] ?? $coordinates[$j][0] ?? 0;

            if ((($yi > $longitude) !== ($yj > $longitude)) &&
                ($latitude < ($xj - $xi) * ($longitude - $yi) / ($yj - $yi) + $xi)) {
                $intersections++;
            }
        }

        return ($intersections % 2) === 1;
    }

    /**
     * Calculate the distance between two points using Haversine formula
     */
    public static function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $latDiff = deg2rad($lat2 - $lat1);
        $lonDiff = deg2rad($lon2 - $lon1);

        $a = sin($latDiff / 2) * sin($latDiff / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lonDiff / 2) * sin($lonDiff / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Get zones that contain the given coordinates
     *
     * Note: This method now returns a single zone ID instead of an array of zone IDs
     * as there will be no overlapping zones.
     */
    public static function getZonesAtPoint(float $latitude, float $longitude): array
    {
        $zones = DeliveryZone::where('status', ActiveInactiveStatusEnum::ACTIVE())
            ->get()
            ->filter(function ($zone) use ($latitude, $longitude) {
                return self::containsPoint($zone, $latitude, $longitude);
            });

        // Get the first zone if any exists
        $zone = $zones->first();

        return [
            'exists' => $zones->isNotEmpty(),
            'zone' => $zone ? $zone->name : null,
            'zone_count' => $zone ? 1 : 0,
            'zone_id' => $zone ? $zone->id : null,
            'handling_charges' => $zone ? $zone->handling_charges : 0,
            'delivery_time_per_km' => $zone ? $zone->delivery_time_per_km : 0,
            'rush_delivery_enabled' => $zone ? $zone->rush_delivery_enabled : false,
            'rush_delivery_time_per_km' => $zone ? $zone->rush_delivery_time_per_km : 0,
            'rush_delivery_charges' => $zone ? $zone->rush_delivery_charges : 0,
            'regular_delivery_charges' => $zone ? $zone->regular_delivery_charges : 0,
            'free_delivery_amount' => $zone ? $zone->free_delivery_amount : 0,
            'distance_based_delivery_charges' => $zone ? $zone->distance_based_delivery_charges : 0,
            'per_store_drop_off_fee' => $zone ? $zone->per_store_drop_off_fee : 0,
            'buffer_time' => $zone ? $zone->buffer_time : 0,
        ];
    }

    /**
     * Check if delivery exists at coordinates (backward compatibility)
     */
    public static function existsAtPoint(float $latitude, float $longitude): bool
    {
        return self::getZonesAtPoint($latitude, $longitude)['exists'];
    }

    /**
     * Get the nearest delivery zone to a point
     */
    public static function getNearestZone(float $latitude, float $longitude): ?DeliveryZone
    {
        $zones = DeliveryZone::where('status', 'active')->get();

        $nearestZone = null;
        $shortestDistance = PHP_FLOAT_MAX;

        foreach ($zones as $zone) {
            $distance = self::calculateDistance(
                $zone->center_latitude,
                $zone->center_longitude,
                $latitude,
                $longitude
            );

            if ($distance < $shortestDistance) {
                $shortestDistance = $distance;
                $nearestZone = $zone;
            }
        }

        return $nearestZone;
    }

    /**
     * Get all zones within a certain distance from a point
     */
    public static function getZonesWithinDistance(float $latitude, float $longitude, float $maxDistance): array
    {
        $zones = DeliveryZone::where('status', ActiveInactiveStatusEnum::ACTIVE())
            ->get()
            ->filter(function ($zone) use ($latitude, $longitude, $maxDistance) {
                $distance = self::calculateDistance(
                    $zone->center_latitude,
                    $zone->center_longitude,
                    $latitude,
                    $longitude
                );
                return $distance <= $maxDistance;
            })
            ->map(function ($zone) use ($latitude, $longitude) {
                $zone->distance = self::calculateDistance(
                    $zone->center_latitude,
                    $zone->center_longitude,
                    $latitude,
                    $longitude
                );
                return $zone;
            })
            ->sortBy('distance');

        return $zones->values()->toArray();
    }

    /**
     * Validate coordinates
     */
    public static function validateCoordinates(float $latitude, float $longitude): bool
    {
        return $latitude >= -90 && $latitude <= 90 && $longitude >= -180 && $longitude <= 180;
    }

    /**
     * Calculate estimated delivery time for a product
     *
     * Formula: Estimated Time = Base Preparation Time + (Distance × delivery_time_per_km) + Buffer Time
     *
     * @param int $productId Product ID
     * @param float $userLat User latitude
     * @param float $userLon User longitude
     * @param float $storeLat Store latitude
     * @param float $storeLon Store longitude
     * @return array Returns an array with estimated time and details
     */
    public static function calculateEstimatedDeliveryTime(int $productId, float $userLat, float $userLon, float $storeLat, float $storeLon): array
    {
        // Validate coordinates
        if (!self::validateCoordinates($userLat, $userLon) || !self::validateCoordinates($storeLat, $storeLon)) {
            return [
                'success' => false,
                'message' => 'Invalid coordinates',
                'estimated_time' => null,
                'details' => null
            ];
        }

        // Get product
        $product = \App\Models\Product::find($productId);
        if (!$product) {
            return [
                'success' => false,
                'message' => 'Product not found',
                'estimated_time' => null,
                'details' => null
            ];
        }

        // Get base preparation time
        $basePrepTime = $product->base_prep_time ?? 0;

        // Calculate distance between user and store
        $distance = self::calculateDistance($userLat, $userLon, $storeLat, $storeLon);

        // Get zone information at user's location
        $zoneInfo = self::getZonesAtPoint($userLat, $userLon);

        // Check if delivery is available at user's location
        if (!$zoneInfo['exists']) {
            return [
                'success' => false,
                'message' => 'Delivery not available at this location',
                'estimated_time' => null,
                'details' => null
            ];
        }

        // Get delivery time per km and buffer time
        $deliveryTimePerKm = $zoneInfo['delivery_time_per_km'] ?? 0;
        $bufferTime = $zoneInfo['buffer_time'] ?? 0;

        // Calculate estimated time (in minutes)
        $estimatedTime = $basePrepTime + ($distance * $deliveryTimePerKm) + $bufferTime;

        // Round to nearest minute
        $estimatedTime = ceil($estimatedTime);

        return [
            'success' => true,
            'message' => 'Estimated delivery time calculated successfully',
            'estimated_time' => $estimatedTime,
            'details' => [
                'base_prep_time' => $basePrepTime,
                'distance_km' => round($distance, 2),
                'delivery_time_per_km' => $deliveryTimePerKm,
                'buffer_time' => $bufferTime,
                'zone_id' => $zoneInfo['zone_id'],
                'zone_name' => $zoneInfo['zone']
            ]
        ];
    }

    /**
     * Get delivery zones formatted for select options
     */
    public static function getZonesForSelect(): array
    {
        return DeliveryZone::where('status', 'active')
            ->select('id', 'name')
            ->orderBy('name')
            ->get()
            ->map(function ($zone) {
                return [
                    'id' => $zone->id,
                    'value' => $zone->id,
                    'text' => $zone->name,
                ];
            })
            ->toArray();
    }

    /**
     * Check if multiple points are within any delivery zone
     */
    public static function checkMultiplePoints(array $coordinates): array
    {
        $results = [];

        foreach ($coordinates as $index => $coord) {
            if (!isset($coord['latitude']) || !isset($coord['longitude'])) {
                continue;
            }

            $results[$index] = self::getZonesAtPoint(
                $coord['latitude'],
                $coord['longitude']
            );
        }

        return $results;
    }

    /**
     * Get zones coverage statistics
     */
    public static function getCoverageStats(): array
    {
        $totalZones = DeliveryZone::count();
        $activeZones = DeliveryZone::where('status', 'active')->count();
        $inactiveZones = DeliveryZone::where('status', 'inactive')->count();

        $averageRadius = DeliveryZone::where('status', 'active')
            ->avg('radius_km');

        return [
            'total_zones' => $totalZones,
            'active_zones' => $activeZones,
            'inactive_zones' => $inactiveZones,
            'average_radius_km' => round($averageRadius, 2),
        ];
    }

    /**
     * Check if a zone would overlap with existing zones
     */
    public static function checkZoneOverlap(DeliveryZone $zone, ?int $excludeId = null): array
    {
        $existingZones = DeliveryZone::where('status', 'active')
            ->when($excludeId, function ($query) use ($excludeId) {
                return $query->where('id', '!=', $excludeId);
            })
            ->get();

        $overlappingZones = [];

        foreach ($existingZones as $existingZone) {
            // Skip if it's the same zone (for updates)
            if ($zone->id && $zone->id === $existingZone->id) {
                continue;
            }

            // Check if zones overlap
            if (self::doZonesOverlap($zone, $existingZone)) {
                $distance = self::calculateDistance(
                    $zone->center_latitude,
                    $zone->center_longitude,
                    $existingZone->center_latitude,
                    $existingZone->center_longitude
                );

                $combinedRadius = $zone->radius_km + $existingZone->radius_km;
                $overlapPercentage = round((($combinedRadius - $distance) / $combinedRadius) * 100, 2);

                $overlappingZones[] = [
                    'zone' => $existingZone,
                    'distance_km' => round($distance, 2),
                    'overlap_percentage' => $overlapPercentage
                ];
            }
        }

        return [
            'has_overlap' => !empty($overlappingZones),
            'overlapping_zones' => $overlappingZones,
            'overlap_count' => count($overlappingZones)
        ];
    }

    /**
     * Check if two zones overlap
     */
    public static function doZonesOverlap(DeliveryZone $zone1, DeliveryZone $zone2): bool
    {
        // If both zones have polygons, check for polygon overlap
        if (!empty($zone1->boundary_json) && !empty($zone2->boundary_json)) {
            return self::doPolygonsOverlap($zone1, $zone2);
        }

        // If one or both zones are circular, check distance vs combined radius
        $distance = self::calculateDistance(
            $zone1->center_latitude,
            $zone1->center_longitude,
            $zone2->center_latitude,
            $zone2->center_longitude
        );

        $combinedRadius = $zone1->radius_km + $zone2->radius_km;

        return $distance < $combinedRadius;
    }

    /**
     * Check if two polygon zones overlap (simplified approach)
     */
    public static function doPolygonsOverlap(DeliveryZone $zone1, DeliveryZone $zone2): bool
    {
        // Get polygon coordinates
        $polygon1 = $zone1->boundary_json;
        $polygon2 = $zone2->boundary_json;

        if (isset($polygon1['coordinates'][0])) {
            $coordinates1 = $polygon1['coordinates'][0];
        } else {
            $coordinates1 = $polygon1;
        }

        if (isset($polygon2['coordinates'][0])) {
            $coordinates2 = $polygon2['coordinates'][0];
        } else {
            $coordinates2 = $polygon2;
        }
        if (is_string($coordinates1)) {
            $coordinates1 = json_decode($coordinates1, true);
        }
        if (is_string($coordinates2)) {
            $coordinates2 = json_decode($coordinates2, true);
        }
        // Check if any point of polygon1 is inside polygon2
        foreach ($coordinates1 as $point) {
            $lat = $point['lat'] ?? $point['latitude'] ?? $point[1] ?? 0;
            $lng = $point['lng'] ?? $point['longitude'] ?? $point[0] ?? 0;

            if (self::isPointInPolygon($zone2, $lat, $lng)) {
                return true;
            }
        }

        // Check if any point of polygon2 is inside polygon1
        foreach ($coordinates2 as $point) {
            $lat = $point['lat'] ?? $point['latitude'] ?? $point[1] ?? 0;
            $lng = $point['lng'] ?? $point['longitude'] ?? $point[0] ?? 0;

            if (self::isPointInPolygon($zone1, $lat, $lng)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if store can deliver to given location
     */
    public static function canStoreDeliverToLocation(Store $store, float $userLat, float $userLng): bool
    {
        // Check if store has delivery zones
        $deliveryZones = $store->zones;
        if ($deliveryZones->isEmpty()) {
            // No delivery zones defined, cannot deliver
            return false;
        }

        // Check if user location is within any delivery zone (polygon or radius)
        foreach ($deliveryZones as $zone) {
            if (self::containsPoint($zone, $userLat, $userLng)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check delivery availability and remove unavailable items
     */
    public static function checkDeliveryAvailability(Cart $cart, float $latitude = null, float $longitude = null): array
    {
        $removedItems = [];
        $reassignedItems = [];
        $itemsToRemove = [];

        // Check each cart item
        foreach ($cart->items as $item) {
            $store = $item->store;
            $product = $item->product;
            $variant = $item->variant;

            $isAvailable = true;
            $unavailabilityReason = '';

            // Check if store is offline
            if ($store->isOffline()) {
                $isAvailable = false;
                $unavailabilityReason = 'Store is offline';
            }

            // Check product status and verification status
            if ($isAvailable && (!$product->status || $product->verification_status !== ProductVarificationStatusEnum::APPROVED())) {
                $isAvailable = false;
                $unavailabilityReason = 'Product is not active or not approved';
            }

            // Check stock availability
            if ($isAvailable) {
                $storeVariant = $variant->storeProductVariants->where('store_id', $store->id)->first();
                if (!$storeVariant || $storeVariant->stock < $item->quantity) {
                    $isAvailable = false;
                    $unavailabilityReason = 'Insufficient stock';
                }
            }

            // Check delivery availability if coordinates provided
            if ($isAvailable && $latitude && $longitude) {
                // Check if store delivers to user's location
                $canDeliver = self::canStoreDeliverToLocation($store, $latitude, $longitude);
                if (!$canDeliver) {
                    $isAvailable = false;
                    $unavailabilityReason = 'Not deliverable to location';
                }
            }
            // If not available (due to stock, offline, product status or delivery),
            // try to find another store of the same seller at the current location
            if (!$isAvailable && $latitude && $longitude) {
                try {
                    // Find alternative stores for the same seller (excluding current store)
                    $alternativeStores = Store::where('seller_id', $store->seller_id)
                        ->where('id', '!=', $store->id)
                        ->get();

                    foreach ($alternativeStores as $altStore) {
                        // Must be online
                        if ($altStore->isOffline()) {
                            continue;
                        }
                        // Must be able to deliver to the new location
                        if (!self::canStoreDeliverToLocation($altStore, $latitude, $longitude)) {
                            continue;
                        }
                        // Must have the same variant with enough stock
                        $altStoreVariant = $variant->storeProductVariants->where('store_id', $altStore->id)->first();
                        if (!$altStoreVariant || $altStoreVariant->stock < $item->quantity) {
                            continue;
                        }

                        // Reassign the cart item to this store
                        $oldStoreId = $item->store_id;
                        $item->store_id = $altStore->id;
                        $item->save();

                        $reassignedItems[] = [
                            'product_name' => $product->title,
                            'main_image' => $product->main_image,
                            'variant_name' => $variant->title,
                            'from_store_name' => $store->name,
                            'to_store_name' => $altStore->name,
                            'quantity' => $item->quantity,
                            'reason' => $unavailabilityReason,
                        ];

                        // Mark as available after reassignment and stop searching
                        $isAvailable = true;
                        $unavailabilityReason = '';
                        break;
                    }
                } catch (\Throwable $e) {
                    // In case of any unexpected issue during reassignment, fallback to removal
                }
            }

            if (!$isAvailable) {
                $removedItems[] = [
                    'product_name' => $product->title,
                    'main_image' => $product->main_image,
                    'variant_name' => $variant->title,
                    'store_name' => $store->name,
                    'quantity' => $item->quantity,
                    'reason' => $unavailabilityReason
                ];
                $itemsToRemove[] = $item->id;
            }
        }

        // Remove items that are not available
        if (!empty($itemsToRemove)) {
            CartItem::whereIn('id', $itemsToRemove)->delete();
        }

        return [
            'removed_items' => $removedItems,
            'items_to_remove' => $itemsToRemove,
            // New optional key to inform about reassigned items
            'reassigned_items' => $reassignedItems,
        ];
    }

    /**
     * Check if location is within delivery zone
     */
    public static function isLocationInZone(float $userLat, float $userLng, $zone): bool
    {
        if (isset($zone->center_latitude) && isset($zone->center_longitude) && isset($zone->radius)) {
            $distance = self::calculateDistance(
                $zone->center_latitude,
                $zone->center_longitude,
                $userLat,
                $userLng
            );
            return $distance <= $zone->radius;
        }

        return true;
    }

    /**
     * Calculate the optimal delivery route and total distance
     *
     * @param float $customerLat Customer latitude
     * @param float $customerLng Customer longitude
     * @param array $storeIds Array of store IDs in the cart
     * @return array Route information including total distance and ordered stores
     */
    public static function calculateDeliveryRoute(float $customerLat, float $customerLng, array $storeIds, $order = null): array
    {
        if (empty($storeIds)) {
            return [
                'total_distance' => 0,
                'route' => [],
                'route_details' => []
            ];
        }

        $stores = self::getStoresForRoute($customerLat, $customerLng, $storeIds);

        if ($stores->isEmpty()) {
            return [
                'total_distance' => 0,
                'route' => [],
                'route_details' => []
            ];
        }

        $route = self::buildGreedyRoute($stores);
        $storeCollectionStatus = self::computeCollectionStatus($order, $storeIds);

        [$totalDistance, $routeDetails] = self::buildRouteDetailsAndDistance(
            $route,
            $customerLat,
            $customerLng,
            $order,
            $storeCollectionStatus
        );

        return [
            'total_distance' => round($totalDistance, 2),
            'route' => collect($route)->pluck('id')->toArray(),
            'route_details' => $routeDetails
        ];
    }

    /**
     * Fetch stores for the route ordered by distance from the customer.
     */
    private static function getStoresForRoute(float $customerLat, float $customerLng, array $storeIds): \Illuminate\Support\Collection
    {
        return Store::whereIn('id', $storeIds)
            ->select([
                'id', 'name', 'latitude', 'longitude', 'address', 'city', 'landmark', 'state', 'zipcode', 'country', 'country_code',
                DB::raw("(6371 * acos( cos(radians($customerLat)) * cos(radians(latitude)) * cos(radians(longitude) - radians($customerLng)) + sin(radians($customerLat)) * sin(radians(latitude)) )) AS distance_from_customer")
            ])
            ->orderBy('distance_from_customer', 'asc')
            ->get();
    }

    /**
     * Build a nearest-neighbor greedy route through the provided stores.
     * Returns an ordered array of store models.
     */
    private static function buildGreedyRoute(\Illuminate\Support\Collection $stores): array
    {
        $nearestStore = $stores->first();
        $route = [$nearestStore];
        $remainingStores = $stores->where('id', '!=', $nearestStore->id)->values();

        $currentLat = $nearestStore->latitude;
        $currentLng = $nearestStore->longitude;

        while ($remainingStores->isNotEmpty()) {
            $remainingStores = $remainingStores->map(function ($store) use ($currentLat, $currentLng) {
                $store->current_distance = self::calculateDistance(
                    $currentLat,
                    $currentLng,
                    $store->latitude,
                    $store->longitude
                );
                return $store;
            })->sortBy('current_distance')->values();

            $nextStore = $remainingStores->first();
            $route[] = $nextStore;
            $currentLat = $nextStore->latitude;
            $currentLng = $nextStore->longitude;
            $remainingStores = $remainingStores->where('id', '!=', $nextStore->id)->values();
        }

        return $route;
    }

    /**
     * Pre-compute collection status for stores in an order to avoid N+1 queries.
     */
    private static function computeCollectionStatus($order, array $storeIds): array
    {
        $storeCollectionStatus = [];
        if ($order && ($order->id ?? null)) {
            try {
                $totals = $order->items()
                    ->select('store_id', DB::raw('count(*) as total'))
                    ->whereIn('store_id', $storeIds)
                    ->groupBy('store_id')
                    ->pluck('total', 'store_id')
                    ->toArray();

                $collected = $order->items()
                    ->select('store_id', DB::raw('count(*) as collected'))
                    ->whereIn('store_id', $storeIds)
                    ->where('status', OrderItemStatusEnum::COLLECTED())
                    ->groupBy('store_id')
                    ->pluck('collected', 'store_id')
                    ->toArray();

                foreach ($totals as $sid => $total) {
                    $col = $collected[$sid] ?? 0;
                    $storeCollectionStatus[$sid] = ($total > 0) && ($col === $total);
                }
            } catch (\Throwable $e) {
                $storeCollectionStatus = [];
            }
        }

        return $storeCollectionStatus;
    }

    /**
     * Build route details and compute total distance for the full trip.
     * Returns array [totalDistance, routeDetails].
     */
    private static function buildRouteDetailsAndDistance(array $route, float $customerLat, float $customerLng, $order, array $storeCollectionStatus): array
    {
        $totalDistance = 0;
        $routeDetails = [];

        $isStoreCollected = static function ($storeId) use ($storeCollectionStatus) {
            return (bool)($storeCollectionStatus[$storeId] ?? false);
        };

        if (count($route) > 1) {
            $totalDistance += $route[0]->distance_from_customer;

            $routeDetails[] = [
                'store_id' => $route[0]->id,
                'store_name' => $route[0]->name,
                'distance_from_customer' => round($route[0]->distance_from_customer, 2),
                'address' => $route[0]->address,
                'city' => $route[0]->city,
                'landmark' => $route[0]->landmark,
                'state' => $route[0]->state,
                'zipcode' => $route[0]->zipcode,
                'country' => $route[0]->country,
                'country_code' => $route[0]->country_code,
                'latitude' => (float)$route[0]->latitude,
                'longitude' => (float)$route[0]->longitude,
                'is_collected' => $isStoreCollected($route[0]->id)
            ];

            for ($i = 0; $i < count($route) - 1; $i++) {
                $distance = self::calculateDistance(
                    $route[$i]->latitude,
                    $route[$i]->longitude,
                    $route[$i + 1]->latitude,
                    $route[$i + 1]->longitude
                );
                $totalDistance += $distance;

                $routeDetails[] = [
                    'store_id' => $route[$i + 1]->id,
                    'store_name' => $route[$i + 1]->name,
                    'distance_from_previous' => round($distance, 2),
                    'address' => $route[$i + 1]->address,
                    'city' => $route[$i + 1]->city,
                    'landmark' => $route[$i + 1]->landmark,
                    'state' => $route[$i + 1]->state,
                    'zipcode' => $route[$i + 1]->zipcode,
                    'country' => $route[$i + 1]->country,
                    'country_code' => $route[$i + 1]->country_code,
                    'latitude' => (float)$route[$i + 1]->latitude,
                    'longitude' => (float)$route[$i + 1]->longitude,
                    'is_collected' => $isStoreCollected($route[$i + 1]->id)
                ];
            }

            $lastStore = $route[count($route) - 1];
            $lastLegDistance = self::calculateDistance(
                $lastStore->latitude,
                $lastStore->longitude,
                $customerLat,
                $customerLng
            );
            $totalDistance += $lastLegDistance;

            $routeDetails[] = [
                'store_id' => null,
                'store_name' => 'Customer Location',
                'distance_from_previous' => round($lastLegDistance, 2),
                'address' => $order ? $order->shipping_address_1 : 'Customer Address',
                'city' => $order ? $order->shipping_city : '',
                'landmark' => $order ? $order->shipping_landmark : '',
                'state' => $order ? $order->shipping_state : '',
                'zipcode' => $order ? $order->shipping_zipcode : '',
                'country' => $order ? $order->shipping_country : '',
                'country_code' => $order ? $order->shipping_country_code : '',
                'latitude' => $customerLat,
                'longitude' => $customerLng,
                'is_collected' => false
            ];
            return [$totalDistance, $routeDetails];

        }
        $singleStoreDistance = $route[0]->distance_from_customer * 2; // Round trip
        $totalDistance = $singleStoreDistance;

        $routeDetails[] = [
            'store_id' => $route[0]->id,
            'store_name' => $route[0]->name,
            'distance_from_customer' => round($route[0]->distance_from_customer, 2),
            'address' => $route[0]->address,
            'city' => $route[0]->city,
            'landmark' => $route[0]->landmark,
            'state' => $route[0]->state,
            'zipcode' => $route[0]->zipcode,
            'country' => $route[0]->country,
            'country_code' => $route[0]->country_code,
            'latitude' => (float)$route[0]->latitude,
            'longitude' => (float)$route[0]->longitude,
            'is_collected' => $isStoreCollected($route[0]->id)
        ];

        $routeDetails[] = [
            'store_id' => null,
            'store_name' => 'Customer Location',
            'distance_from_previous' => round($route[0]->distance_from_customer, 2),
            'address' => $order ? $order->shipping_address_1 : 'Customer Address',
            'city' => $order ? $order->shipping_city : '',
            'landmark' => $order ? $order->shipping_landmark : '',
            'state' => $order ? $order->shipping_state : '',
            'zipcode' => $order ? $order->shipping_zip : '',
            'country' => $order ? $order->shipping_country : '',
            'country_code' => $order ? $order->shipping_country_code : '',
            'latitude' => $customerLat,
            'longitude' => $customerLng,
            'is_collected' => false
        ];

        return [$totalDistance, $routeDetails];
    }

    /**
     * Get stores within map viewport (bounding box)
     */
    public static function getStoresByBounds(
        float $neLat,
        float $neLng,
        float $swLat,
        float $swLng,
        float $user_lat = null,
        float $user_lng = null,
        int   $limit = 200
    ): array
    {

        // Validate coordinates
        if (
            !DeliveryZoneService::validateCoordinates($neLat, $neLng) ||
            !DeliveryZoneService::validateCoordinates($swLat, $swLng)
        ) {
            return [
                'success' => false,
                'message' => __('labels.invalid_coordinates'),
                'data' => ['stores' => []]
            ];
        }

        $stores = Store::whereBetween('latitude', [$swLat, $neLat])
            ->whereBetween('longitude', [$swLng, $neLng])
            ->where('visibility_status', StoreVisibilityStatusEnum::VISIBLE())
            ->where('verification_status', StoreVerificationStatusEnum::APPROVED())
            ->with('zones')
            ->limit($limit)
            ->get();

        $stores = $stores->transform(function ($store) use ($user_lat, $user_lng) {
            $store->user_latitude = $user_lat;
            $store->user_longitude = $user_lng;
            return new StoreResource($store);
        });

        return [
            'success' => true,
            'message' => __('labels.stores_found'),
            'data' => [
                'count' => $stores->count(),
                'stores' => $stores
            ]
        ];
    }
}
