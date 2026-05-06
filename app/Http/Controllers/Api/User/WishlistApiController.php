<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\Wishlist\CreateWishlistRequest;
use App\Http\Requests\User\Wishlist\StoreWishlistRequest;
use App\Http\Resources\User\WishlistItemResource;
use App\Http\Resources\User\WishlistResource;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use App\Types\Api\ApiResponseType;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

#[Group('Wishlist')]
class WishlistApiController extends Controller
{
    /**
     * Get all user wishlists with their items.
     */
    #[QueryParameter('page', description: 'Page number for pagination.', type: 'int', default: 1, example: 1)]
    #[QueryParameter('per_page', description: 'Number of items per page.', type: 'int', default: 15, example: 15)]
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);

        $wishlists = Wishlist::where('user_id', auth()->id())
            ->with(['items.product', 'items.variant', 'items.store'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $wishlists->getCollection()->transform(fn($wishlist) => new WishlistResource($wishlist));

        return ApiResponseType::sendJsonResponse(
            success: true,
            message: __('labels.wishlists_fetched_successfully'),
            data: $wishlists
        );
    }

    /**
     * Get all user wishlist titles.
     */
    public function getTitles(): JsonResponse
    {
        $wishlists = Wishlist::where('user_id', auth()->id())
            ->withCount('items')
            ->orderBy('created_at', 'desc')
            ->get(['id', 'title', 'slug', 'created_at'])
            ->map(function ($wishlist) {
                return [
                    'id' => $wishlist->id,
                    'title' => $wishlist->title,
                    'slug' => $wishlist->slug,
                    'items_count' => $wishlist->items_count,
                    'created_at' => $wishlist->created_at?->format('Y-m-d H:i:s'),
                ];
            });

        return ApiResponseType::sendJsonResponse(
            success: true,
            message: __('labels.wishlist_titles_fetched_successfully'),
            data: $wishlists
        );
    }


    /**
     * Get a specific wishlist by ID.
     */
    #[QueryParameter('id', description: 'Wishlist ID.', type: 'int', example: 1)]
    #[QueryParameter('page', description: 'Page number for pagination.', type: 'int', default: 1, example: 1)]
    #[QueryParameter('per_page', description: 'Number of items per page.', type: 'int', default: 15, example: 15)]
    #[QueryParameter('latitude', description: 'User latitude for location-based filtering.', type: 'float', example: '23.23232801')]
    #[QueryParameter('longitude', description: 'User longitude for location-based filtering.', type: 'float', example: '69.64359362')]
    public function show($id, Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);

        $wishlist = Wishlist::where('user_id', auth()->id())
            ->with(['items' => function ($query) use ($perPage) {
                $query->with(['product', 'variant', 'store'])
                    ->orderBy('created_at', 'desc')
                    ->paginate($perPage);
            }])
            ->find($id);

        if (!$wishlist) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.wishlist_not_found'),
                data: []
            );
        }
        $wishlist->user_latitude = $request->input('latitude');
        $wishlist->user_longitude = $request->input('longitude');

        return ApiResponseType::sendJsonResponse(
            success: true,
            message: __('labels.wishlist_fetched_successfully'),
            data: new WishlistResource($wishlist)
        );
    }

    /**
     * Create a new wishlist or add item to the existing / new wishlist.
     */
    public function store(StoreWishlistRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();
            $validatedData = $request->validated();

            $wishlistTitle = $validatedData['wishlist_title'] ?? 'Favorite';
            $wishlist = Wishlist::where(['title'=> $wishlistTitle, 'user_id' => auth()->id()])->first();
            if ($wishlist == null) {
                $wishlist = Wishlist::create([
                    'user_id' => auth()->id(),
                    'title' => $wishlistTitle,
                ]);
            }
            $responseData = new WishlistResource($wishlist->load(['items.product', 'items.variant', 'items.store']));
            $message = __('labels.wishlist_created_successfully');

            // If product_id is provided, add item to wishlist
            if ($validatedData['product_id']) {
                // Check if an item already exists in the wishlist
                $existingItem = WishlistItem::where('wishlist_id', $wishlist->id)
                    ->where('product_id', $validatedData['product_id'])
                    ->where('product_variant_id', $validatedData['product_variant_id'] ?? null)
                    ->where('store_id', $validatedData['store_id'])
                    ->first();

                if ($existingItem) {
                    return ApiResponseType::sendJsonResponse(
                        success: false,
                        message: __('labels.item_already_in_wishlist'),
                        data: []
                    );
                }

                // Add item to the wishlist
                $wishlistItem = WishlistItem::create([
                    'wishlist_id' => $wishlist->id,
                    'product_id' => $validatedData['product_id'],
                    'product_variant_id' => $validatedData['product_variant_id'] ?? null,
                    'store_id' => $validatedData['store_id'],
                ]);

                $message = $wishlist->wasRecentlyCreated
                    ? __('labels.wishlist_created_and_item_added')
                    : __('labels.item_added_to_wishlist');

                $responseData = new WishlistItemResource($wishlistItem->load(['product', 'variant', 'store', 'wishlist']));
            }
            DB::commit();

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: $message,
                data: $responseData
            );

        } catch (ValidationException $e) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.validation_error'),
                data: $e->errors()
            );
        } catch (Exception $e) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.something_went_wrong'),
                data: $e
            );
        }
    }

    /**
     * Update a wishlist.
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $request->validate([
                'title' => 'required|string|max:255',
            ]);

            $wishlist = Wishlist::where('user_id', auth()->id())->find($id);

            if (!$wishlist) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: __('labels.wishlist_not_found'),
                    data: []
                );
            }

            $wishlist->update([
                'title' => $request->title,
            ]);

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: __('labels.wishlist_updated_successfully'),
                data: new WishlistResource($wishlist->load(['items.product', 'items.variant', 'items.store']))
            );
        } catch (ValidationException $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.validation_error'),
                data: $e->errors()
            );
        }
    }

    /**
     * Create a new wishlist without items.
     */
    public function createWishlist(CreateWishlistRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();
            $validatedData = $request->validated();

            $title = $validatedData['title'];

            // Check if a wishlist with the same slug already exists for this user
            $existingWishlist = Wishlist::where(['title'=> $title, 'user_id' => auth()->id()])->first();
            if ($existingWishlist) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: __('labels.wishlist_already_exists'),
                    data: []
                );
            }

            // Create the wishlist
            $wishlist = Wishlist::create([
                'user_id' => auth()->id(),
                'title' => $title,
            ]);

            DB::commit();

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: __('labels.wishlist_created_successfully'),
                data: new WishlistResource($wishlist)
            );

        } catch (ValidationException $e) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.validation_error'),
                data: $e->errors()
            );
        } catch (Exception $e) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.something_went_wrong'),
                data: $e
            );
        }
    }

    /**
     * Delete a wishlist.
     */
    public function destroy($id): JsonResponse
    {
        $wishlist = Wishlist::where('user_id', auth()->id())->find($id);

        if (!$wishlist) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.wishlist_not_found'),
                data: []
            );
        }

        // Don't allow deletion of default "favorite" wishlist
        if (strtolower($wishlist->slug) === 'favorite') {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.cannot_delete_default_wishlist'),
                data: []
            );
        }

        $wishlist->delete();

        return ApiResponseType::sendJsonResponse(
            success: true,
            message: __('labels.wishlist_deleted_successfully'),
            data: []
        );
    }

    /**
     * Remove item from wishlist.
     */
    public function removeItem($itemId): JsonResponse
    {
        $wishlistItem = WishlistItem::whereHas('wishlist', function ($query) {
            $query->where('user_id', auth()->id());
        })->find($itemId);

        if (!$wishlistItem) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.wishlist_item_not_found'),
                data: []
            );
        }

        $wishlistItem->delete();

        return ApiResponseType::sendJsonResponse(
            success: true,
            message: __('labels.item_removed_from_wishlist'),
            data: []
        );
    }

    /**
     * Move item to another wishlist.
     */
    public function moveItem(Request $request, $itemId): JsonResponse
    {
        try {
            $request->validate([
                'target_wishlist_id' => 'required|exists:wishlists,id',
            ]);

            $wishlistItem = WishlistItem::whereHas('wishlist', function ($query) {
                $query->where('user_id', auth()->id());
            })->find($itemId);

            if (!$wishlistItem) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: __('labels.wishlist_item_not_found'),
                    data: []
                );
            }

            // Check if target wishlist belongs to user
            $targetWishlist = Wishlist::where('user_id', auth()->id())
                ->find($request->target_wishlist_id);

            if (!$targetWishlist) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: __('labels.target_wishlist_not_found'),
                    data: []
                );
            }

            // Check if item already exists in target wishlist
            $existingItem = WishlistItem::where('wishlist_id', $targetWishlist->id)
                ->where('product_id', $wishlistItem->product_id)
                ->where('product_variant_id', $wishlistItem->product_variant_id)
                ->where('store_id', $wishlistItem->store_id)
                ->first();

            if ($existingItem) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: __('labels.item_already_in_target_wishlist'),
                    data: []
                );
            }

            $wishlistItem->update(['wishlist_id' => $request->target_wishlist_id]);

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: __('labels.item_moved_successfully'),
                data: new WishlistItemResource($wishlistItem->load(['product', 'variant', 'store', 'wishlist']))
            );
        } catch (ValidationException $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.validation_error'),
                data: $e->errors()
            );
        }
    }
}
