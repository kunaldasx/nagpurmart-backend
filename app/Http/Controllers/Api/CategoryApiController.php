<?php

namespace App\Http\Controllers\Api;

use App\Enums\CategoryStatusEnum;
use App\Enums\Product\ProductStatusEnum;
use App\Enums\Product\ProductVarificationStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Models\Product;
use App\Services\DeliveryZoneService;
use App\Enums\Category\CategorySubCategoryFilterEnum;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use App\Types\Api\ApiResponseType;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

#[Group('Categories')]
class CategoryApiController extends Controller
{
    /**
     * Get categories base api.
     * If slug is not provided, returns root categories.
     */
    #[QueryParameter('page', description: 'Page number for pagination.', type: 'int', default: 1, example: 1)]
    #[QueryParameter('per_page', description: 'Number of categories per page', type: 'int', default: 15, example: 15)]
    #[QueryParameter('latitude', description: 'Latitude of the user location for zone-wise product counts', type: 'float', example: 23.11684540)]
    #[QueryParameter('longitude', description: 'Longitude of the user location for zone-wise product counts', type: 'float', example: 70.02805670)]
    #[QueryParameter('slug', description: 'Category slug to filter by', type: 'string', example: 'apple')]
    #[QueryParameter('search', description: 'Search term to filter categories', type: 'string', example: 'electronics')]
    #[QueryParameter('home', description: 'When true, returns only root categories marked as home categories, ordered by sort_order', type: 'boolean', example: true)]
    #[QueryParameter('include_no_product', description: 'When true, also return categories without product', type: 'boolean', example: 'false')]
    public function index(Request $request): JsonResponse
    {
        // Validate inputs
        $request->validate([
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'slug' => 'sometimes|string',
            'search' => 'sometimes|string',
            'home' => 'nullable',
        ]);

        $perPage = (int)$request->input('per_page', 15);
        $includeNoProduct = $request->input('include_no_product', false);

        // Base query: either children of slug or root categories
        $query = Category::query()->with('parent')->where('status', CategoryStatusEnum::ACTIVE());
        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->input('search') . '%');
        }
        $homeOnly = filter_var($request->input('home', false), FILTER_VALIDATE_BOOLEAN);

        if ($homeOnly) {
            // Force root categories and home-category filter when `home` is truthy
            $query->whereNull('parent_id')
                ->where('is_home_category', true);
        } elseif ($request->has('slug')) {
            $parentCategory = Category::where('slug', $request->input('slug'))
                ->where('status', CategoryStatusEnum::ACTIVE())
                ->first();
            if (!$parentCategory) {
                return ApiResponseType::sendJsonResponse(true, 'labels.category_fetched_successfully', $this->emptyResponse($perPage));
            }
            $query->where('parent_id', $parentCategory->id);
        } else {
            $query->whereNull('parent_id');
        }

        // Zone context and counts
        [$useZoneFilter, $storeIds] = $this->zoneContext(
            $request->input('latitude'),
            $request->input('longitude')
        );
        $this->applyProductsCount($query, $storeIds);

        // Fetch and post-process
        if ($homeOnly) {
            // Home categories should be ordered by configured sort_order
            $allCategories = $query->ordered()->get();
        } else {
            $allCategories = $query->orderBy('title')->get();
        }
        // When listing root categories, aggregate children's product counts only for root items.
        // When a slug is provided (listing children of a specific category), aggregate for all
        // returned categories so their immediate children's products are included as well.
        $predicate = $request->has('slug')
            ? function (Category $cat) {
                return true;
            }
            : function (Category $cat) {
                return is_null($cat->parent_id);
            };

        $processed = $this->aggregateImmediateChildrenProducts($allCategories, $predicate, $useZoneFilter, $storeIds);

        if (!$includeNoProduct) {
            $processed = $this->filterNonZeroProducts($processed);
        }

        // Paginate and respond
        $paginator = $this->paginateCollection($processed, (int)$request->input('page', 1), $perPage, $request);
        $response = ApiResponseType::responseFromPaginator($paginator);
        return ApiResponseType::sendJsonResponse(true, 'labels.category_fetched_successfully', $response);
    }

    /**
     * Get sub-categories.
     */
    #[QueryParameter('page', description: 'Page number for pagination.', type: 'int', default: 1, example: 1)]
    #[QueryParameter('per_page', description: 'Number of categories per page', type: 'int', default: 15, example: 15)]
    #[QueryParameter('filter', description: 'Filter enum: random | top_category', type: 'string', example: 'random')]
    #[QueryParameter('latitude', description: 'Latitude of the user location for zone-wise product counts', type: 'float', example: 23.11684540)]
    #[QueryParameter('longitude', description: 'Longitude of the user location for zone-wise product counts', type: 'float', example: 70.02805670)]
    public function subCategories(Request $request): JsonResponse
    {
        // Validate inputs
        $request->validate([
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'filter' => 'nullable|string',
        ]);

        $perPage = (int)$request->input('per_page', 15);
//        $filter = is_null($request->input('filter')) ? CategorySubCategoryFilterEnum::RANDOM() : $request->input('filter');
        $filter = $request->input('filter');

        $query = Category::query()
            ->with('parent')
            ->whereNotNull('parent_id')
            ->where('status', CategoryStatusEnum::ACTIVE());

        if ($filter === CategorySubCategoryFilterEnum::TOP_CATEGORY()) {
            $topCategory = Category::query()
                ->whereNull('parent_id')
                ->where('status', CategoryStatusEnum::ACTIVE())
                ->first();
            if (!$topCategory) {
                return ApiResponseType::sendJsonResponse(true, 'labels.category_fetched_successfully', $this->emptyResponse($perPage, ['filter' => $filter]));
            }
            $query->where('parent_id', $topCategory->id);
        }

        // Zone context and counts
        [$useZoneFilter, $storeIds] = $this->zoneContext(
            $request->input('latitude'),
            $request->input('longitude')
        );
        $this->applyProductsCount($query, $storeIds);

        // Ordering
        $filter === CategorySubCategoryFilterEnum::RANDOM()
            ? $query->inRandomOrder()
            : $query->orderBy('title');

        $allCategories = $query->get();

        $processed = $this->aggregateImmediateChildrenProducts($allCategories, function (Category $cat) {
            return ($cat->children_count ?? 0) > 0;
        }, $useZoneFilter, $storeIds);

        $filtered = $this->filterNonZeroProducts($processed);

        $paginator = $this->paginateCollection($filtered, (int)$request->input('page', 1), $perPage, $request);
        $response = array_merge(ApiResponseType::responseFromPaginator($paginator), ['filter' => $filter]);
        return ApiResponseType::sendJsonResponse(true, 'labels.category_fetched_successfully', $response);
    }

    /**
     * Get categories for sidebar filter.
     */
    #[QueryParameter('ids', description: 'Comma separated list of category IDs or array of IDs', type: 'string', example: '1,2,3')]
    #[QueryParameter('page', description: 'Page number for pagination.', type: 'int', default: 1, example: 1)]
    #[QueryParameter('per_page', description: 'Number of categories per page', type: 'int', default: 15, example: 15)]
    #[QueryParameter('latitude', description: 'Latitude of the user location for zone-wise product counts', type: 'float', example: 23.11684540)]
    #[QueryParameter('longitude', description: 'Longitude of the user location for zone-wise product counts', type: 'float', example: 70.02805670)]
    public function getCategories(Request $request): JsonResponse
    {
        // Normalize ids: accept CSV string or array
        $idsInput = $request->input('ids');
        if (is_string($idsInput)) {
            $ids = array_filter(array_map('trim', explode(',', $idsInput)), fn($v) => $v !== '');
            $request->merge(['ids' => $ids]);
        }

        // Validate inputs
        $validated = $request->validate([
            'ids' => 'sometimes|array',
            'ids.*' => 'integer|min:1',
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        // Prepare IDs (optional)
        $ids = array_values(array_unique(array_map('intval', $validated['ids'] ?? [])));

        $perPage = (int)$request->input('per_page', 15);

        $query = Category::query()
            ->with('parent')
            ->where('status', CategoryStatusEnum::ACTIVE());
        // If IDs were provided, limit to those
        if (!empty($ids)) {
            $query->whereIn('id', $ids);
        }

        // Zone context and counts
        [$useZoneFilter, $storeIds] = $this->zoneContext(
            $request->input('latitude'),
            $request->input('longitude')
        );
        $this->applyProductsCount($query, $storeIds);

        // Fetch all. If IDs provided, keep their order; otherwise order by title
        if (!empty($ids)) {
            $allCategories = $query->get()->sortBy(function (Category $cat) use ($ids) {
                return array_search($cat->id, $ids);
            })->values();
        } else {
            $allCategories = $query->orderBy('title')->get();
        }

        // Aggregate children's product counts for root categories to include immediate subcategory products
        $processed = $this->aggregateImmediateChildrenProducts(
            $allCategories,
            function (Category $cat) {
                return is_null($cat->parent_id);
            },
            $useZoneFilter,
            $storeIds
        );

        $filtered = $this->filterNonZeroProducts($processed);

        // Paginate and respond
        $paginator = $this->paginateCollection($filtered, (int)$request->input('page', 1), $perPage, $request);
        $response = ApiResponseType::responseFromPaginator($paginator);
        return ApiResponseType::sendJsonResponse(true, 'labels.category_fetched_successfully', $response);
    }

    /**
     * Build an empty paginated-like response.
     *
     */
    private function emptyResponse(int $perPage, array $extra = []): array
    {
        return array_merge([
            'current_page' => 1,
            'last_page' => 1,
            'per_page' => $perPage,
            'total' => 0,
            'data' => [],
        ], $extra);
    }

    /**
     * Determine zone context and store ids.
     * @return array{0: bool, 1: array}
     */
    private function zoneContext($latitude, $longitude): array
    {
        $useZoneFilter = !is_null($latitude) && !is_null($longitude);
        if (!$useZoneFilter) {
            return [false, []];
        }
        $zoneInfo = DeliveryZoneService::getZonesAtPoint((float)$latitude, (float)$longitude);
        $storeIds = Product::getStoreIdsInZone($zoneInfo);
        return [true, $storeIds ?? []];
    }

    /**
     * Apply children and products_count withCount to a query.
     */
    private function applyProductsCount(Builder $query, array $storeIds): void
    {
        if (!empty($storeIds)) {
            $query->withCount([
                'children',
                'products as products_count' => function ($q) use ($storeIds) {
                    if (empty($storeIds)) {
                        $q->whereRaw('1 = 0');
                        return;
                    }
                    $q->where('verification_status', ProductVarificationStatusEnum::APPROVED())
                        ->where('status', ProductStatusEnum::ACTIVE())
                        ->whereHas('variants.storeProductVariants', function ($sq) use ($storeIds) {
                            $sq->whereIn('store_id', $storeIds);
                        });
                }
            ]);
            return;
        }
        $query->withCount(['children', 'products']);
    }

    /**
     * For each category in the collection, add immediate children's product counts when predicate matches.
     * @param Collection<int,Category> $categories
     * @param callable $predicate receives Category and returns bool to decide aggregation
     */
    private function aggregateImmediateChildrenProducts(Collection $categories, callable $predicate, bool $useZoneFilter, array $storeIds): Collection
    {
        return $categories->map(function (Category $cat) use ($predicate, $useZoneFilter, $storeIds) {
            if (!$predicate($cat)) {
                return $cat;
            }
            $subCategoryIds = Category::where('parent_id', $cat->id)
                ->where('status', CategoryStatusEnum::ACTIVE())
                ->pluck('id');
            if ($subCategoryIds->isEmpty()) {
                return $cat;
            }

            if ($useZoneFilter) {
                $additionalCount = Product::whereIn('category_id', $subCategoryIds)
                    ->where('verification_status', ProductVarificationStatusEnum::APPROVED())
                    ->where('status', ProductStatusEnum::ACTIVE())
                    ->whereHas('variants.storeProductVariants', function ($sq) use ($storeIds) {
                        $sq->whereIn('store_id', $storeIds);
                    })
                    ->count();
            } else {
                $additionalCount = Product::whereIn('category_id', $subCategoryIds)->count();
            }
            $cat->products_count = ($cat->products_count ?? 0) + $additionalCount;
            return $cat;
        });
    }

    /**
     * Keep categories with products_count > 0.
     * @param Collection<int,Category> $categories
     */
    private function filterNonZeroProducts(Collection $categories): Collection
    {
        return $categories->filter(function ($cat) {
            return ($cat->products_count ?? 0) > 0;
        })->values();
    }

    /**
     * Paginate an in-memory collection and wrap items in CategoryResource.
     */
    private function paginateCollection(Collection $categories, int $page, int $perPage, Request $request): LengthAwarePaginator
    {
        $total = $categories->count();
        $items = $categories->slice(($page - 1) * $perPage, $perPage)->values();
        $resourceItems = $items->map(fn($cat) => new CategoryResource($cat));

        $paginator = new LengthAwarePaginator(
            $resourceItems,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'pageName' => 'page',
            ]
        );
        $paginator->appends($request->query());
        return $paginator;
    }
}
