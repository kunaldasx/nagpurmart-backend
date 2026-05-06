<?php

namespace App\Http\Controllers\Api;

use App\Enums\Banner\BannerPositionEnum;
use App\Enums\Banner\BannerVisibilityStatusEnum;
use App\Enums\Banner\BannerTypeEnum;
use App\Enums\HomePageScopeEnum;
use App\Enums\Product\ProductStatusEnum;
use App\Enums\Product\ProductVarificationStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\BannerResource;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Services\DeliveryZoneService;
use App\Types\Api\ApiResponseType;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

#[Group('Banners')]
class BannerApiController extends Controller
{
    /**
     * Display a listing of the banners.
     */
    #[QueryParameter('position', description: 'position filter', type: 'string', example: "top, carousel")]
    #[QueryParameter('scope_category_slug', description: 'if you pass slug then banners will be filtered by category', type: 'string', example: "apple, amul")]
    #[QueryParameter('latitude', description: 'Latitude of the user location for zone-wise availability', type: 'float', example: 23.11684540)]
    #[QueryParameter('longitude', description: 'Longitude of the user location for zone-wise availability', type: 'float', example: 70.02805670)]
    public function index(Request $request): JsonResponse
    {
        // Validate request parameters
        $validationResult = $this->validateRequest($request);
        if ($validationResult) {
            return $validationResult;
        }

        // Build query
        $query = $this->buildQuery($request);

        // Get paginated results
        $perPage = $request->input('per_page', 15);
        $banners = $query->orderBy('position')->orderBy('display_order')->paginate($perPage);

        // Transform and format response
        $response = $this->formatResponse($banners);

        return ApiResponseType::sendJsonResponse(success: true, message: 'labels.banner_fetched_successfully', data: $response);
    }

    /**
     * Validate request parameters
     */
    private function validateRequest(Request $request): ?JsonResponse
    {
        // Validate position parameter
        if ($request->has('position')) {
            $position = $request->input('position');
            if (!in_array($position, BannerPositionEnum::values())) {
                return ApiResponseType::sendJsonResponse(success: false, message: __('labels.invalid_position_entered'), data: []);
            }
        }

        // Validate scope parameter
        if ($request->has('scope_category_slug')) {
            $categorySlug = $request->input('scope_category_slug');
            if (empty($categorySlug)) {
                return ApiResponseType::sendJsonResponse(success: false, message: __('labels.category_slug_is_required'), data: []);
            }

            $category = Category::where('slug', $categorySlug)->first();
            if (!$category) {
                return ApiResponseType::sendJsonResponse(success: false, message: __('labels.category_not_found'), data: []);
            }
        }

        return null;
    }

