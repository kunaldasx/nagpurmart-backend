<?php

namespace App\Http\Controllers\Api\User;

use App\Enums\Order\OrderItemStatusEnum;
use App\Enums\SpatieMediaCollectionName;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\Review\StoreReviewRequest;
use App\Http\Requests\User\Review\UpdateReviewRequest;
use App\Http\Resources\Product\ProductListResource;
use App\Http\Resources\User\ReviewResource;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Services\SpatieMediaService;
use App\Types\Api\ApiResponseType;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Mockery\Exception;

#[Group('Product Reviews')]
class ProductReviewApiController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of reviews.
     * Optionally filter by product_id.
     */
    #[QueryParameter('page', description: 'Page number for pagination.', type: 'int', default: 1, example: 1)]
    #[QueryParameter('per_page', description: 'Number of items per page.', type: 'int', default: 15, example: 15)]
    #[QueryParameter('product_id', description: 'Filter Product Wise.', type: 'int', example: 1)]
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);

        $productId = $request->input('product_id');

        $query = Review::with('user', 'product');

        if ($productId) {
            $query->where('product_id', $productId);
        }

        $reviews = $query->latest()->paginate($perPage);

        return ApiResponseType::sendJsonResponse(
            true,
            __('labels.reviews_fetched_successfully'),
            ReviewResource::collection($reviews)
        );
    }

    /**
     * Store a newly created review.
     */
    public function store(StoreReviewRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $validated = $request->validated();

            // Fetch the order item and ensure it belongs to the user and is delivered
            $orderItem = OrderItem::with(['order:id,user_id', 'product:id', 'store:id'])
                ->find($validated['order_item_id']);

            if (!$orderItem) {
                return ApiResponseType::sendJsonResponse(false, __('labels.order_item_not_found'), []);
            }

            if ($orderItem->order->user_id !== $user->id) {
                return ApiResponseType::sendJsonResponse(false, __('labels.not_authorized_to_modify_review'), []);
            }

            if ($orderItem->status !== OrderItemStatusEnum::DELIVERED()) {
                return ApiResponseType::sendJsonResponse(false, __('labels.order_item_not_delivered'), []);
            }

            // ✅ Check if the user already reviewed this order item
            $existingReview = Review::where('user_id', $user->id)
                ->where('order_item_id', $orderItem->id)
                ->first();

            if ($existingReview) {
                return ApiResponseType::sendJsonResponse(
                    false,
                    __('labels.already_reviewed_this_order_item'),
                    []
                );
            }
            DB::beginTransaction();
            $review = Review::create([
                'user_id' => $user->id,
                'product_id' => $orderItem->product_id,
                'order_id' => $orderItem->order_id,
                'order_item_id' => $orderItem->id,
                'store_id' => $orderItem->store_id,
                'rating' => $validated['rating'],
                'title' => $validated['title'],
                'comment' => $validated['comment'] ?? null,
            ]);
            if ($request->hasFile('review_images')) {
                foreach ($request->file('review_images') as $image) {
                    SpatieMediaService::uploadFromRequest($review, $image, SpatieMediaCollectionName::REVIEW_IMAGES());
                }
            }
            DB::commit();
            return ApiResponseType::sendJsonResponse(
                true,
                __('labels.review_added_successfully'),
                new ReviewResource($review)
            );
        } catch (AuthenticationException $e) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(
                success: false, message: __('user_not_authenticated'), data: []
            );
        } catch (Exception $e) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(
                success: false, message: __('something_went_wrong'), data: []
            );
        }
    }


    /**
     * Display the specified review.
     */
    public function show(int $id): JsonResponse
    {
        $review = Review::with('user', 'product')->find($id);

        if (!$review) {
            return ApiResponseType::sendJsonResponse(false, __('labels.review_not_found'), []);
        }

        return ApiResponseType::sendJsonResponse(
            true,
            __('labels.review_fetched_successfully'),
            new ReviewResource($review)
        );
    }

    /**
     * Update the specified review.
     */
    public function update(UpdateReviewRequest $request, int $id): JsonResponse
    {
        try {
            DB::beginTransaction();
            $user = Auth::user();

            $review = Review::where('id', $id)->where('user_id', $user->id)->first();

            if (!$review) {
                return ApiResponseType::sendJsonResponse(false, __('labels.review_not_found_or_unauthorized'), []);
            }
            // ✅ Check if user owns this review
            $this->authorize('update', $review);
            $review->update($request->validated());
            if ($request->hasFile('review_images')) {
                // Remove existing additional images if requested
                $review->clearMediaCollection(SpatieMediaCollectionName::REVIEW_IMAGES());

                // Upload new additional images
                foreach ($request->file('review_images') as $image) {
                    SpatieMediaService::uploadFromRequest($review, $image, SpatieMediaCollectionName::REVIEW_IMAGES());
                }
            }
            DB::commit();
            return ApiResponseType::sendJsonResponse(
                true,
                __('labels.review_updated_successfully'),
                new ReviewResource($review)
            );
        } catch (AuthenticationException $e) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(
                success: false, message: __('user_not_authenticated'), data: []
            );
        } catch (Exception $e) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(
                success: false, message: __('something_went_wrong'), data: []
            );
        }
    }

    /**
     * Remove the specified review.
     */
    public function destroy(int $id): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return ApiResponseType::sendJsonResponse(false, __('labels.user_not_authenticated'), []);
        }

        $review = Review::where('id', $id)->where('user_id', $user->id)->first();

        if (!$review) {
            return ApiResponseType::sendJsonResponse(false, __('labels.review_not_found_or_unauthorized'), []);
        }
        // ✅ Check if user owns this review
        $this->authorize('delete', $review);

        $review->delete();

        return ApiResponseType::sendJsonResponse(
            true,
            __('labels.review_deleted_successfully'),
            []
        );
    }

    /**
     * Get reviews for a specific product with rating stats.
     */
    #[QueryParameter('page', description: 'Page number for pagination.', type: 'int', default: 1, example: 1)]
    #[QueryParameter('per_page', description: 'Number of items per page.', type: 'int', default: 15, example: 15)]
    public function getProductReviews(string $slug): JsonResponse
    {
        try {
            $product = Product::select('id')->where('slug', $slug)->first();
            if (!$product) {
                return ApiResponseType::sendJsonResponse(false, __('labels.product_not_found'), []);
            }

            // ✅ Fetch paginated reviews
            $reviews = Review::where('product_id', $product->id)
                ->with('user:id,name') // eager load user
                ->latest()
                ->paginate(10);
            $stats = Review::scopeProductRatingStats($product->id);

            return ApiResponseType::sendJsonResponse(
                true,
                __('labels.reviews_fetched_successfully'),
                [
                    'current_page' => $reviews->currentPage(),
                    'last_page' => $reviews->lastPage(),
                    'per_page' => $reviews->perPage(),
                    'total' => $reviews->total(),
                    'data' => [
                        'total_reviews' => $stats->total_reviews ?? 0,
                        'average_rating' => $stats->average_rating ?? 0.0,
                        'ratings_breakdown' => [
                            '1_star' => $stats->one_star_count ?? 0,
                            '2_star' => $stats->two_star_count ?? 0,
                            '3_star' => $stats->three_star_count ?? 0,
                            '4_star' => $stats->four_star_count ?? 0,
                            '5_star' => $stats->five_star_count ?? 0,
                        ],
                        'reviews' => ReviewResource::collection($reviews),
                    ],
                ]
            );
        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(
                false,
                __('labels.something_went_wrong'),
                []
            );
        }
    }

    /**
     * Get products available for review by the user.
     */
    #[QueryParameter('page', description: 'Page number for pagination.', type: 'int', default: 1, example: 1)]
    #[QueryParameter('per_page', description: 'Number of items per page.', type: 'int', default: 15, example: 15)]
    public function getAvailableProductsForReview(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: __('labels.user_not_authenticated'),
                    data: []
                );
            }

            $perPage = $request->input('per_page', 15);

            // Find user's delivered order items
            $deliveredOrderItems = OrderItem::where('status', OrderItemStatusEnum::DELIVERED())
                ->whereHas('order', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->select(['id', 'product_id'])
                ->get();

            // Get order_item_ids already reviewed by this user
            $reviewedOrderItemIds = Review::where('user_id', $user->id)
                ->pluck('order_item_id')
                ->toArray();

            // Keep only order items that have not yet been reviewed
            $unreviewedOrderItems = $deliveredOrderItems->reject(function ($item) use ($reviewedOrderItemIds) {
                return in_array($item->id, $reviewedOrderItemIds);
            });

            // From the remaining order items, extract distinct product IDs
            $availableProductIds = $unreviewedOrderItems->pluck('product_id')->values()->toArray();

            // Get the products
            $products = Product::whereIn('id', $availableProductIds)
                ->with([
                    'variants.attributes.attribute',
                    'variants.attributes.attributeValue',
                    'variantAttributes.attribute',
                    'variantAttributes.attributeValue'
                ])
                ->paginate($perPage);

            $products->getCollection()->transform(fn($product) => new ProductListResource($product));

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: __('labels.products_available_for_review_fetched_successfully'),
                data: $products
            );
        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.something_went_wrong'),
                data: $e->getMessage()
            );
        }
    }
}
