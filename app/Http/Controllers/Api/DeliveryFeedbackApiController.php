<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeliveryFeedback\StoreDeliveryFeedbackRequest;
use App\Http\Requests\DeliveryFeedback\UpdateDeliveryFeedbackRequest;
use App\Http\Resources\DeliveryFeedbackResource;
use App\Models\DeliveryFeedback;
use App\Models\Order;
use App\Types\Api\ApiResponseType;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

#[Group('Delivery Feedback')]
class DeliveryFeedbackApiController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of delivery feedback.
     * Optionally filter by delivery_boy_id.
     */
    #[QueryParameter('page', description: 'Page number for pagination.', type: 'int', default: 1, example: 1)]
    #[QueryParameter('per_page', description: 'Number of items per page.', type: 'int', default: 15, example: 15)]
    #[QueryParameter('delivery_boy_id', description: 'Filter by delivery boy ID.', type: 'int', example: 1)]
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $deliveryBoyId = $request->input('delivery_boy_id');

        $query = DeliveryFeedback::with('user', 'deliveryBoy', 'order');

        if ($deliveryBoyId) {
            $query->where('delivery_boy_id', $deliveryBoyId);
        }

        $feedback = $query->latest()->paginate($perPage);

        return ApiResponseType::sendJsonResponse(
            true,
            __('labels.delivery_feedback_fetched_successfully'),
            [
                'current_page' => $feedback->currentPage(),
                'last_page' => $feedback->lastPage(),
                'per_page' => $feedback->perPage(),
                'total' => $feedback->total(),
                'data' => DeliveryFeedbackResource::collection($feedback),
            ]
        );
    }

    /**
     * Store a newly created delivery feedback.
     */
    public function store(StoreDeliveryFeedbackRequest $request): JsonResponse
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

            // Check if the user already provided feedback for this delivery boy on the same order
            $existingFeedback = DeliveryFeedback::where('user_id', $user->id)
                ->where('delivery_boy_id', $validated['delivery_boy_id'])
                ->where('order_id', $validated['order_id'])
                ->first();

            if ($existingFeedback) {
                return ApiResponseType::sendJsonResponse(
                    false,
                    __('labels.already_provided_feedback_for_this_delivery_boy'),
                    []
                );
            }

            // Check if order_id is provided
            if (!isset($validated['order_id'])) {
                return ApiResponseType::sendJsonResponse(
                    false,
                    __('labels.order_id_required_for_feedback'),
                    []
                );
            }

            // Verify that the order belongs to the authenticated user
            $order = Order::where('id', $validated['order_id'])
                ->where('user_id', $user->id)
                ->first();

            if (!$order) {
                return ApiResponseType::sendJsonResponse(
                    false,
                    __('labels.order_not_found_or_not_yours'),
                    []
                );
            }

            DB::beginTransaction();

            // Generate a slug from the title
            $slug = Str::slug($validated['title']);
            $baseSlug = $slug;
            $counter = 1;

            // Ensure slug is unique
            while (DeliveryFeedback::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $counter++;
            }

            $feedback = DeliveryFeedback::create([
                'user_id' => $user->id,
                'delivery_boy_id' => $validated['delivery_boy_id'],
                'order_id' => $validated['order_id'],
                'rating' => $validated['rating'],
                'title' => $validated['title'],
                'slug' => $slug,
                'description' => $validated['description'] ?? null,
            ]);

            DB::commit();

            return ApiResponseType::sendJsonResponse(
                true,
                __('labels.delivery_feedback_added_successfully'),
                new DeliveryFeedbackResource($feedback)
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
     * Display the specified delivery feedback.
     */
    public function show(int $id): JsonResponse
    {
        $feedback = DeliveryFeedback::with('user', 'deliveryBoy', 'order')->find($id);

        if (!$feedback) {
            return ApiResponseType::sendJsonResponse(
                false,
                __('labels.delivery_feedback_not_found'),
                []
            );
        }

        return ApiResponseType::sendJsonResponse(
            true,
            __('labels.delivery_feedback_fetched_successfully'),
            new DeliveryFeedbackResource($feedback)
        );
    }

    /**
     * Update the specified delivery feedback.
     */
    public function update(UpdateDeliveryFeedbackRequest $request, int $id): JsonResponse
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

            $feedback = DeliveryFeedback::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$feedback) {
                return ApiResponseType::sendJsonResponse(
                    false,
                    __('labels.delivery_feedback_not_found_or_unauthorized'),
                    []
                );
            }

            // Check if user owns this feedback
//            $this->authorize('update', $feedback);

            $validated = $request->validated();

            // Update the slug if title is changed
            if (isset($validated['title']) && $validated['title'] !== $feedback->title) {
                $slug = Str::slug($validated['title']);
                $baseSlug = $slug;
                $counter = 1;

                // Ensure slug is unique
                while (DeliveryFeedback::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                    $slug = $baseSlug . '-' . $counter++;
                }

                $validated['slug'] = $slug;
            }

            $feedback->update($validated);

            DB::commit();

            return ApiResponseType::sendJsonResponse(
                true,
                __('labels.delivery_feedback_updated_successfully'),
                new DeliveryFeedbackResource($feedback)
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
     * Remove the specified delivery feedback.
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

            $feedback = DeliveryFeedback::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$feedback) {
                return ApiResponseType::sendJsonResponse(
                    false,
                    __('labels.delivery_feedback_not_found_or_unauthorized'),
                    []
                );
            }

            // Check if user owns this feedback
//            $this->authorize('delete', $feedback);

            $feedback->delete();

            return ApiResponseType::sendJsonResponse(
                true,
                __('labels.delivery_feedback_deleted_successfully'),
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
     * Get delivery feedback statistics.
     */
    #[QueryParameter('delivery_boy_id', description: 'Delivery Boy ID to get ratings for.', type: 'int', required: true, example: 1)]
    public function getDeliveryRatings(Request $request): JsonResponse
    {
        $deliveryBoyId = $request->input('delivery_boy_id');

        if (!$deliveryBoyId) {
            return ApiResponseType::sendJsonResponse(
                false,
                __('labels.delivery_boy_id_required'),
                []
            );
        }

        // Get average rating and count
        $stats = DeliveryFeedback::getDeliveryFeedbackStatistics($deliveryBoyId);

        return ApiResponseType::sendJsonResponse(
            true,
            __('labels.delivery_ratings_fetched_successfully'),
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
