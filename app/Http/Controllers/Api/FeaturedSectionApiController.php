<?php

namespace App\Http\Controllers\Api;

use App\Enums\ActiveInactiveStatusEnum;
use App\Enums\FeaturedSection\FeaturedSectionTypeEnum;
use App\Enums\HomePageScopeEnum;
use App\Enums\Store\StoreVerificationStatusEnum;
use App\Enums\Store\StoreVisibilityStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\FeaturedSectionResource;
use App\Http\Resources\Product\ProductResource;
use App\Models\Category;
use App\Models\FeaturedSection;
use App\Models\Store;
use App\Services\DeliveryZoneService;
use App\Types\Api\ApiResponseType;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

#[Group('Featured Sections')]
class FeaturedSectionApiController extends Controller
{
    /**
     * Get featured sections with products.
     */
    #[QueryParameter('per_page', description: 'Number of items per page.', type: 'int', default: 15, example: 15)]
    #[QueryParameter('page', description: 'Page number for pagination.', type: 'int', default: 1, example: 1)]
    #[QueryParameter('section_type', description: 'Filter by section type (newly_added, top_rated, trending, best_seller, featured, on_sale, recommended).', type: 'string', example: 'featured')]
    #[QueryParameter('products_limit', description: 'Limit number of products per section.', type: 'int', default: 10, example: 20)]
    #[QueryParameter('scope_category_slug', description: 'if you pass slug then featured sections will be filtered by category', type: 'string', example: 'apple, amul')]
    #[QueryParameter('latitude', description: 'User latitude for location-based filtering.', type: 'float', example: '37.7749')]
    #[QueryParameter('longitude', description: 'User longitude for location-based filtering.', type: 'float', example: '-122.4194')]
    public function index(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'per_page' => 'sometimes|integer|min:1|max:100',
                'page' => 'sometimes|integer|min:1',
                'section_type' => 'sometimes|string|in:newly_added,top_rated,trending,best_seller,featured,on_sale,recommended',
                'products_limit' => 'sometimes|integer|min:1|max:50',
                'scope_category_slug' => 'sometimes|string',
                'latitude' => 'sometimes|required_with:longitude|numeric|between:-90,90',
                'longitude' => 'sometimes|required_with:latitude|numeric|between:-180,180',
            ]);

            $perPage = $request->input('per_page', 15);
            $productsLimit = $request->input('products_limit', 10);
            $latitude = $request->input('latitude');
            $longitude = $request->input('longitude');

            // Validate and get category if scope_category_slug is provided
            $category = null;
            if ($request->has('scope_category_slug')) {
                $categorySlug = $request->input('scope_category_slug');
                if (empty($categorySlug)) {
                    return ApiResponseType::sendJsonResponse(
                        success: false,
                        message: 'labels.category_slug_is_required',
                        data: [],
                        status: 422
                    );
                }

                $category = Category::where('slug', $categorySlug)->first();
                if (!$category) {
                    return ApiResponseType::sendJsonResponse(
                        success: false,
                        message: 'labels.category_not_found',
                        data: [],
                        status: 404
                    );
                }
            }

            $query = FeaturedSection::active()
                ->ordered()
                ->with('categories');

            // Apply scope filter
            if ($category) {
                $query = FeaturedSection::scopeByCategory($query, $category->id);
            } else {
                $query->where('scope_type', 'global');
            }

            // Filter by section type if provided
            if ($request->filled('section_type')) {
                $query->byType($request->input('section_type'));
            }

            // Paginate results
            $featuredSections = $query->paginate($perPage);

            // Check if location is provided and get zone info
            $zoneInfo = null;
            if ($latitude && $longitude) {
                $zoneInfo = DeliveryZoneService::getZonesAtPoint($latitude, $longitude);

                if (!$zoneInfo['exists']) {
                    return ApiResponseType::sendJsonResponse(
                        success: false,
                        message: 'labels.delivery_not_available_at_location',
                        data: [],
                        status: 404
                    );
                }
            }

            // Transform using FeaturedSectionResource
            $featuredSections->getCollection()->transform(function ($section) use ($productsLimit, $latitude, $longitude, $zoneInfo) {
                $resource = new FeaturedSectionResource($section);
                $additional = ['products_limit' => $productsLimit];

                // Add location data if provided
                if ($latitude && $longitude && $zoneInfo) {
                    $additional['latitude'] = $latitude;
                    $additional['longitude'] = $longitude;
                    $additional['zone_info'] = $zoneInfo;
                }

                $resource->additional($additional);
                return $resource;
            });
            return ApiResponseType::sendJsonResponse(
                success: true,
                message: 'labels.featured_sections_fetched_successfully',
                data: [
                    'current_page' => $featuredSections->currentPage(),
                    'last_page' => $featuredSections->lastPage(),
                    'per_page' => $featuredSections->perPage(),
                    'total' => $featuredSections->total(),
                    'data' => $featuredSections->items(),
                ]
            );

        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.validation_failed',
                data: $e->errors(),
                status: 422
            );
        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.something_went_wrong',
                data: [],
                status: 500
            );
        }
    }

    /**
     * Get a specific featured section by slug.
     */
    #[QueryParameter('products_limit', description: 'Limit number of products.', type: 'int', default: 10, example: 20)]
    #[QueryParameter('latitude', description: 'User latitude for location-based filtering.', type: 'float', example: '37.7749')]
    #[QueryParameter('longitude', description: 'User longitude for location-based filtering.', type: 'float', example: '-122.4194')]
    public function show(Request $request, string $slug): JsonResponse
    {
        try {
            $request->validate([
                'products_limit' => 'sometimes|integer|min:1|max:50',
                'latitude' => 'sometimes|required_with:longitude|numeric|between:-90,90',
                'longitude' => 'sometimes|required_with:latitude|numeric|between:-180,180',
            ]);

            $productsLimit = $request->input('products_limit', 10);
            $latitude = $request->input('latitude');
            $longitude = $request->input('longitude');

            $featuredSection = FeaturedSection::active()
                ->with('categories')
                ->where('slug', $slug)
                ->first();

            if (!$featuredSection) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: 'labels.featured_section_not_found',
                    data: [],
                    status: 404
                );
            }

            $resource = new FeaturedSectionResource($featuredSection);
            // Add products if requested and add location data if provided
            $additional = ['products_limit' => $productsLimit];

            if ($latitude && $longitude) {
                // Get zone info at the given coordinates
                $zoneInfo = DeliveryZoneService::getZonesAtPoint($latitude, $longitude);

                if (!$zoneInfo['exists']) {
                    return ApiResponseType::sendJsonResponse(
                        success: false,
                        message: 'labels.delivery_not_available_at_location',
                        data: [],
                        status: 404
                    );
                }

                $additional['latitude'] = $latitude;
                $additional['longitude'] = $longitude;
                $additional['zone_info'] = $zoneInfo;
            }

            $resource->additional($additional);

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: 'labels.featured_section_fetched_successfully',
                data: $resource
            );

        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.validation_failed',
                data: $e->errors(),
                status: 422
            );
        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.something_went_wrong',
                data: [],
                status: 500
            );
        }
    }

    /**
     * Get products for a specific featured section.
     */
    #[QueryParameter('per_page', description: 'Number of products per page.', type: 'int', default: 15, example: 15)]
    #[QueryParameter('page', description: 'Page number for pagination.', type: 'int', default: 1, example: 1)]
    #[QueryParameter('latitude', description: 'User latitude for location-based filtering.', type: 'float', example: '37.7749')]
    #[QueryParameter('longitude', description: 'User longitude for location-based filtering.', type: 'float', example: '-122.4194')]
    #[QueryParameter('sort', description: 'Enter sort filter', type: 'string', example: 'price_asc, price_desc, relevance, avg_rated, best_seller, featured',)]
    #[QueryParameter('categories', description: 'Comma-separated list of category slugs to filter products', type: 'string', example: 'apple,samsung')]
    #[QueryParameter('brands', description: 'Comma-separated list of brand slugs to filter products', type: 'string', example: 'mobile,electronics')]
    #[QueryParameter('attribute_values', description: 'Comma-separated list of global attribute value IDs to filter products', type: 'string', example: '12,34,56')]
    public function products(Request $request, string $slug): JsonResponse
    {
        try {
            $request->validate([
                'per_page' => 'sometimes|integer|min:1|max:100',
                'page' => 'sometimes|integer|min:1',
                'sort' => 'string|nullable',
                'latitude' => 'sometimes|required_with:longitude|numeric|between:-90,90',
                'longitude' => 'sometimes|required_with:latitude|numeric|between:-180,180',
                'categories' => 'sometimes|string|nullable',
                'brands' => 'sometimes|string|nullable',
                'attribute_values' => 'sometimes|string|nullable',
            ]);

            $perPage = $request->input('per_page', 15);
            $latitude = $request->input('latitude');
            $longitude = $request->input('longitude');
            $sort = $request->input('sort');
            $categories = (!empty($request->input('categories')) && is_string($request->input('categories'))) ? explode(',', $request->input('categories')) : null;
            $brands = (!empty($request->input('brands')) && is_string($request->input('brands'))) ? explode(',', $request->input('brands')) : null;
            // Parse attribute value IDs CSV into integer array
            $attributeValues = null;
            if (!empty($request->input('attribute_values')) && is_string($request->input('attribute_values'))) {
                $attributeValues = array_values(array_unique(array_filter(array_map(function ($v) {
                    $n = (int)trim($v);
                    return $n > 0 ? $n : null;
                }, explode(',', $request->input('attribute_values'))))));
                if (empty($attributeValues)) {
                    $attributeValues = null;
                }
            }

            $featuredSection = FeaturedSection::active()
                ->where('slug', $slug)
                ->first();

            if (!$featuredSection) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: 'labels.featured_section_not_found',
                    data: []
                );
            }

            // Get products based on location if coordinates are provided
            if ($latitude && $longitude) {
                // Get zone info at the given coordinates
                $zoneInfo = DeliveryZoneService::getZonesAtPoint($latitude, $longitude);

                if (!$zoneInfo['exists']) {
                    return ApiResponseType::sendJsonResponse(
                        success: false,
                        message: 'labels.delivery_not_available_at_location',
                        data: [],
                        status: 404
                    );
                }

                // Get stores in the delivery zone
                $storeIds = Store::whereHas('zones', function ($q) use ($zoneInfo) {
                    $q->where('delivery_zones.id', $zoneInfo['zone_id']);
                })
                    ->where('verification_status', StoreVerificationStatusEnum::APPROVED())
                    ->where('visibility_status', StoreVisibilityStatusEnum::VISIBLE())
                    ->pluck('id')
                    ->toArray();

                // Build base products query with sorting aware of store IDs
                $productsQuery = $featuredSection->getProductsQuery($sort, $storeIds);

                // Filter products by stores in the zone
                $productsQuery->with([
                    'variants' => function ($q) use ($storeIds) {
                        $q->whereHas('storeProductVariants', function ($sq) use ($storeIds) {
                            $sq->whereIn('store_id', $storeIds);
                        });
                    },
                    'variants.storeProductVariants' => function ($q) use ($storeIds) {
                        $q->whereIn('store_id', $storeIds);
                    },
                    'variants.storeProductVariants.store',
                ])
                    ->whereHas('variants.storeProductVariants', function ($q) use ($storeIds) {
                        $q->whereIn('store_id', $storeIds);
                    });

                // Apply additional filters
                $productsQuery->where('status', ActiveInactiveStatusEnum::ACTIVE());

                // Apply attribute value filter (global attribute value IDs) before collecting IDs
                if (!empty($attributeValues)) {
                    $productsQuery->whereHas('variantAttributes', function ($q) use ($attributeValues) {
                        $q->whereIn('global_attribute_value_id', $attributeValues);
                    });
                }

                // Collect unique category and brand IDs BEFORE applying category/brand slug filters
                $categoryIds = (clone $productsQuery)->distinct()->pluck('category_id')->filter()->unique()->map(fn($id) => (int) $id)->values()->toArray();

                // Apply category filters if provided (by slug)
                if (!empty($categories)) {
                    $productsQuery->whereHas('category', function ($q) use ($categories) {
                        $q->whereIn('slug', $categories);
                    });
                }

                // Apply brand filters if provided (by slug)
                if (!empty($brands)) {
                    $productsQuery->whereHas('brand', function ($q) use ($brands) {
                        $q->whereIn('slug', $brands);
                    });
                }
                $brandIds = (clone $productsQuery)->distinct()->pluck('brand_id')->filter()->unique()->map(fn($id) => (int) $id)->values()->toArray();

                // Paginate products
                $products = $productsQuery->paginate($perPage);

                // Store the user's latitude and longitude in each product for delivery time calculation
                foreach ($products as $product) {
                    $product->user_latitude = $latitude;
                    $product->user_longitude = $longitude;
                    $product->zone_info = $zoneInfo;
                }
            } else {
                // Get products query based on section type and categories (without location filtering)
                $productsQuery = $featuredSection->getProductsQuery($sort);

                // Apply additional filters
                $productsQuery->where('status', ActiveInactiveStatusEnum::ACTIVE());

                // Apply attribute value filter (global attribute value IDs) before collecting IDs
                if (!empty($attributeValues)) {
                    $productsQuery->whereHas('variantAttributes', function ($q) use ($attributeValues) {
                        $q->whereIn('global_attribute_value_id', $attributeValues);
                    });
                }

                // Collect unique category and brand IDs BEFORE applying category/brand slug filters
                $categoryIds = (clone $productsQuery)->distinct()->pluck('category_id')->filter()->unique()->map(fn($id) => (int) $id)->values()->toArray();
                $brandIds = (clone $productsQuery)->distinct()->pluck('brand_id')->filter()->unique()->map(fn($id) => (int) $id)->values()->toArray();

                // Apply category filters if provided (by slug)
                if (!empty($categories)) {
                    $productsQuery->whereHas('category', function ($q) use ($categories) {
                        $q->whereIn('slug', $categories);
                    });
                }

                // Apply brand filters if provided (by slug)
                if (!empty($brands)) {
                    $productsQuery->whereHas('brand', function ($q) use ($brands) {
                        $q->whereIn('slug', $brands);
                    });
                }

                // Paginate products
                $products = $productsQuery->paginate($perPage);
            }

            // Transform using ProductResource
            $products->getCollection()->transform(function ($product) {
                return new ProductResource($product);
            });

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: 'labels.featured_section_products_fetched_successfully',
                data: [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'category_ids' => $categoryIds,
                    'brand_ids' => $brandIds,
                    'data' => $products->items(),
                ]
            );

        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.validation_failed',
                data: $e->errors(),
                status: 422
            );
        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.something_went_wrong',
                data: [],
                status: 500
            );
        }
    }

    /**
     * Get featured section types.
     * Returns available section types for filtering.
     */
    public function types(): JsonResponse
    {
        try {
            return ApiResponseType::sendJsonResponse(
                success: true,
                message: 'labels.featured_section_types_fetched_successfully',
                data: FeaturedSectionTypeEnum::values()
            );

        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.something_went_wrong',
                data: [],
                status: 500
            );
        }
    }
}
