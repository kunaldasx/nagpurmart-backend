<?php

namespace App\Http\Controllers;

use App\Enums\AdminPermissionEnum;
use App\Http\Requests\TaxClass\TaxClassRequest;
use App\Models\TaxClass;
use App\Traits\ChecksPermissions;
use App\Traits\PanelAware;
use App\Types\Api\ApiResponseType;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TaxClassController extends Controller
{
    use AuthorizesRequests, ChecksPermissions, PanelAware;

    protected bool $editPermission = false;
    protected bool $deletePermission = false;
    protected bool $createPermission = false;

    public function __construct()
    {
        if ($this->getPanel() == 'admin') {
            $this->editPermission = $this->hasPermission(AdminPermissionEnum::TAX_CLASS_EDIT());
            $this->deletePermission = $this->hasPermission(AdminPermissionEnum::TAX_CLASS_DELETE());
            $this->createPermission = $this->hasPermission(AdminPermissionEnum::TAX_CLASS_CREATE());
        }
    }

    public function getTaxClasses(Request $request): JsonResponse
    {
        $this->authorize('viewAny', TaxClass::class);
        $draw = $request->get('draw');
        $start = $request->get('start');
        $length = $request->get('length');
        $searchValue = $request->get('search')['value'] ?? '';

        $orderColumnIndex = $request->get('order')[0]['column'] ?? 0;
        $orderDirection = $request->get('order')[0]['dir'] ?? 'asc';

        $columns = ['id', 'title', 'rates', 'created_at'];
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';

        $query = TaxClass::with('taxRates');

        $totalRecords = TaxClass::count();
        $filteredRecords = $totalRecords;
        if (!empty($searchValue)) {
            $filteredRecords = $query->count();
            $query->where(function ($q) use ($searchValue) {
                $q->where('title', 'like', "%$searchValue%")
                    ->orWhereHas('taxRates', function ($qr) use ($searchValue) {
                        $qr->where('title', 'like', "%$searchValue%");
                    });
            });
        }

        if ($orderColumn === 'rates') {
            $orderColumn = 'id';
        }

        $editPermission = $this->editPermission;
        $deletePermission = $this->deletePermission;

        $data = $query
            ->orderBy($orderColumn, $orderDirection)
            ->skip($start)
            ->take($length)
            ->get()
            ->map(function ($taxClass) use ($editPermission, $deletePermission) {
                return [
                    'id' => $taxClass->id,
                    'title' => $taxClass->title,
                    'rates' => $taxClass->taxRates
                        ->map(fn($rate) => $rate->title . ' (' . $rate->rate . '%)')
                        ->implode(', '),
                    'created_at' => $taxClass->created_at->format('Y-m-d'),
                    'action' => view('partials.actions', [
                        'modelName' => 'tax-class',
                        'id' => $taxClass->id,
                        'title' => $taxClass->title,
                        'mode' => 'model_view',
                        'editPermission' => $editPermission,
                        'deletePermission' => $deletePermission
                    ])->render(),
                ];
            })
            ->toArray();

        return response()->json([
            'draw' => intval($draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    public function store(TaxClassRequest $request): JsonResponse
    {
        try {
            $this->authorize('create', TaxClass::class);

            $request->validated();

            $taxClass = TaxClass::create([
                'title' => $request->title,
            ]);
            $taxClass->taxRates()->sync($request->tax_rate_ids);

            return ApiResponseType::sendJsonResponse(
                true,
                'labels.tax_class_created_successfully',
                $taxClass->load('taxRates'),
                201
            );
        } catch (AuthorizationException $e) {
            return ApiResponseType::sendJsonResponse(false, 'labels.permission_denied', []);
        }
    }

    public function show($id): JsonResponse
    {
        $taxClass = TaxClass::with('taxRates')->find($id);

        if (!$taxClass) {
            return ApiResponseType::sendJsonResponse(false, 'labels.tax_class_not_found', [], 404);
        }

        return ApiResponseType::sendJsonResponse(
            true,
            'labels.tax_class_fetched_successfully',
            $taxClass
        );
    }

    public function update(TaxClassRequest $request, $id): JsonResponse
    {
        try {
            $taxClass = TaxClass::find($id);

            if (!$taxClass) {
                return ApiResponseType::sendJsonResponse(false, 'labels.tax_class_not_found', [], 404);
            }

            $this->authorize('update', $taxClass);

            $request->validated();

            if ($request->has('title')) {
                $taxClass->title = $request->title;
            }
            $taxClass->save();

            if ($request->has('tax_rate_ids')) {
                $taxClass->taxRates()->sync($request->tax_rate_ids);
            }

            return ApiResponseType::sendJsonResponse(
                true,
                'labels.tax_class_updated_successfully',
                $taxClass->load('taxRates')
            );
        } catch (AuthorizationException $e) {
            return ApiResponseType::sendJsonResponse(false, 'labels.permission_denied', []);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $taxClass = TaxClass::find($id);
            if (!$taxClass) {
                return ApiResponseType::sendJsonResponse(false, 'labels.tax_class_not_found', [], 404);
            }

            $this->authorize('delete', $taxClass);

            $taxClass->taxRates()->detach();
            $taxClass->delete();

            return ApiResponseType::sendJsonResponse(
                true,
                'labels.tax_class_deleted_successfully',
                []
            );
        } catch (AuthorizationException $e) {
            return ApiResponseType::sendJsonResponse(false, 'labels.permission_denied', []);
        }
    }

    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q'); // Get the search query
        $exceptId = $request->input('exceptId'); // Get the search query
        $findId = $request->input('find_id'); // Specific category ID to find

        if ($findId) {
            // If find_id is set and not empty, fetch only that category
            $taxClasses = TaxClass::where('id', $findId)
                ->select('id', 'title')
                ->get();
        } else {
            // Fetch categories matching the search query
            $taxClasses = TaxClass::where('title', 'LIKE', '%' . $query . '%')
                ->select('id', 'title') // Fetch only required fields
                ->when($exceptId, function ($q) use ($exceptId) {
                    $q->where('id', '!=', $exceptId);
                })
                ->take(10) // Limit the results
                ->get();
        }
        $results = $taxClasses->map(function ($taxClass) {
            return [
                'id' => $taxClass->id,
                'value' => $taxClass->id,
                'text' => $taxClass->title,
            ];
        });
        // Return the categories as JSON
        return response()->json($results);
    }
}
