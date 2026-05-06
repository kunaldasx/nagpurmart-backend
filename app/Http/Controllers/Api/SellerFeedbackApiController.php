<?php

namespace App\Http\Controllers\Api;

use App\Enums\Order\OrderItemStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSellerFeedbackRequest;
use App\Http\Requests\UpdateSellerFeedbackRequest;
use App\Http\Resources\SellerFeedbackResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\SellerFeedback;
use App\Types\Api\ApiResponseType;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

#[Group('Seller Feedback')]
class SellerFeedbackApiController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of seller feedback.
     * Optionally filter by seller_id.
     */
    #[QueryParameter('page', description: 'Page number for pagination.', type: 'int', default: 1, example: 1)]
    #[QueryParameter('per_page', description: 'Number of items per page.', type: 'int', default: 15, example: 15)]
    #[QueryParameter('seller_id', description: 'Filter by seller ID.', type: 'int', example: 1)]
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $sellerId = $request->input('seller_id');

        $query = SellerFeedback::with('user', 'seller', 'order');

        if ($sellerId) {
            $query->where('seller_id', $sellerId);
        }

        $feedback = $query->latest()->paginate($perPage);

        return ApiResponseType::sendJsonResponse(
            true,
            __('labels.seller_feedback_fetched_successfully'),
            [
                'current_page' => $feedback->currentPage(),
                'last_page' => $feedback->lastPage(),
                'per_page' => $feedback->perPage(),
                'total' => $feedback->total(),
                'data' => SellerFeedbackResource::collection($feedback),
            ]
        );
    }

    /**
     * Store a newly created seller feedback.
     */
    public function store(StoreSellerFeedbackRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return ApiResponseType::sendJsonResponse(
                    false,
                    __('labels.user_not_authenticated'),
                    []
                );
            }

            $validated = $request->validated();

            // Load the order item and ensure it belongs to the user and has been delivered
            $orderItem = OrderItem::with(['order:id,user_id', 'store:id,seller_id'])
                ->find($validated['order_item_id']);

            if (!$orderItem) {
                return ApiResponseType::sendJsonResponse(false, __('labels.order_item_not_found'), []);
            }

            if ($orderItem->order->user_id !== $user->id) {
                return ApiResponseType::sendJsonResponse(false, __('labels.order_not_found_or_not_yours'), []);
            }

            if ($orderItem->status !== OrderItemStatusEnum::DELIVERED()) {
                return ApiResponseType::sendJsonResponse(false, __('labels.order_item_not_delivered'), []);
            }

            // Ensure the order item belongs to the provided seller
            if ((int)($orderItem->store->seller_id ?? 0) !== (int)$validated['seller_id']) {
                return ApiResponseType::sendJsonResponse(false, __('labels.order_item_not_from_seller'), []);
            }

            // Check if the user already provided feedback for this specific order item
            $existingFeedback = SellerFeedback::where('user_id', $user->id)
                ->where('order_item_id', $orderItem->id)
                ->first();

            if ($existingFeedback) {
                return ApiResponseType::sendJsonResponse(
                    false,
                    __('labels.already_provided_feedback_for_this_order_item'),
                    []
                );
            }

            DB::beginTransaction();

            // Generate a slug from the title
            $slug = Str::slug($validated['title']);
            $baseSlug = $slug;
            $counter = 1;

            // Ensure slug is unique
            while (SellerFeedback::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $counter++;
            }

            $feedback = SellerFeedback::create([
                'user_id' => $user->id,
                'seller_id' => $orderItem->store->seller_id,
                'order_id' => $orderItem->order_id,
                'order_item_id' => $orderItem->id,
                'store_id' => $orderItem->store_id,
                'rating' => $validated['rating'],
                'title' => $validated['title'],
                'slug' => $slug,
                'description' => $validated['description'],
            ]);

            DB::commit();

            return ApiResponseType::sendJsonResponse(
                true,
                __('labels.seller_feedback_added_successfully'),
                new SellerFeedbackResource($feedback)
            );
        } catch (AuthenticationException $e) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(
                false,
                __('labels.user_not_authenticated'),
                []
            );
        } catch (Exception $e) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(
                false,
                __('labels.something_went_wrong'),
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Display the specified seller feedback.
     */
    public function show(int $id): JsonResponse
    {
        $feedback = SellerFeedback::with('user', 'seller', 'order')->find($id);

        if (!$feedback) {
            return ApiResponseType::sendJsonResponse(
                false,
                __('labels.seller_feedback_not_found'),
                []
            );
        }

        return ApiResponseType::sendJsonResponse(
            true,
            __('labels.seller_feedback_fetched_successfully'),
            new SellerFeedbackResource($feedback)
        );
    }

    /**
     * Update the specified seller feedback.
     */
    public function update(UpdateSellerFeedbackRequest $request, int $id): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return ApiResponseType::sendJsonResponse(
                    false,
                    __('labels.user_not_authenticated'),
                    []
                );
            }

            DB::beginTransaction();

            $feedback = SellerFeedback::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$feedback) {
                return ApiResponseType::sendJsonResponse(
                    false,
                    __('labels.seller_feedback_not_found_or_unauthorized'),
                    []
                );
            }

            // Check if user owns this feedback
            $this->authorize('update', $feedback);

            $validated = $request->validated();

            // Update the slug if title is changed
            if (isset($validated['title']) && $validated['title'] !== $feedback->title) {
                $slug = Str::slug($validated['title']);
                $baseSlug = $slug;
                $counter = 1;

                // Ensure slug is unique
                while (SellerFeedback::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                    $slug = $baseSlug . '-' . $counter++;
                }

                $validated['slug'] = $slug;
            }

            $feedback->update($validated);

            DB::commit();

            return ApiResponseType::sendJsonResponse(
                true,
                __('labels.seller_feedback_updated_successfully'),
                new SellerFeedbackResource($feedback)
            );
        } catch (AuthenticationException $e) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(
                false,
                __('labels.user_not_authenticated'),
                []
            );
        } catch (Exception $e) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(
                false,
                __('labels.something_went_wrong'),
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Remove the specified seller feedback.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return ApiResponseType::sendJsonResponse(
                    false,
                    __('labels.user_not_authenticated'),
                    []
                );
            }

            $feedback = SellerFeedback::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$feedback) {
                return ApiResponseType::sendJsonResponse(
                    false,
                    __('labels.seller_feedback_not_found_or_unauthorized'),
                    []
                );
            }

            // Check if user owns this feedback
            $this->authorize('delete', $feedback);

            $feedback->delete();

            return ApiResponseType::sendJsonResponse(
                true,
                __('labels.seller_feedback_deleted_successfully'),
                []
            );
        } catch (Exception $e) {
            return ApiResponseType::sendJsonResponse(
                false,
                __('labels.something_went_wrong'),
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Get seller feedback statistics.
     */
    #[QueryParameter('seller_id', description: 'Seller ID to get ratings for.', type: 'int', required: true, example: 1)]
    public function getSellerRatings(Request $request): JsonResponse
    {
        $sellerId = $request->input('seller_id');

        if (!$sellerId) {
            return ApiResponseType::sendJsonResponse(
                false,
                __('labels.seller_id_required'),
                []
            );
        }

        // Get average rating and count
        $stats = SellerFeedback::getSellerFeedbackStatistics($sellerId);

        return ApiResponseType::sendJsonResponse(
            true,
            __('labels.seller_ratings_fetched_successfully'),
            [
                'total_reviews' => $stats->total_reviews ?? 0,
                'average_rating' => round($stats->average_rating ?? 0, 1),
                'ratings_breakdown' => [
                    '1_star' => $stats->one_star_count ?? 0,
                    '2_star' => $stats->two_star_count ?? 0,
                    '3_star' => $stats->three_star_count ?? 0,
                    '4_star' => $stats->four_star_count ?? 0,
                    '5_star' => $stats->five_star_count ?? 0,
                ],
            ]
        );
    }
}
