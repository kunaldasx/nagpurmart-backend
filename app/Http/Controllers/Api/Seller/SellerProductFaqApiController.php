<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductFaq\StoreUpdateProductFaqRequest;
use App\Http\Resources\Product\ProductFaqResource;
use App\Models\Product;
use App\Models\ProductFaq;
use App\Types\Api\ApiResponseType;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Seller Product FAQs')]
class SellerProductFaqApiController extends Controller
{
    use AuthorizesRequests;

    /**
     * List Product FAQs for authenticated seller with pagination and search
     */
    #[QueryParameter('page', description: 'Page number for pagination.', type: 'int', default: 1, example: 1)]
    #[QueryParameter('per_page', description: 'Number of items per page.', type: 'int', default: 15, example: 15)]
    #[QueryParameter('search', description: 'Search by question/answer.', type: 'string', example: 'delivery')]
    #[QueryParameter('product_id', description: 'Filter by product ID.', type: 'int', example: 10)]
    public function index(Request $request): JsonResponse
    {
        try {
            $this->authorize('viewAny', ProductFaq::class);

            $user = auth()->user();
            $seller = $user?->seller();
            if (!$seller) {
                return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found'), null, 404);
            }

            $perPage = (int) $request->input('per_page', 15);
            $q = $request->input('search');
            $productId = $request->input('product_id');

            $query = ProductFaq::query()
                ->with('product')
                ->whereHas('product', function ($sub) use ($seller) {
                    $sub->where('seller_id', $seller->id);
                })
                ->orderByDesc('id');

            if ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('question', 'like', "%$q%")
                        ->orWhere('answer', 'like', "%$q%");
                });
            }

            if ($productId) {
                $query->where('product_id', (int) $productId);
            }

            $paginator = $query->paginate($perPage);
            $paginator->getCollection()->transform(fn($faq) => new ProductFaqResource($faq));

            $response = [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'data' => $paginator->items(),
            ];

            return ApiResponseType::sendJsonResponse(true, __('labels.product_faqs_fetched_successfully'), $response);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.permission_denied'), [], 403);
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(false, __('labels.failed_to_fetch_product_faqs'), ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Show a single Product FAQ owned by the seller
     */
    public function show(int $id): JsonResponse
    {
        try {
            $user = auth()->user();
            $seller = $user?->seller();
            if (!$seller) {
                return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found'), null, 404);
            }

            $faq = ProductFaq::with('product')
                ->whereHas('product', fn($q) => $q->where('seller_id', $seller->id))
                ->findOrFail($id);

            $this->authorize('view', $faq);

            return ApiResponseType::sendJsonResponse(true, __('labels.product_faq_fetched_successfully'), new ProductFaqResource($faq));
        } catch (ModelNotFoundException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.product_faq_not_found'), null, 404);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.permission_denied'), [], 403);
        }
    }

    /**
     * Create a Product FAQ for a product owned by the seller
     */
    public function store(StoreUpdateProductFaqRequest $request): JsonResponse
    {
        try {
            $this->authorize('create', ProductFaq::class);

            $user = auth()->user();
            $seller = $user?->seller();
            if (!$seller) {
                return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found'), null, 404);
            }

            $validated = $request->validated();

            // Ensure the product belongs to the seller
            $product = Product::where('seller_id', $seller->id)->find($validated['product_id'] ?? 0);
            if (!$product) {
                return ApiResponseType::sendJsonResponse(false, __('labels.permission_denied'), [], 403);
            }

            $faq = ProductFaq::create($validated);

            return ApiResponseType::sendJsonResponse(true, __('labels.product_faq_created_successfully'), new ProductFaqResource($faq));
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.permission_denied'), [], 403);
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(false, __('labels.invalid_product_faq_data'), ['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Update a Product FAQ owned by the seller
     */
    public function update(StoreUpdateProductFaqRequest $request, int $id): JsonResponse
    {
        try {
            $user = auth()->user();
            $seller = $user?->seller();
            if (!$seller) {
                return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found'), null, 404);
            }

            $faq = ProductFaq::whereHas('product', fn($q) => $q->where('seller_id', $seller->id))
                ->findOrFail($id);

            $this->authorize('update', $faq);

            $validated = $request->validated();

            // If product_id is being changed, ensure the new product belongs to seller
            if (isset($validated['product_id'])) {
                $product = Product::where('seller_id', $seller->id)->find($validated['product_id']);
                if (!$product) {
                    return ApiResponseType::sendJsonResponse(false, __('labels.permission_denied'), [], 403);
                }
            }

            $faq->update($validated);

            return ApiResponseType::sendJsonResponse(true, __('labels.product_faq_updated_successfully'), new ProductFaqResource($faq));
        } catch (ModelNotFoundException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.product_faq_not_found'), null, 404);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.permission_denied'), [], 403);
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(false, __('labels.invalid_product_faq_data'), ['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Delete a Product FAQ owned by the seller
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $user = auth()->user();
            $seller = $user?->seller();
            if (!$seller) {
                return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found'), null, 404);
            }

            $faq = ProductFaq::whereHas('product', fn($q) => $q->where('seller_id', $seller->id))
                ->findOrFail($id);

            $this->authorize('delete', $faq);

            $faq->delete();

            return ApiResponseType::sendJsonResponse(true, __('labels.product_faq_deleted_successfully'), []);
        } catch (ModelNotFoundException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.product_faq_not_found'), null, 404);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.permission_denied'), [], 403);
        }
    }
}
