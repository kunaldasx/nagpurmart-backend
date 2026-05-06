<?php

namespace App\Http\Controllers;

use App\Enums\Attribute\AttributeTypesEnum;
use App\Enums\DefaultSystemRolesEnum;
use App\Enums\SellerPermissionEnum;
use App\Enums\SpatieMediaCollectionName;
use App\Http\Requests\AttributeValue\StoreAttributeValueRequest;
use App\Http\Requests\AttributeValue\UpdateAttributeValueRequest;
use App\Models\GlobalProductAttribute;
use App\Models\GlobalProductAttributeValue;
use App\Services\AttributeService;
use App\Traits\ChecksPermissions;
use App\Traits\PanelAware;
use App\Types\Api\ApiResponseType;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GlobalAttributeValueController extends Controller
{
    use PanelAware, AuthorizesRequests, ChecksPermissions;

    public float $sellerId;
    protected bool $editPermission = false;
    protected bool $deletePermission = false;
    protected bool $createPermission = false;
    protected AttributeService $attributeService;

    public function __construct(AttributeService $attributeService)
    {
        $this->attributeService = $attributeService;
        $user = auth()->user();
        $seller = $user?->seller();
        $this->sellerId = $seller ? $seller->id : 0;
        if ($this->getPanel() === 'seller') {
            $this->editPermission = $this->hasPermission(SellerPermissionEnum::ATTRIBUTE_EDIT()) || $user->hasRole(DefaultSystemRolesEnum::SELLER());
            $this->deletePermission = $this->hasPermission(SellerPermissionEnum::ATTRIBUTE_DELETE()) || $user->hasRole(DefaultSystemRolesEnum::SELLER());
            $this->createPermission = $this->hasPermission(SellerPermissionEnum::ATTRIBUTE_CREATE()) || $user->hasRole(DefaultSystemRolesEnum::SELLER());
        }
    }

    public function store(StoreAttributeValueRequest $request): JsonResponse
    {
        try {
            $this->authorize('create', GlobalProductAttributeValue::class);

            $seller = auth()->user()->seller();
            if (!$seller) {
                return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found'), null);
            }

            $validated = $request->validated();

            // Decide whether we have uploaded files (image swatches) or plain values (text/color)
            $swatcheValues = $request->file('swatche_value') ?: ($validated['swatche_value'] ?? []);

            $createdValues = $this->attributeService->createAttributeValues(
                attributeId: (int) $validated['attribute_id'],
                titles: $validated['values'],
                swatcheValues: $swatcheValues
            );

            return ApiResponseType::sendJsonResponse(
                true,
                __('labels.attribute_values_created_successfully'),
                $createdValues
            );
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(
                false,
                __('labels.no_permission_create_attribute_values'),
                [],
                403
            );
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(
                false,
                __('labels.failed_to_save_attribute_values') . ': ' . $e->getMessage(),
                ['error' => $e->getMessage()]
            );
        }
    }

    public function update(UpdateAttributeValueRequest $request, $id): JsonResponse
    {
        try {
            $attributeValue = GlobalProductAttributeValue::findOrFail($id);
            $this->authorize('update', $attributeValue);

            $seller = auth()->user()->seller();
            if (!$seller) {
                return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found'), null);
            }

            $validated = $request->validated();
            $updatedValues = [];
            $values = $validated['values'];

            // Begin transaction
            DB::beginTransaction();

            // Get the attribute to determine swatche_type
            $attribute = GlobalProductAttribute::findOrFail($validated['attribute_id']);
            $swatche_type = $attribute->swatche_type;

            // Update the existing attribute value
            $attributeValue->global_attribute_id = $validated['attribute_id'];
            $attributeValue->title = $values[0];

            // Handle swatche_value based on swatche_type
            if ($swatche_type === AttributeTypesEnum::IMAGE->value) {
                // For image type, handle with SpatieMediaService
                if (isset($validated['swatche_value'][0]) && $request->hasFile('swatche_value.0')) {
                    // Clear existing media and add new one
                    $attributeValue->clearMediaCollection(SpatieMediaCollectionName::SWATCHE_IMAGE());
                    $attributeValue->addMediaFromRequest('swatche_value.0')->toMediaCollection(SpatieMediaCollectionName::SWATCHE_IMAGE());
                    $attributeValue->swatche_value = null;
                }
            } else {
                // For text or color, update the value directly
                $attributeValue->swatche_value = $validated['swatche_value'][0];
            }

            $attributeValue->save();
            $updatedValues[] = $attributeValue;

            // Create additional values if more than one is provided
            for ($i = 1; $i < count($values); $i++) {
                $newAttributeValue = new GlobalProductAttributeValue();
                $newAttributeValue->global_attribute_id = $validated['attribute_id'];
                $newAttributeValue->title = $values[$i];

                // Handle swatche_value based on swatche_type
                if ($swatche_type === AttributeTypesEnum::IMAGE->value) {
                    // For image type, save the model first to get an ID
                    $newAttributeValue->swatche_value = null;
                    $newAttributeValue->save();

                    // Then upload the image using SpatieMediaService
                    if (isset($validated['swatche_value'][$i]) && $request->hasFile('swatche_value.' . $i)) {
                        $newAttributeValue->addMediaFromRequest('swatche_value.' . $i)->toMediaCollection(SpatieMediaCollectionName::SWATCHE_IMAGE());
                    }
                } else {
                    // For text or color, save the value directly
                    $newAttributeValue->swatche_value = $validated['swatche_value'][$i];
                    $newAttributeValue->save();
                }

                $updatedValues[] = $newAttributeValue;
            }

            DB::commit();

            return ApiResponseType::sendJsonResponse(
                true,
                __('labels.attribute_values_updated_successfully'),
                $updatedValues
            );

        } catch (ModelNotFoundException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.attribute_value_not_found'));
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(
                false,
                __('labels.no_permission_update_attribute_values'),
                [],
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(
                false,
                __('labels.failed_to_update_attribute_values') . ': ' . $e->getMessage(),
                ['error' => $e->getMessage()]
            );
        }
    }

    public function edit($id): JsonResponse
    {
        try {
            $attributeValue = GlobalProductAttributeValue::with('attribute')->find($id);
            return ApiResponseType::sendJsonResponse(
                success: true,
                message: __('labels.attribute_value_fetched_successfully'),
                data: $attributeValue
            );
        } catch (ModelNotFoundException) {
            return ApiResponseType::sendJsonResponse(success: false, message: __('labels.attribute_value_not_found'));
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('labels.failed_to_fetch_attribute_value'),
                data: ['error' => $e->getMessage()]
            );
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $attributeValue = GlobalProductAttributeValue::find($id);

            if (!$attributeValue) {
                return ApiResponseType::sendJsonResponse(false, __('labels.attribute_value_not_found'), null);
            }

            $this->authorize('delete', $attributeValue);

            $attributeValue->delete();

            return ApiResponseType::sendJsonResponse(true, __('labels.attribute_value_deleted_successfully'), null);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(
                false,
                __('labels.no_permission_delete_attribute_value'),
                [],
            );
        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(
                false,
                __('labels.failed_to_delete_attribute_value'),
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Get attributes for datatable
     */
    public function getAllAttributeValues(Request $request): JsonResponse
    {
        try {
            $draw = $request->get('draw');
            $start = $request->get('start');
            $length = $request->get('length');
            $searchValue = $request->get('search')['value'] ?? '';

            $orderColumnIndex = $request->get('order')[0]['column'] ?? 0;
            $orderDirection = $request->get('order')[0]['dir'] ?? 'asc';
            $swatcheType = $request->get('swatche_type');

            $columns = ['id', 'global_attribute_id', 'title', 'swatche_value', 'created_at'];
            $orderColumn = $columns[$orderColumnIndex] ?? 'id';
            $seller = auth()->user()->seller();
            if (!$seller) {
                return ApiResponseType::sendJsonResponse(success: false, message: __('labels.seller_not_found'), data: []);
            }
            // Get all attribute IDs for the seller
            $attributeIds = GlobalProductAttribute::where('seller_id', $seller->id)->pluck('id');
            $query = GlobalProductAttributeValue::whereIn('global_attribute_id', $attributeIds)->with('attribute');

            // Search filter
            $totalRecords = $filteredRecords = $query->count();
            if (!empty($searchValue)) {
                $query->where(function ($q) use ($searchValue) {
                    $q->where('title', 'like', "%{$searchValue}%")
                        ->orWhere('swatche_value', 'like', "%{$searchValue}%");
                    $q->orWhereHas('attribute', function ($attrQuery) use ($searchValue) {
                        $attrQuery->where('title', 'like', "%{$searchValue}%");
                    });
                });
            }

            if (!empty($swatcheType)) {
                $query->whereHas('attribute', function ($attrQuery) use ($swatcheType) {
                    $attrQuery->where('swatche_type', $swatcheType);
                });
            }
            $filteredRecords = $query->count();
            try {

                $data = $query
                    ->orderBy($orderColumn, $orderDirection)
                    ->skip($start)
                    ->take($length)
                    ->get()
                    ->map(function ($value) {
                        return [
                            'id' => $value->id,
                            'global_attribute_id' => $value->global_attribute_id,
                            'attribute_title' => optional($value->attribute)->title,
                            'title' => $value->title,
                            'swatche_value' => view('seller.global_product_attributes.partials.swatche-values', ['data' => $value])->render(),
                            'created_at' => $value->created_at->format('Y-m-d'),
                            'action' => view('partials.actions', [
                                'modelName' => 'attribute-value-create-update',
                                'id' => $value->id,
                                'title' => $value->title,
                                'mode' => 'model_view',
                                'editPermission' => $this->editPermission,
                                'deletePermission' => $this->deletePermission
                            ])->render(),
                        ];
                    })
                    ->toArray();
            } catch (\Exception $e) {
                $totalRecords = 0;
                $filteredRecords = 0;
                $data = [];
            }

            return response()->json([
                'draw' => intval($draw),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'draw' => intval($request->get('draw')),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage()
            ]);
        }
    }
}
