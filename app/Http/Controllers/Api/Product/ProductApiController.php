<?php

namespace App\Http\Controllers\Api\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\GetProductsByLocationRequest;
use App\Http\Resources\Product\ProductListResource;
use App\Http\Resources\Product\ProductResource;
use App\Models\Product;
use App\Models\Category;
use App\Models\Store;
use App\Types\Api\ApiResponseType;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nette\Schema\ValidationException;

#[Group('Products')]
class ProductApiController extends Controller
{

    /**
     * Get products Based on location.
     */
    #[QueryParameter('page', description: 'Page number for pagination.', type: 'int', default: 1, example: 1)]
    #[QueryParameter('per_page', description: 'Products Per Page', type: 'int', default: 15, example: 15)]
    #[QueryParameter('latitude', description: 'Latitude of the user location', required: true, type: 'float', example: 23.2420)]
    #[QueryParameter('longitude', description: 'Longitude of the user location', required: true, type: 'float', example: 69.6669)]
    #[QueryParameter('categories', description: 'Comma-separated list of category slugs to filter products', type: 'string', example: 'apple,samsung')]
    #[QueryParameter('brands', description: 'Comma-separated list of brand slugs to filter products', type: 'string', example: 'mobile,electronics')]
    #[QueryParameter('exclude_product', description: 'Comma-separated list of product slugs to exclude from response', type: 'string', example: 'iphone-14,iphone-14-pro')]
    #[QueryParameter('sort', description: 'Enter sort filter', type: 'string', example: 'price_asc, price_desc, relevance, avg_rated, best_seller, featured',)]
    #[QueryParameter('store', description: 'Enter Store Slug to filter products', type: 'string', example: 'my-store')]
    #[QueryParameter('search', description: 'Search term to filter products by name, description, category name, or tags', type: 'string', example: 'smartphone')]
    #[QueryParameter('include_child_categories', description: 'Include products from child categories when filtering by categories', type: 'boolean', default: false, example: true)]
    #[QueryParameter('attribute_values', description: 'Comma-separated list of global attribute value IDs to filter products', type: 'string', example: '12,34,56')]
    public function index(GetProductsByLocationRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $filter = $this->buildFilters($validated);

            $products = Product::getProductsByLocation(
                latitude: $validated['latitude'],
                longitude: $validated['longitude'],
                perPage: $validated['per_page'] ?? 15,
                filter: $filter
            );

            if ($products->isEmpty()) {
                return ApiResponseType::sendJsonResponse(
                    success: true,
                    message: 'labels.products_not_found',
                    data: [
                        'current_page' => $products->currentPage(),
                        'last_page'    => $products->lastPage(),
                        'per_page'     => $products->perPage(),
                        'total'        => $products->total(),
                        'keywords'     => $products->related_keywords ?? [],
                        'category_ids' => $products->category_ids ?? [],
                        'brand_ids'    => $products->brand_ids ?? [],
                        'data'         => [],
                    ]
                );
            }

            $products->getCollection()
                ->transform(fn($product) => new ProductListResource($product));

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: 'labels.products_fetched_successfully',
                data: [
                    'current_page' => $products->currentPage(),
                    'last_page'    => $products->lastPage(),
                    'per_page'     => $products->perPage(),
                    'total'        => $products->total(),
                    'keywords'     => $products->related_keywords ?? [],
                    'category_ids' => $products->category_ids ?? [],
                    'brand_ids'    => $products->brand_ids ?? [],
                    'data'         => $products->items(),
                ]
            );

        } catch (ValidationException $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.validation_error',
                data: $e->errors()
            );
        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.error_fetching_products',
                data: $e->getMessage(),
            );
        }
    }

    /**
     * Build filters cleanly
     */
    private function buildFilters(array $validated): array
    {
        $filter = [];

        $filter['categories'] = $this->explodeIfString($validated['categories'] ?? null);
        $filter['brands']     = $this->explodeIfString($validated['brands'] ?? null);
        $filter['sort']       = $validated['sort'] ?? null;
        $filter['search']     = $validated['search'] ?? null;
        $filter['store']      = $validated['store'] ?? null;
        $filter['include_child_categories'] = $validated['include_child_categories'] ?? null;
        // Attribute value filter (expects CSV of IDs). Convert to int array if provided
        if (!empty($validated['attribute_values'])) {
            $filter['attribute_values'] = $this->explodeCsvToInts($validated['attribute_values']);
        }

        $excludeSlugs = $this->explodeIfString($validated['exclude_product'] ?? null);

        if (!empty($excludeSlugs)) {
            $filter['exclude_product'] = $excludeSlugs;

            // Auto-infer categories if not provided
            if (empty($filter['categories'])) {
                $filter['categories'] = Category::whereIn('id',
                    Product::whereIn('slug', $excludeSlugs)
                        ->pluck('category_id')
                        ->filter()
                        ->unique()
                )
                    ->pluck('slug')
                    ->filter()
                    ->unique()
                    ->toArray();
            }
        }

        return array_filter($filter, fn($value) => !is_null($value) && $value !== []);
    }

    /**
     * Convert comma-separated string to array
     */
    private function explodeIfString(?string $value): ?array
    {
        if (!$value) {
            return null;
        }

        return array_values(array_filter(array_map('trim', explode(',', $value))));
    }

    /**
     * Convert comma-separated string to array<int>
     */
    private function explodeCsvToInts(?string $value): ?array
    {
        if (!$value) {
            return null;
        }

        $parts = array_map('trim', explode(',', $value));
        $ints = [];
        foreach ($parts as $p) {
            if ($p === '' || !is_numeric($p)) {
                continue;
            }
            $n = (int)$p;
            if ($n > 0) {
                $ints[] = $n;
            }
        }
        $ints = array_values(array_unique($ints));
        return empty($ints) ? null : $ints;
    }

    /**
     * Get product Store Wise.
     */
    #[QueryParameter('per_page', description: 'Products Per Page', type: 'int', default: 15, example: 15)]
    #[QueryParameter('store_id', description: 'ID of the store to fetch products from', type: 'int', example: 1)]
    #[QueryParameter('store_slug', description: 'Slug of the store to fetch products from', type: 'string', example: 'my-store')]
    public function storeWise(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $storeId = $request->input('store_id');
        $storeSlug = $request->input('store_slug');

        // Check if at least one of store_id or store_slug is provided
        if (!$storeId && !$storeSlug) {
            return ApiResponseType::sendJsonResponse(success: false, message: __('labels.store_id_or_slug_required'), data: []);
        }

        // If store_slug is provided but not store_id, get the store_id from the slug
        if (!$storeId && $storeSlug) {
            $store = Store::where('slug', $storeSlug)->first();
            if (!$store) {
                return ApiResponseType::sendJsonResponse(success: false, message: __('labels.store_not_found_with_slug'), data: []);
            }
            $storeId = $store->id;
        }

        $query = Product::with([
            'variants' => function ($q) use ($storeId) {
                $q->whereHas('storeProductVariants', function ($sq) use ($storeId) {
                    $sq->where('store_id', $storeId);
                });
            },
            'variants.storeProductVariants' => function ($q) use ($storeId) {
                $q->where('store_id', $storeId);
            },
            'variants.attributes.attribute',
            'variants.attributes.attributeValue',
            'variantAttributes.attribute',
            'variantAttributes.attributeValue'
        ]);
        $query->whereHas('variants.storeProductVariants', function ($q) use ($storeId) {
            $q->where('store_id', $storeId);
        });
        $products = $query->orderBy('title')->paginate($perPage);
        $products->getCollection()->transform(fn($product) => new ProductListResource($product));
        $response = [
            'current_page' => $products->currentPage(),
            'last_page' => $products->lastPage(),
            'per_page' => $products->perPage(),
            'total' => $products->total(),
            'data' => $products->items(),
        ];
        return ApiResponseType::sendJsonResponse(true, 'labels.product_fetched_successfully', $response);
    }

    /**
     * Get product by Slug.
     */
    public function show(Request $request, $slug): JsonResponse
    {
        try {
            $validated = $request->validate([
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric',
            ]);
            $product = Product::select('id')->where('slug', $slug)->first();
            if (!$product) {
                return ApiResponseType::sendJsonResponse(success: false, message: __('labels.product_not_found_with_slug'), data: []);
            }
            $id = $product->id;
            $product = Product::getProductByLocation(latitude: $validated['latitude'], longitude: $validated['longitude'], id: $id);
            if (!$product) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: 'labels.product_not_found',
                    data: []
                );
            }
            $product = new ProductResource($product);
            return ApiResponseType::sendJsonResponse(
                success: true,
                message: 'labels.product_fetched_successfully',
                data: $product
            );
        } catch (ValidationException $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.validation_error',
                data: $e->errors()
            );
        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.error_fetching_product',
                data: $e,
            );
        }
    }

    /**
     * Search products by keywords and group results by keyword.
     */
    #[QueryParameter('latitude', description: 'Latitude of the user location', required: true, type: 'float', example: 23.11684540)]
    #[QueryParameter('longitude', description: 'Longitude of the user location', required: true, type: 'float', example: 70.02805670)]
    #[QueryParameter('keywords', description: 'Comma-separated list of keywords to search for', required: true, type: 'string', example: 'smartphone,mobile,phone')]
    #[QueryParameter('per_page', description: 'Products Per Page per keyword', type: 'int', default: 10, example: 10)]
    public function searchByKeywords(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric',
                'keywords' => 'required|string|min:1',
                'per_page' => 'integer|min:1|max:50',
            ]);

            $keywords = array_map('trim', explode(',', $validated['keywords']));
            $perPage = $validated['per_page'] ?? 10;
            $groupedResults = [];

            foreach ($keywords as $keyword) {
                if (empty($keyword)) {
                    continue;
                }

                $filter = ['search' => $keyword];
                $products = Product::getProductsByLocation(
                    latitude: $validated['latitude'],
                    longitude: $validated['longitude'],
                    perPage: $perPage,
                    filter: $filter
                );

                $transformedProducts = $products->getCollection()->map(fn($product) => new ProductListResource($product));

                $groupedResults[] = [
                    'keyword' => $keyword,
                    'total_products' => $products->total(),
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'products' => $transformedProducts
                ];
            }

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: 'labels.products_fetched_by_keywords_successfully',
                data: $groupedResults
            );

        } catch (ValidationException $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.validation_error',
                data: $e->errors()
            );
        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.error_fetching_products_by_keywords',
                data: $e->getMessage(),
            );
        }
    }
}
