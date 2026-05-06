<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attribute\StoreAttributeRequest;
use App\Http\Requests\Attribute\UpdateAttributeRequest;
use App\Models\GlobalProductAttribute;
use App\Services\AttributeService;
use App\Types\Api\ApiResponseType;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

#[Group('Seller Attributes')]
class SellerAttributeApiController extends Controller
{
    use AuthorizesRequests;
    public function __construct(protected AttributeService $attributeService)
    {
    }

    /**
     * List attributes for the authenticated seller with pagination
     */
    #[QueryParameter('page', description: 'Page number for pagination.', type: 'int', default: 1, example: 1)]
    #[QueryParameter('per_page', description: 'Number of brands per page', type: 'int', default: 15, example: 15)]
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', GlobalProductAttribute::class);
        $user = auth()->user();
        $seller = $user?->seller();
        if (!$seller) {
            return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found'), null, 404);
        }

        $perPage = (int) $request->input('per_page', 15);
        $q = $request->input('search');

        $query = GlobalProductAttribute::where('seller_id', $seller->id)
            ->withCount('values');

        if ($q) {
            $query->where(function ($sub) use ($q) {
                $sub->where('title', 'like', "%$q%")
                    ->orWhere('label', 'like', "%$q%");
            });
        }

        $paginator = $query->orderByDesc('id')->paginate($perPage);
        $response = [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'data' => $paginator->items(),
        ];

        return ApiResponseType::sendJsonResponse(true, __('labels.attribute_fetched_successfully'), $response);
    }

    /**
     * Get a single attribute
     */
    public function show($id): JsonResponse
    {
        try {
            $this->authorize('viewAny', GlobalProductAttribute::class);
            $user = auth()->user();
            $seller = $user?->seller();
            if (!$seller) {
                return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found'), null, 404);
            }

            $attribute = GlobalProductAttribute::where('seller_id', $seller->id)
                ->with('values')
                ->findOrFail($id);

            return ApiResponseType::sendJsonResponse(true, __('labels.attribute_fetched_successfully'), $attribute);
        } catch (ModelNotFoundException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.attribute_not_found'), null, 404);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.no_permission_view_attribute'), [], 403);
        }
    }

    /**
     * Create a new attribute for the authenticated seller
     */
    public function store(StoreAttributeRequest $request): JsonResponse
    {
        try {
            $this->authorize('create', GlobalProductAttribute::class);

            $user = auth()->user();
            $seller = $user?->seller();
            if (!$seller) {
                return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found'), null, 404);
            }

            $attribute = $this->attributeService->createAttribute($request->validated(), $seller->id);

            return ApiResponseType::sendJsonResponse(true, __('labels.attribute_created_successfully'), $attribute, 201);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.no_permission_create_attributes'), [], 403);
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(false, __('labels.failed_to_save_attribute'), ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update attribute
     */
    public function update(UpdateAttributeRequest $request, $id): JsonResponse
    {
        try {
            $user = auth()->user();
            $seller = $user?->seller();
            if (!$seller) {
                return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found'), null, 404);
            }

            $attribute = GlobalProductAttribute::where('seller_id', $seller->id)->findOrFail($id);
            $this->authorize('update', $attribute);

            $validated = $request->validated();
            $attribute->update($validated);

            return ApiResponseType::sendJsonResponse(true, __('labels.attribute_updated_successfully'), $attribute);
        } catch (ModelNotFoundException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.attribute_not_found'), null, 404);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.no_permission_update_attribute'), [], 403);
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(false, __('labels.failed_to_update_attribute'), ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete attribute and its values (if not used)
     */
    public function destroy($id): JsonResponse
    {
        try {
            $user = auth()->user();
            $seller = $user?->seller();
            if (!$seller) {
                return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found'), null, 404);
            }

            $attribute = GlobalProductAttribute::where('seller_id', $seller->id)->findOrFail($id);
            $this->authorize('delete', $attribute);

            if ($attribute->productVariantAttribute()->exists()) {
                return ApiResponseType::sendJsonResponse(false, __('labels.attribute_used_in_variants'), null, 422);
            }

            DB::beginTransaction();
            $attribute->values()->delete();
            $attribute->delete();
            DB::commit();

            return ApiResponseType::sendJsonResponse(true, __('labels.attribute_and_values_deleted'), null);
        } catch (ModelNotFoundException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.attribute_not_found'), null, 404);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.no_permission_delete_attribute'), [], 403);
        } catch (\Throwable $e) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(false, __('labels.failed_to_delete_attribute_and_values'), ['error' => $e->getMessage()], 500);
        }
    }
}
