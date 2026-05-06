<?php

namespace App\Http\Controllers\Api\Product;

use App\Http\Controllers\Controller;
use App\Enums\Product\SidebarFilterContextTypeEnum;
use App\Http\Resources\BrandResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\GlobalProductAttributeResource;
use App\Models\Brand;
use App\Models\Category;
use App\Models\GlobalProductAttribute;
use App\Models\FeaturedSection;
use App\Models\Product;
use App\Models\ProductVariantAttribute;
use App\Services\DeliveryZoneService;
use App\Types\Api\ApiResponseType;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Products')]
class ProductSidebarApiController extends Controller
{
    /**
     * Sidebar filters for product listing (categories, brands, attributes with values).
     *
     * This endpoint aggregates available filter options from products within the
     * user's delivery zone and optional filters. It returns:
     * - categories: list of category values
     * - brands: list of brand values
     * - attributes: array of attribute groups each containing its values
     */
    #[QueryParameter('latitude', description: 'Latitude of the user location', required: true, type: 'float', example: 23.2420)]
    #[QueryParameter('longitude', description: 'Longitude of the user location', required: true, type: 'float', example: 69.6669)]
    #[QueryParameter('brands', description: 'Comma-separated list of brand slugs to filter products', type: 'string', example: 'brand-a,brand-b')]
    #[QueryParameter('categories', description: 'Comma-separated list of category slugs to filter products', type: 'string', example: 'fruits,vegetables')]
    #[QueryParameter('attribute_values', description: 'Comma-separated list of global attribute value IDs to filter products', type: 'string', example: '12,34,56')]
    #[QueryParameter('type', description: 'Context type for deriving filter options: featured_section | category | brand | store | search. Note: store and search are ONLY accepted via this type/value context.', type: 'string', example: 'featured_section')]
    #[QueryParameter('value', description: 'Value for the selected type. Slug for featured_section/category/brand/store, free text for search', type: 'string', example: 'summer-deals')]
    public function filters(Request $request): JsonResponse
    {
        $this->normalizeCsvInputs($request);
        $this->validateFiltersRequest($request);

        $latitude = (float) $request->input('latitude');
        $longitude = (float) $request->input('longitude');

        // Derive base context from type/value
        [$baseFilter, $earlyEmpty] = $this->deriveBaseFilterFromContext(
            $request->input('type'),
            $request->input('value')
        );

        if ($earlyEmpty === true) {
            return $this->emptyResponse();
        }

        // Merge active filters (categories/brands/attribute_values) with context overrides
//        $activeFilter = $this->buildActiveFilter($request, $baseFilter);
        $activeFilter = [
            'categories' => $request->input('categories'),
            'brands' => $request->input('brands'),
            'attribute_values' => $request->input('attribute_values'),
        ];

        $zoneInfo = DeliveryZoneService::getZonesAtPoint($latitude, $longitude);
        $baseProductsQuery = Product::scopeByLocation(zoneInfo: $zoneInfo, query: Product::query(), filter: $baseFilter);
        $productsQuery = Product::scopeByLocation(zoneInfo: $zoneInfo, query: Product::query(), filter: $activeFilter);

        if (!(clone $productsQuery)->limit(1)->exists()) {
            return $this->emptyResponse();
        }

        $categories = $this->computeCategoryOptions($zoneInfo, $baseProductsQuery, $activeFilter);
        $brands = $this->computeBrandOptions($zoneInfo, $baseProductsQuery, $activeFilter);
        $attributes = $this->computeAttributeOptions($zoneInfo, $baseProductsQuery, $activeFilter);

        return $this->successResponse($categories, $brands, $attributes);
    }

    /**
     * Normalize CSV request inputs into arrays.
     */
    private function normalizeCsvInputs(Request $request): void
    {
        $categoriesCsv = $request->input('categories');
        if (is_string($categoriesCsv)) {
            $request->merge(['categories' => array_values(array_filter(array_map('trim', explode(',', $categoriesCsv))))]);
        }

        $brandsCsv = $request->input('brands');
        if (is_string($brandsCsv)) {
            $request->merge(['brands' => array_values(array_filter(array_map('trim', explode(',', $brandsCsv))))]);
        }

        $attrValuesCsv = $request->input('attribute_values');
        if (is_string($attrValuesCsv)) {
            $ids = array_values(array_filter(array_map(function ($v) {
                $n = (int) trim($v);
                return $n > 0 ? $n : null;
            }, explode(',', $attrValuesCsv))));
            $request->merge(['attribute_values' => $ids]);
        }
    }

