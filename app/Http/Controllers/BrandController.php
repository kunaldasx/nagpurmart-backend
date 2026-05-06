<?php

namespace App\Http\Controllers;

use App\Enums\AdminPermissionEnum;
use App\Enums\BrandStatusEnum;
use App\Enums\HomePageScopeEnum;
use App\Enums\SellerPermissionEnum;
use App\Http\Requests\Brand\StoreBrandRequest;
use App\Http\Requests\Brand\UpdateBrandRequest;
use App\Http\Resources\BrandResource;
use App\Models\Brand;
use App\Models\Seller;
use App\Traits\ChecksPermissions;
use App\Traits\PanelAware;
use App\Types\Api\ApiResponseType;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BrandController extends Controller
{
    use ChecksPermissions, PanelAware, AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    protected bool $editPermission = false;
    protected bool $deletePermission = false;
    protected bool $createPermission = false;
    public $sellerId;

    public function __construct()
    {
        $user = auth()->user();
        $seller = $user?->seller();
        $this->sellerId = $seller ? $seller->id : 0;

        if ($this->getPanel() === 'admin') {
            $this->editPermission = $this->hasPermission(AdminPermissionEnum::BRAND_EDIT());
            $this->deletePermission = $this->hasPermission(AdminPermissionEnum::BRAND_DELETE());
            $this->createPermission = $this->hasPermission(AdminPermissionEnum::BRAND_CREATE());
        }
    }

    public function index(): View
    {
        $this->authorize('viewAny', Brand::class);
        $columns = [
            ['data' => 'id', 'name' => 'id', 'title' => __('labels.id')],
            ['data' => 'title', 'name' => 'title', 'title' => __('labels.title')],
            ['data' => 'scope_type', 'name' => 'scope_type', 'title' => __('labels.scope_type')],
            ['data' => 'image', 'name' => 'image', 'title' => __('labels.image')],
            ['data' => 'status', 'name' => 'status', 'title' => __('labels.status')],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => __('labels.created_at')],
            ['data' => 'action', 'name' => 'action', 'title' => __('labels.action'), 'orderable' => false, 'searchable' => false],
        ];
        $editPermission = $this->editPermission;
        $deletePermission = $this->deletePermission;
        $createPermission = $this->createPermission;

        return view($this->panelView('brands.index'), compact('columns', 'editPermission', 'deletePermission', 'createPermission'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBrandRequest $request): JsonResponse
    {
        try {
            $this->authorize('create', Brand::class);
            $validated = $request->validated();
            if (empty($request->status)) {
                $validated['status'] = BrandStatusEnum::INACTIVE();
            }
            if (empty($validated['scope_type'])) {
                $validated['scope_type'] = HomePageScopeEnum::GLOBAL();
            }
            // If scope_type is global, set scope_id to null
            if ($validated['scope_type'] === HomePageScopeEnum::GLOBAL()) {
                $validated['scope_id'] = null;
            }
            $brand = Brand::create($validated);
            if ($request->hasFile('logo')) {
                $brand->addMediaFromRequest('logo')->toMediaCollection('brand');
            }
            return ApiResponseType::sendJsonResponse(success: true, message: 'labels.brand_created_successfully', data: $brand, status: 201);
        } catch (ValidationException $e) {
            return ApiResponseType::sendJsonResponse(success: false, message: 'labels.validation_failed', data: $e->errors());
        } catch (AuthorizationException $e) {
            return ApiResponseType::sendJsonResponse(success: false, message: 'labels.permission_denied', data: []);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id): JsonResponse
    {
        try {
            $brand = Brand::find($id);
            return ApiResponseType::sendJsonResponse(success: true, message: 'labels.brand_retrieved_successfully', data: new BrandResource($brand));
        } catch (ModelNotFoundException $e) {
            return ApiResponseType::sendJsonResponse(success: false, message: 'labels.brand_not_found', data: $e);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBrandRequest $request, $id): JsonResponse
    {
        try {
            $brand = Brand::find($id);
            $this->authorize('update', $brand);
            $validated = $request->validated();
            if (empty($request->status)) {
                $validated['status'] = BrandStatusEnum::INACTIVE();
            }
            if (empty($validated['scope_type'])) {
                $validated['scope_type'] = HomePageScopeEnum::GLOBAL();
            }
            // If scope_type is global, set scope_id to null
            if ($validated['scope_type'] === HomePageScopeEnum::GLOBAL()) {
                $validated['scope_id'] = null;
            }
            $brand->update($validated);
            if ($request->hasFile('logo')) {
                $newImageFile = $request->file('logo');
                $existingImage = $brand->getFirstMedia('brand');

                $newImageName = $newImageFile->getClientOriginalName();

                if (!$existingImage || $existingImage->file_name !== $newImageName) {
                    $brand->addMedia($newImageFile)->toMediaCollection('brand');
                }
            }
            return ApiResponseType::sendJsonResponse(
                success: true,
                message: 'labels.brand_updated_successfully',
                data: $brand
            );
        } catch (ValidationException $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.validation_failed',
                data: $e->errors()
            );
        } catch (ModelNotFoundException) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.brand_not_found',
                data: []
            );
        } catch (AuthorizationException $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.permission_denied',
                data: []
            );
        }
    }

    /**
     * Remove the specified resource from storage.
     * @throws AuthorizationException
     */
    public function destroy($id): JsonResponse
    {
        $brand = Brand::find($id);
        $this->authorize('delete', $brand);
        if (!$brand) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.brand_not_found',
                data: [],
                status: 404
            );
        }
        $brand->delete();
        return ApiResponseType::sendJsonResponse(
            success: true,
            message: 'labels.brand_deleted_successfully',
            data: []
        );
    }

    public function getBrands(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Brand::class);
        $draw = $request->get('draw');
        $start = $request->get('start');
        $length = $request->get('length');
        $searchValue = $request->get('search')['value'] ?? '';

        $orderColumnIndex = $request->get('order')[0]['column'] ?? 0;
        $orderDirection = $request->get('order')[0]['dir'] ?? 'asc';

        $columns = ['id', 'title', 'status', 'created_at'];
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';

        $query = Brand::query();

        $totalRecords = Brand::count();
        $filteredRecords = $totalRecords;
        // Search filter
        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('title', 'like', "%{$searchValue}%");
            });
            $filteredRecords = $query->count();
        }


        $data = $query
            ->orderBy($orderColumn, $orderDirection)
            ->skip($start)
            ->take($length)
            ->get()
            ->map(function ($brand) {
                $scopeDisplay = $brand->scope_type;
                if ($brand->scope_type === HomePageScopeEnum::CATEGORY() && $brand->scopeCategory) {
                    $scopeDisplay .= ' (' . $brand->scopeCategory->title . ')';
                }
                return [
                    'id' => $brand->id,
                    'title' => $brand->title,
                    'scope_type' => view('partials.status', [
                        'status' => $scopeDisplay,
                    ])->render(),
                    'image' => view('partials.image', ['image' => (!empty($brand->logo) ? $brand->logo : asset('assets/images/brand-placeholder.png')), 'title' => $brand->title])->render(),
                    'status' => view('partials.status', ['status' => $brand->status ?? ""])->render(),
                    'created_at' => $brand->created_at->format('Y-m-d'),
                    'action' => view('partials.actions', ['modelName' => 'brand', 'id' => $brand->id, 'title' => $brand->title, 'mode' => 'model_view', 'editPermission' => $this->editPermission, 'deletePermission' => $this->deletePermission])->render(),
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

    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q'); // Get the search query
        $exceptId = $request->input('exceptId'); // Get the search query
        $findId = $request->input('find_id'); // Specific brand ID to find

        if ($findId) {
            // If find_id is set and not empty, fetch only that brand
            $brands = Brand::where('id', $findId)
                ->where('status', BrandStatusEnum::ACTIVE())
                ->select('id', 'title')
                ->get();
        } else {
            // Fetch brands matching the search query
            $brands = Brand::where('title', 'LIKE', '%' . $query . '%')
                ->where('status', BrandStatusEnum::ACTIVE()) // Only active brands
                ->select('id', 'title') // Fetch only required fields
                ->when($exceptId, function ($q) use ($exceptId) {
                    $q->where('id', '!=', $exceptId);
                })
                ->get();
        }
        $results = $brands->map(function ($brand) {
            return [
                'id' => $brand->id,
                'value' => $brand->id,
                'text' => $brand->title,
            ];
        });
        // Return the categories as JSON
        return response()->json($results);
    }

    /**
     * Show bulk upload page for brands.
     */
    public function bulkUploadPage(): View
    {
        $this->authorize('create', Brand::class);
        $editPermission = $this->editPermission;
        $createPermission = $this->createPermission;
        return view($this->panelView('brands.bulk_upload'), compact('editPermission', 'createPermission'));
    }

    /**
     * Bulk upload brands via CSV.
     */
    public function bulkUpload(Request $request): JsonResponse
    {
        try {
            $this->authorize('create', Brand::class);

            $request->validate([
                'csv-file' => 'required|file|mimes:csv,txt|max:10240', // up to 10MB
            ]);

            $file = $request->file('csv-file');
            $handle = fopen($file->getRealPath(), 'r');
            if ($handle === false) {
                return ApiResponseType::sendJsonResponse(false, 'labels.file_open_error', [], 422);
            }

            $headers = fgetcsv($handle);
            if (!$headers) {
                fclose($handle);
                return ApiResponseType::sendJsonResponse(false, 'labels.csv_no_header', [], 422);
            }

            $normalizedHeaders = array_map(function ($h) {
                return strtolower(trim($h));
            }, $headers);

            // Required title field
            if (!in_array('title', $normalizedHeaders, true)) {
                fclose($handle);
                return ApiResponseType::sendJsonResponse(false, 'labels.csv_missing_title', [], 422);
            }

            $successCount = 0;
            $failed = [];
            $rowNumber = 1; // header row

            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;
                $data = [];
                foreach ($normalizedHeaders as $idx => $key) {
                    $data[$key] = $row[$idx] ?? null;
                }

                $title = trim((string)($data['title'] ?? ''));
                if ($title === '') {
                    $failed[] = ['row' => $rowNumber, 'title' => $title, 'error' => __('labels.validation_failed') . ': ' . __('validation.required', ['attribute' => 'title'])];
                    continue;
                }

                try {
                    // Unique title constraint
                    if (Brand::where('title', $title)->exists()) {
                        throw new \Exception(__('labels.title_already_exists'));
                    }

                    // Map status
                    $statusRaw = strtolower(trim((string)($data['status'] ?? '')));
                    $status = in_array($statusRaw, BrandStatusEnum::values(), true) ? $statusRaw : BrandStatusEnum::INACTIVE->value;

                    // Scope handling: scope_type [global|category], scope_id or category_title
                    $scopeTypeRaw = strtolower(trim((string)($data['scope_type'] ?? '')));
                    $scopeType = in_array($scopeTypeRaw, \App\Enums\HomePageScopeEnum::values(), true) ? $scopeTypeRaw : \App\Enums\HomePageScopeEnum::GLOBAL->value;
                    $scopeId = null;
                    if ($scopeType === \App\Enums\HomePageScopeEnum::CATEGORY->value) {
                        // Resolve category by id or title
                        if (!empty($data['scope_id']) && is_numeric($data['scope_id'])) {
                            $scopeId = (int)$data['scope_id'];
                            if (!\App\Models\Category::where('id', $scopeId)->exists()) {
                                throw new \Exception(__('labels.invalid_parent'));
                            }
                        } elseif (!empty($data['category_title'])) {
                            $cat = \App\Models\Category::where('title', trim((string)$data['category_title']))->first();
                            if ($cat) {
                                $scopeId = $cat->id;
                            } else {
                                throw new \Exception(__('labels.invalid_parent'));
                            }
                        } else {
                            // category scope but no id or title provided
                            throw new \Exception(__('labels.validation_failed'));
                        }
                    }

                    $brandData = [
                        'title' => $title,
                        'description' => $data['description'] ?? null,
                        'status' => $status,
                        'scope_type' => $scopeType,
                        'scope_id' => $scopeType === \App\Enums\HomePageScopeEnum::GLOBAL->value ? null : $scopeId,
                    ];

                    Brand::create($brandData);

                    $successCount++;
                } catch (\Throwable $e) {
                    $failed[] = [
                        'row' => $rowNumber,
                        'title' => $title,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            fclose($handle);

            $result = [
                'success_count' => $successCount,
                'failed_count' => count($failed),
                'failed_rows' => $failed,
            ];

            return ApiResponseType::sendJsonResponse(true, 'labels.bulk_upload_completed', $result, 200);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, 'labels.permission_denied', [], 403);
        } catch (ValidationException $e) {
            return ApiResponseType::sendJsonResponse(false, 'labels.validation_failed', $e->errors(), 422);
        }
    }

    /**
     * Download simple CSV template for brand bulk upload.
     */
    public function downloadTemplate()
    {
        $this->authorize('viewAny', Brand::class);
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="brand_bulk_template.csv"',
        ];

        $columns = ['title','description','status','scope_type','scope_id','category_title'];
        $sample = ['Nike','Sports brand','active','global','',''];

        $callback = function () use ($columns, $sample) {
            $output = fopen('php://output', 'w');
            fputcsv($output, $columns);
            fputcsv($output, $sample);
            fclose($output);
        };

        return response()->stream($callback, 200, $headers);
    }
}
