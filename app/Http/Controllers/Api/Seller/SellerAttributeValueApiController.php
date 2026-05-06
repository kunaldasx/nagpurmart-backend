<?php

namespace App\Http\Controllers\Api\Seller;

use App\Enums\Attribute\AttributeTypesEnum;
use App\Enums\SpatieMediaCollectionName;
use App\Http\Controllers\Controller;
use App\Http\Requests\AttributeValue\StoreAttributeValueRequest;
use App\Http\Requests\AttributeValue\UpdateAttributeValueRequest;
use App\Models\GlobalProductAttribute;
use App\Models\GlobalProductAttributeValue;
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

#[Group('Seller Attribute Values')]
class SellerAttributeValueApiController extends Controller
{
    use AuthorizesRequests;
    public function __construct(protected AttributeService $attributeService)
    {
    }

    /**
     * List attribute values with pagination, optionally filtered by attribute_id
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
        $attributeId = $request->input('attribute_id');
        $q = $request->input('search');

        $query = GlobalProductAttributeValue::query()
            ->whereHas('attribute', function ($q2) use ($seller) {
                $q2->where('seller_id', $seller->id);
            })
            ->with('attribute');

        if ($attributeId) {
            $query->where('global_attribute_id', $attributeId);
        }
        if ($q) {
            $query->where('title', 'like', "%$q%");
        }

        $paginator = $query->orderByDesc('id')->paginate($perPage);
        $response = [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'data' => $paginator->items(),
        ];

        return ApiResponseType::sendJsonResponse(true, __('labels.attribute_value_fetched_successfully'), $response);
    }

    /**
     * Get single attribute value
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

            $attributeValue = GlobalProductAttributeValue::where('id', $id)
                ->whereHas('attribute', function ($q) use ($seller) {
                    $q->where('seller_id', $seller->id);
                })
                ->with('attribute')
                ->firstOrFail();


            return ApiResponseType::sendJsonResponse(true, __('labels.attribute_value_fetched_successfully'), $attributeValue);
        } catch (ModelNotFoundException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.attribute_value_not_found'), null, 404);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.no_permission_view_attribute_value'), [], 403);
        }
    }

    /**
     * Create new attribute values for a specific attribute
     */
    public function store(StoreAttributeValueRequest $request): JsonResponse
    {
        try {
            $this->authorize('create', GlobalProductAttributeValue::class);

            $user = auth()->user();
            $seller = $user?->seller();
            if (!$seller) {
                return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found'), null, 404);
            }

            $validated = $request->validated();

            $created = $this->attributeService->createAttributeValues(
                attributeId: (int) $validated['attribute_id'],
                titles: $validated['values'],
                swatcheValues: $request->file('swatche_value') ?: ($validated['swatche_value'] ?? [])
            );

            return ApiResponseType::sendJsonResponse(true, __('labels.attribute_values_created_successfully'), $created, 201);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.no_permission_create_attribute_values'), [], 403);
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(false, __('labels.failed_to_save_attribute_values') . ': ' . $e->getMessage(), ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update an attribute value (and optionally replace its swatch)
     */
    public function update(UpdateAttributeValueRequest $request, $id): JsonResponse
    {
        try {
            $user = auth()->user();
            $seller = $user?->seller();
            if (!$seller) {
                return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found'), null, 404);
            }

            $attributeValue = GlobalProductAttributeValue::where('id', $id)
                ->whereHas('attribute', function ($q) use ($seller) {
                    $q->where('seller_id', $seller->id);
                })->firstOrFail();

            $this->authorize('update', $attributeValue);

            $validated = $request->validated();

            // Determine swatch type from attribute
            $attribute = GlobalProductAttribute::findOrFail($validated['attribute_id']);
            $swatcheType = $attribute->swatche_type;

            // Apply updates
            if (!empty($validated['values'])) {
                $attributeValue->title = $validated['values'][0];
            }
            $attributeValue->global_attribute_id = $validated['attribute_id'];

            if ($swatcheType === AttributeTypesEnum::IMAGE->value) {
                if ($request->hasFile('swatche_value.0')) {
                    $attributeValue->clearMediaCollection(SpatieMediaCollectionName::SWATCHE_IMAGE());
                    $attributeValue->addMediaFromRequest('swatche_value.0')->toMediaCollection(SpatieMediaCollectionName::SWATCHE_IMAGE());
                    $attributeValue->swatche_value = null;
                }
            } else {
                if (isset($validated['swatche_value'][0])) {
                    $attributeValue->swatche_value = $validated['swatche_value'][0];
                }
            }

            $attributeValue->save();

            return ApiResponseType::sendJsonResponse(true, __('labels.attribute_values_updated_successfully'), $attributeValue);
        } catch (ModelNotFoundException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.attribute_value_not_found'), null, 404);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.no_permission_update_attribute_values'), [], 403);
        } catch (\Throwable $e) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(false, __('labels.failed_to_update_attribute_values') . ': ' . $e->getMessage(), ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete attribute value
     */
    public function destroy($id): JsonResponse
    {
        try {
            $user = auth()->user();
            $seller = $user?->seller();
            if (!$seller) {
                return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found'), null, 404);
            }

            $attributeValue = GlobalProductAttributeValue::where('id', $id)
                ->whereHas('attribute', function ($q) use ($seller) {
                    $q->where('seller_id', $seller->id);
                })
                ->firstOrFail();

            $this->authorize('delete', $attributeValue);

            if ($attributeValue->productVariantAttributeValue()->exists()) {
                return ApiResponseType::sendJsonResponse(false, __('labels.attribute_value_used_in_variants'), null, 422);
            }

            $attributeValue->delete();

            return ApiResponseType::sendJsonResponse(true, __('labels.attribute_value_deleted_successfully'), null);
        } catch (ModelNotFoundException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.attribute_value_not_found'), null, 404);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.no_permission_delete_attribute_value'), [], 403);
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(false, __('labels.failed_to_delete_attribute_value'), ['error' => $e->getMessage()], 500);
        }
    }
}
