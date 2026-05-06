<?php

namespace App\Http\Controllers\Api\Seller;

use App\Enums\Product\ProductFilterEnum;
use App\Enums\Product\ProductStatusEnum;
use App\Enums\Product\ProductTypeEnum;
use App\Enums\Product\ProductVarificationStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreUpdateProductRequest;
use App\Models\Product;
use App\Services\ProductService;
use App\Types\Api\ApiResponseType;
use App\Events\Product\ProductAfterCreate;
use App\Events\Product\ProductBeforeCreate;
use App\Http\Resources\Product\ProductListResource;
use App\Http\Resources\Product\ProductResource;
use App\Http\Resources\Product\SellerProductResource;
use App\Models\Setting;
use App\Services\SubscriptionUsageService;
use App\Traits\SubscriptionLimitGuard;
use App\Enums\Subscription\SubscriptionPlanKeyEnum;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use TypeError;

#[Group('Seller Products')]
class SellerProductApiController extends Controller
{
    use AuthorizesRequests, SubscriptionLimitGuard;

    public function __construct(protected ProductService $productService)
    {
    }

    /**
     * List products for the authenticated seller with pagination and search
     */
    #[QueryParameter('page', description: 'Page number for pagination.', type: 'int', default: 1, example: 1)]
    #[QueryParameter('per_page', description: 'Number of products per page', type: 'int', default: 15, example: 15)]
    #[QueryParameter('search', description: 'Search query for title/description', type: 'string', example: 'phone')]
    #[QueryParameter('type', description: 'Filter by product type', type: 'string', example: 'simple,variant')]
    #[QueryParameter('status', description: 'Filter by product status', type: 'string', example: 'active, draft')]
    #[QueryParameter('verification_status', description: 'Filter by verification status', type: 'string', example: 'pending_verification, rejected, approved')]
    #[QueryParameter('category_id', description: 'Filter by category ID', type: 'int', example: 1)]
    #[QueryParameter('product_filter', description: 'Special product filters', type: 'string', example: 'featured, low_stock, out_of_stock')]
    public function index(Request $request): JsonResponse
    {
        try {
            $this->authorize('viewAny', Product::class);

            $user = auth()->user();
            $seller = $user?->seller();
            if (!$seller) {
                return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found'), null, 404);
            }

            $perPage = (int)$request->input('per_page', 15);
            $q = $request->input('search');

            $query = Product::query()
                ->where('seller_id', $seller->id)
                ->with(['category', 'brand', 'variants.storeProductVariants.store','customProductSections.fields'])
                ->orderByDesc('id');

            if ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('title', 'like', "%$q%")
                        ->orWhere('short_description', 'like', "%$q%")
                        ->orWhere('description', 'like', "%$q%")
                        ->orWhere('tags', 'like', "%$q%");
                });
            }

            if ($request->has('type')) {
                $query->where('type', $request->input('type'));
            }

            if ($request->has('status')) {
                $query->where('status', $request->input('status'));
            }

            if ($request->has('verification_status')) {
                $query->where('verification_status', $request->input('verification_status'));
            }

            if ($request->has('category_id')) {
                $query->where('category_id', $request->input('category_id'));
            }

            // Apply reusable product_filter via model scope for featured/low_stock/out_of_stock
            $productFilter = $request->input('product_filter');
            if (!empty($productFilter) && in_array($productFilter, ProductFilterEnum::values(), true)) {
                $query->applyProductFilter($productFilter);
            }

            $paginator = $query->paginate($perPage);
            // Transform collection using ProductListResource to follow product response standard
            $paginator->getCollection()->transform(fn($product) => new ProductListResource($product));

            $response = [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'data' => $paginator->items(),
            ];

            return ApiResponseType::sendJsonResponse(true, __('labels.products_fetched_successfully'), $response);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.permission_denied'), [], 403);
        } catch (\Throwable $e) {
            Log::error('Seller products index error: ' . $e->getMessage());
            return ApiResponseType::sendJsonResponse(false, __('labels.error_fetching_products'), ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Show single product owned by seller
     */
    public function show($id): JsonResponse
    {
        try {

            $user = auth()->user();
            $seller = $user?->seller();
            if (!$seller) {
                return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found'), null, 404);
            }

            $product = Product::where('seller_id', $seller->id)
                ->with([
                    'category',
                    'brand',
                    'variants.attributes.attribute',
                    'variants.attributes.attributeValue',
                    'variants.storeProductVariants.store',
                    'customProductSections.fields',
                ])
                ->findOrFail($id);

            $this->authorize('view', $product);

            // For seller single product view, return seller-specific resource
            // that includes store-wise pricing details on each variant
            $productResource = new SellerProductResource($product);
            return ApiResponseType::sendJsonResponse(true, __('labels.product_fetched_successfully'), $productResource);
        } catch (ModelNotFoundException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.product_not_found'), null, 404);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.permission_denied'), [], 403);
        } catch (\Exception $e) {
            Log::error('Seller product show error: ' . $e->getMessage());
            return ApiResponseType::sendJsonResponse(false, __('labels.error_fetching_product'), ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Create product for seller
     */
    public function store(StoreUpdateProductRequest $request): JsonResponse
    {
        try {
            $this->authorize('create', Product::class);
            $validated = $request->validated();

            $user = auth()->user();
            $seller = $user?->seller();
            if (!$seller) {
                return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found'), null, 404);
            }
            $validated['seller_id'] = $seller->id;

            // Pre-check subscription usage before creating (multivendor only)
            if ($error = $this->ensureCanUseOrError($validated['seller_id'], SubscriptionPlanKeyEnum::PRODUCT_LIMIT())) {
                return $error;
            }

            event(new ProductBeforeCreate());
            $result = $this->productService->storeProduct($validated, $request);
            event(new ProductAfterCreate($result['product']));

            // Update usage after successful creation (multivendor only)
            $this->recordUsageIfMultivendor($validated['seller_id'], SubscriptionPlanKeyEnum::PRODUCT_LIMIT());

            return ApiResponseType::sendJsonResponse(true, __('labels.product_created_successfully'), [
                'product_id' => $result['product']->id,
                'product_uuid' => $result['product']->uuid,
            ], 201);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.permission_denied'), [], 403);
        } catch (\Throwable $e) {
            Log::error('Seller product store error: ' . $e->getMessage());
            return ApiResponseType::sendJsonResponse(false, __('labels.failed_to_save_product'), ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update a product owned by seller
     */
    public function update(StoreUpdateProductRequest $request, int $id): JsonResponse
    {
        try {
            $user = auth()->user();
            $seller = $user?->seller();
            if (!$seller) {
                return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found'), null, 404);
            }

            $product = Product::where('seller_id', $seller->id)->findOrFail($id);
            $this->authorize('update', $product);

            $validated = $request->validated();
            $validated['seller_id'] = $seller->id; // ensure ownership preserved

            $result = $this->productService->updateProduct($product, $validated, $request);

            return ApiResponseType::sendJsonResponse(true, __('labels.product_updated_successfully'), [
                'product_id' => $result['product']->id,
                'product_uuid' => $result['product']->uuid,
            ]);
        } catch (ModelNotFoundException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.product_not_found'), null, 404);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.permission_denied'), [], 403);
        } catch (\Throwable $e) {
            Log::error('Seller product update error: ' . $e->getMessage());
            return ApiResponseType::sendJsonResponse(false, __('labels.failed_to_update_product'), ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete a product owned by seller
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $user = auth()->user();
            $seller = $user?->seller();
            if (!$seller) {
                return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found'), null, 404);
            }

            $product = Product::where('seller_id', $seller->id)->findOrFail($id);
            $this->authorize('delete', $product);

            if ($product->hasPendingOrders()) {
                return ApiResponseType::sendJsonResponse(false, 'labels.product_cannot_be_deleted_pending_orders_exist', [], 422);
            }

            $sellerId = $product->seller_id;
            $product->delete();

            // Reduce subscription usage for product_limit only when multivendor
            if ($sellerId) {
                $this->reduceUsageIfMultivendor((int)$sellerId, SubscriptionPlanKeyEnum::PRODUCT_LIMIT());
            }

            return ApiResponseType::sendJsonResponse(true, __('labels.product_deleted_successfully'), null);
        } catch (ModelNotFoundException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.product_not_found'), null, 404);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.permission_denied'), [], 403);
        } catch (\Throwable $e) {
            Log::error('Seller product destroy error: ' . $e->getMessage());
            return ApiResponseType::sendJsonResponse(false, __('labels.failed_to_delete_product'), ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get all product enums
     * @return JsonResponse
     */
    public function getProductEnums(): JsonResponse
    {
        return ApiResponseType::sendJsonResponse(
            success: true,
            message: 'Product enums fetched successfully',
            data: [
                'type' => ProductTypeEnum::values(),
                'status' => ProductStatusEnum::values(),
                'verification_status' => ProductVarificationStatusEnum::values(),
                'product_filter' => ProductFilterEnum::values()
            ]
        );
    }

    /**
     * Update product status (draft/active)
     */
    public function updateStatus(int $id, Request $request): JsonResponse
    {
        try {
            $product = Product::findOrFail($id);
            $this->authorize('update', $product);

            $validated = $request->validate([
                'status' => 'required|string|in:'.implode(',', ProductStatusEnum::values())
            ]);

            // Toggle status between ACTIVE and DRAFT
            $newStatus = $validated['status'];

            $product->status = $newStatus;
            $product->save();

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: "Product status updated to {$newStatus} successfully",
                data: [
                    'id' => $product->id,
                    'status' => $product->status,
                ]
            );
        } catch (AuthorizationException $e) {
            return ApiResponseType::sendJsonResponse(success: false, message: 'labels.permission_denied', data: []);
        } catch (ModelNotFoundException $e) {
            return ApiResponseType::sendJsonResponse(success: false, message: 'labels.product_not_found', data: []);
        } catch (ValidationException $e) {
            return ApiResponseType::sendJsonResponse(success: false, message: $e->getMessage(), data: ['errors' => $e->errors()]);
        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(success: false, message: 'labels.something_went_wrong', data: ['error' => $e->getMessage()]);
        }
    }
}