    /**
     * Build the query based on request parameters
     */
    private function buildQuery(Request $request)
    {
        $query = Banner::query();

        // Apply position filter
        if ($request->has('position')) {
            $query->where('position', $request->input('position'));
        }

        // Apply scope filter
        if ($request->has('scope_category_slug')) {
            $categorySlug = $request->input('scope_category_slug');
            $category = Category::where('slug', $categorySlug)->first();
            $query = Banner::scopeByCategory($query, $category->id);
        } else {
            $query->where('scope_type', HomePageScopeEnum::GLOBAL());
        }

        // Apply visibility filter
        $query->where('visibility_status', BannerVisibilityStatusEnum::PUBLISHED());

        // Zone-awareness and product availability filtering
        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');
        $useZoneFilter = !is_null($latitude) && !is_null($longitude);
        $storeIds = [];
        if ($useZoneFilter) {
            $zoneInfo = DeliveryZoneService::getZonesAtPoint((float)$latitude, (float)$longitude);
            $storeIds = Product::getStoreIdsInZone($zoneInfo);
        }

        // Filter banners based on type and available products
        $query->where(function ($q) use ($useZoneFilter, $storeIds) {
            // Always allow custom banners
            $q->where('type', BannerTypeEnum::CUSTOM());

            // PRODUCT type: only if the specific product is available
            $q->orWhere(function ($q) use ($useZoneFilter, $storeIds) {
                $q->where('type', BannerTypeEnum::PRODUCT());
                $q->whereExists(
                    Product::query()
                        ->selectRaw('1')
                        ->whereColumn('products.id', 'banners.product_id')
                        ->where('verification_status', ProductVarificationStatusEnum::APPROVED())
                        ->where('status', ProductStatusEnum::ACTIVE())
                        ->when($useZoneFilter, function ($pq) use ($storeIds) {
                            if (empty($storeIds)) {
                                // Force no match when no stores in zone
                                $pq->whereRaw('1 = 0');
                            } else {
                                $pq->whereHas('variants.storeProductVariants', function ($sq) use ($storeIds) {
                                    $sq->whereIn('store_id', $storeIds);
                                });
                            }
                        })
                );
            });

            // CATEGORY type: only if any product exists in that category or its direct subcategories
            $q->orWhere(function ($q) use ($useZoneFilter, $storeIds) {
                $q->where('type', BannerTypeEnum::CATEGORY());
                $q->whereExists(
                    Product::query()
                        ->selectRaw('1')
                        // Match products that belong to the banner's category OR any of its immediate children
                        ->where(function ($pq) {
                            $pq->whereColumn('products.category_id', 'banners.category_id')
                                ->orWhereIn('products.category_id', function ($sq) {
                                    $sq->select('id')
                                        ->from('categories')
                                        ->whereColumn('parent_id', 'banners.category_id');
                                });
                        })
                        ->where('verification_status', ProductVarificationStatusEnum::APPROVED())
                        ->where('status', ProductStatusEnum::ACTIVE())
                        ->when($useZoneFilter, function ($pq) use ($storeIds) {
                            if (empty($storeIds)) {
                                $pq->whereRaw('1 = 0');
                            } else {
                                $pq->whereHas('variants.storeProductVariants', function ($sq) use ($storeIds) {
                                    $sq->whereIn('store_id', $storeIds);
                                });
                            }
                        })
                );
            });

            // BRAND type: only if any product exists for that brand
            $q->orWhere(function ($q) use ($useZoneFilter, $storeIds) {
                $q->where('type', BannerTypeEnum::BRAND());
                $q->whereExists(
                    Product::query()
                        ->selectRaw('1')
                        ->whereColumn('products.brand_id', 'banners.brand_id')
                        ->where('verification_status', ProductVarificationStatusEnum::APPROVED())
                        ->where('status', ProductStatusEnum::ACTIVE())
                        ->when($useZoneFilter, function ($pq) use ($storeIds) {
                            if (empty($storeIds)) {
                                $pq->whereRaw('1 = 0');
                            } else {
                                $pq->whereHas('variants.storeProductVariants', function ($sq) use ($storeIds) {
                                    $sq->whereIn('store_id', $storeIds);
                                });
                            }
                        })
                );
            });
        });

        return $query;
    }

    /**
     * Format the response data
     */
    private function formatResponse($banners): array
    {
        // Transform banners to resources
        $banners->getCollection()->transform(function ($banner) {
            return new BannerResource($banner);
        });

        // Group banners by position
        $grouped = $this->groupBannersByPosition($banners->getCollection());

        return [
            'current_page' => $banners->currentPage(),
            'last_page' => $banners->lastPage(),
            'per_page' => $banners->perPage(),
            'total' => $banners->total(),
            'data' => $grouped,
        ];
    }

    /**
     * Group banners by position with all positions initialized
     */
    private function groupBannersByPosition($banners)
    {
        // Group banners by position
        $grouped = $banners->groupBy('position')->map(function ($items) {
            return $items->values();
        });

        // Initialize all positions from enum with empty arrays
        $allPositions = collect();
        foreach (BannerPositionEnum::cases() as $position) {
            $allPositions[$position->value] = collect();
        }

        // Merge with actual grouped data
        return $allPositions->merge($grouped);
    }
}