    /**
     * Validate sidebar filters request.
     */
    private function validateFiltersRequest(Request $request): void
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'categories' => 'nullable|array',
            'categories.*' => 'string',
            'brands' => 'nullable|array',
            'brands.*' => 'string',
            'attribute_values' => 'nullable|array',
            'attribute_values.*' => 'integer',
            'type' => 'nullable|string|in:' . implode(',', SidebarFilterContextTypeEnum::values()),
            'value' => 'nullable|string',
        ]);
    }

    /**
     * Derive base filter from context (type/value). Returns [baseFilter, earlyEmptyFlag].
     */
    private function deriveBaseFilterFromContext(?string $type, ?string $value): array
    {
        $baseFilter = [];
        if (!$type || !in_array($type, SidebarFilterContextTypeEnum::values(), true)) {
            return [$baseFilter, false];
        }

        switch ($type) {
            case SidebarFilterContextTypeEnum::CATEGORY():
                if ($value) {
                    $baseFilter['categories'] = [$value];
                }
                break;
            case SidebarFilterContextTypeEnum::BRAND():
                if ($value) {
                    $baseFilter['brands'] = [$value];
                }
                break;
            case SidebarFilterContextTypeEnum::STORE():
                if ($value) {
                    $baseFilter['store'] = $value;
                }
                break;
            case SidebarFilterContextTypeEnum::SEARCH():
                if ($value) {
                    $baseFilter['search'] = $value;
                }
                break;
            case SidebarFilterContextTypeEnum::FEATURED_SECTION():
                if ($value) {
                    $section = FeaturedSection::where('slug', $value)->first();
                    if ($section) {
                        $catSlugs = $section->categories()->pluck('slug')->filter()->unique()->values()->toArray();
                        if (!empty($catSlugs)) {
                            $baseFilter['categories'] = $catSlugs;
                        } else {
                            return [$baseFilter, true]; // early empty
                        }
                    } else {
                        return [$baseFilter, true]; // early empty
                    }
                }
                break;
        }

        return [$baseFilter, false];
    }

    /**
     * Build active filter by merging request filters with base context overrides.
     */
    private function buildActiveFilter(Request $request, array $baseFilter): array
    {
        $activeFilter = [
            'categories' => $request->input('categories'),
            'brands' => $request->input('brands'),
            'attribute_values' => $request->input('attribute_values'),
        ];

        foreach (['categories', 'brands', 'store', 'search'] as $k) {
            if (array_key_exists($k, $baseFilter) && !is_null($baseFilter[$k])) {
                $activeFilter[$k] = array_merge($activeFilter[$k] ?? [], $baseFilter[$k]);
            }
        }
        return $activeFilter;
    }

    /**
     * Compute category options and enabled IDs.
     */
    private function computeCategoryOptions(array $zoneInfo, $baseProductsQuery, array $activeFilter): \Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection
    {
        $allCategories = Category::query()
            ->whereIn('id', (clone $baseProductsQuery)->select('category_id')->distinct())
            ->orderBy('title')
            ->limit(50)
            ->get();

        $queryWithoutCategories = Product::scopeByLocation(zoneInfo: $zoneInfo, query: Product::query(), filter: array_merge($activeFilter, ['categories' => null]));
        $enabledCategoryIds = (clone $queryWithoutCategories)->distinct()->pluck('category_id')->filter()->unique()->values()->toArray();

        $categories = $allCategories->map(function ($cat) use ($enabledCategoryIds) {
            $cat->enabled = in_array($cat->id, $enabledCategoryIds, true);
            return $cat;
        });

        return $categories->sortByDesc('enabled');
    }

    /**
     * Compute brand options and enabled IDs.
     */
    private function computeBrandOptions(array $zoneInfo, $baseProductsQuery, array $activeFilter): \Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection
    {
        $allBrands = Brand::query()
            ->whereIn('id', (clone $baseProductsQuery)->select('brand_id')->distinct())
            ->orderBy('title')
            ->limit(50)
            ->get();

        $queryWithoutBrands = Product::scopeByLocation(zoneInfo: $zoneInfo, query: Product::query(), filter: array_merge($activeFilter, ['brands' => null]));
        $enabledBrandIds = (clone $queryWithoutBrands)->distinct()->pluck('brand_id')->filter()->unique()->values()->toArray();

        $brands = $allBrands->map(function ($brand) use ($enabledBrandIds) {
            $brand->enabled = in_array($brand->id, $enabledBrandIds, true);
            return $brand;
        });

        return $brands->sortByDesc('enabled');
    }

    /**
     * Compute attributes payload with enabled flags for values.
     */
    private function computeAttributeOptions(array $zoneInfo, $baseProductsQuery, array $activeFilter): array
    {
        $baseProductIds = (clone $baseProductsQuery)->pluck('products.id')->unique()->values();
        $attributeRows = ProductVariantAttribute::query()
            ->whereIn('product_id', $baseProductIds)
            ->whereNotNull('global_attribute_id')
            ->whereNotNull('global_attribute_value_id')
            ->with(['attribute:id,title,slug', 'attributeValue:id,global_attribute_id,title,swatche_value'])
            ->get(['global_attribute_id', 'global_attribute_value_id'])
            ->groupBy('global_attribute_id');

        $queryWithoutAttrValues = Product::scopeByLocation(zoneInfo: $zoneInfo, query: Product::query(), filter: array_merge($activeFilter, ['attribute_values' => null]));
        $enabledAttrProductIds = (clone $queryWithoutAttrValues)->pluck('products.id')->unique()->values();
        $enabledAttrValueIds = ProductVariantAttribute::query()
            ->whereIn('product_id', $enabledAttrProductIds)
            ->whereNotNull('global_attribute_value_id')
            ->pluck('global_attribute_value_id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $attributes = [];
        foreach ($attributeRows as $attributeId => $rows) {
            /** @var ProductVariantAttribute $first */
            $first = $rows->first();
            $attr = $first?->attribute;
            if (!$attr) {
                $attr = GlobalProductAttribute::query()->find($attributeId, ['id', 'title', 'slug']);
            }
            if (!$attr) {
                continue;
            }

            $valueItems = $rows
                ->pluck('attributeValue')
                ->filter()
                ->unique(fn($v) => $v->id ?? null)
                ->values()
                ->map(function ($v) use ($enabledAttrValueIds) {
                    return [
                        'id' => $v->id,
                        'title' => $v->title,
                        'swatche_value' => $v->swatche_value,
                        'enabled' => in_array($v->id, $enabledAttrValueIds, true),
                    ];
                })
                ->sortByDesc('enabled')
                ->sortBy('title')
                ->values()
                ->toArray();

            if (!empty($valueItems)) {
                $attributes[] = [
                    'title' => $attr->title,
                    'slug' => $attr->slug ?? null,
                    'values' => $valueItems,
                ];
            }
        }

        return $attributes;
    }

    /**
     * Standard empty response for filters.
     */
    private function emptyResponse(): JsonResponse
    {
        return ApiResponseType::sendJsonResponse(true, 'labels.filters_fetched_successfully', [
            'categories' => [],
            'brands' => [],
            'attributes' => [],
        ]);
    }

    /**
     * Standard success response for filters.
     */
    private function successResponse($categories, $brands, array $attributes): JsonResponse
    {
        return ApiResponseType::sendJsonResponse(true, 'labels.filters_fetched_successfully', [
            'categories_count' => $categories->count(),
            'brands_count' => $brands->count(),
            'attributes_count' => count($attributes),
            'categories' => CategoryResource::collection($categories),
            'brands' => BrandResource::collection($brands),
            'attributes' => GlobalProductAttributeResource::collection(collect(array_values($attributes))),
        ]);
    }

    /**
     * Get sidebar filter context types.
     */
    public function getTypes(): JsonResponse
    {
        return ApiResponseType::sendJsonResponse(true, 'labels.sidebar_filter_context_types_fetched_successfully',
            SidebarFilterContextTypeEnum::values());
    }
}
