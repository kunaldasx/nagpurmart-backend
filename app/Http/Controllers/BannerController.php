<?php

namespace App\Http\Controllers;

use App\Enums\AdminPermissionEnum;
use App\Enums\Banner\BannerPositionEnum;
use App\Enums\Banner\BannerTypeEnum;
use App\Enums\HomePageScopeEnum;
use App\Enums\Order\OrderItemStatusEnum;
use App\Enums\SpatieMediaCollectionName;
use App\Http\Requests\Banner\StoreUpdateBannerRequest;
use App\Models\Banner;
use App\Models\Category;
use App\Traits\ChecksPermissions;
use App\Traits\PanelAware;
use App\Types\Api\ApiResponseType;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BannerController extends Controller
{
    use ChecksPermissions, PanelAware, AuthorizesRequests;

    protected bool $editPermission = false;
    protected bool $deletePermission = false;
    protected bool $createPermission = false;

    public function __construct()
    {
        if ($this->getPanel() === 'admin') {
            $this->editPermission = $this->hasPermission(AdminPermissionEnum::BANNER_EDIT());
            $this->deletePermission = $this->hasPermission(AdminPermissionEnum::BANNER_DELETE());
            $this->createPermission = $this->hasPermission(AdminPermissionEnum::BANNER_CREATE());
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $columns = [
            ['data' => 'id', 'name' => 'id', 'title' => __('labels.id')],
            ['data' => 'title', 'name' => 'title', 'title' => __('labels.title')],
            ['data' => 'banner_image', 'name' => 'banner_image', 'title' => __('labels.banner_image')],
            ['data' => 'type', 'name' => 'type', 'title' => __('labels.type')],
            ['data' => 'scope_type', 'name' => 'scope_type', 'title' => __('labels.scope_type')],
            ['data' => 'position', 'name' => 'position', 'title' => __('labels.position')],
            ['data' => 'visibility_status', 'name' => 'visibility_status', 'title' => __('labels.visibility_status')],
            ['data' => 'display_order', 'name' => 'display_order', 'title' => __('labels.display_order')],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => __('labels.created_at')],
            ['data' => 'action', 'name' => 'action', 'title' => __('labels.action'), 'orderable' => false, 'searchable' => false],
        ];

        $editPermission = $this->editPermission;
        $deletePermission = $this->deletePermission;
        $createPermission = $this->createPermission;

        // Pass enum values for filters
        $bannerTypes = BannerTypeEnum::values();
        $bannerPositions = BannerPositionEnum::values();
        $scopeTypes = HomePageScopeEnum::values();

        return view($this->panelView('banners.index'), compact(
            'columns',
            'editPermission',
            'deletePermission',
            'createPermission',
            'bannerTypes',
            'bannerPositions',
            'scopeTypes'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $this->authorize('create', Banner::class);

        $bannerTypes = BannerTypeEnum::cases();
        $bannerPositions = BannerPositionEnum::cases();
        $scopeTypes = HomePageScopeEnum::values();

        return view($this->panelView('banners.form'), compact(
            'bannerTypes',
            'bannerPositions',
            'scopeTypes',
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUpdateBannerRequest $request): JsonResponse
    {
        try {
            $this->authorize('create', Banner::class);
            $validated = $request->validated();

            // Set default values if not provided
            if (empty($validated['type'])) {
                $validated['type'] = BannerTypeEnum::GENERAL();
            }
            if (empty($validated['position'])) {
                $validated['position'] = BannerPositionEnum::TOP();
            }
            if (empty($validated['visibility_status'])) {
                $validated['visibility_status'] = 'draft';
            }
            if (empty($validated['scope_type'])) {
                $validated['scope_type'] = HomePageScopeEnum::GLOBAL();
            }
            // If scope_type is global, set scope_id to null
            if ($validated['scope_type'] === HomePageScopeEnum::GLOBAL()) {
                $validated['scope_id'] = null;
            }
            DB::beginTransaction();

            $banner = Banner::create($validated);

            // Handle media uploads if present
            if ($request->hasFile('banner_image')) {
                $banner->addMediaFromRequest('banner_image')->toMediaCollection(SpatieMediaCollectionName::BANNER_IMAGE());
            }
            DB::commit();

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: 'labels.banner_created_successfully',
                data: $banner,
                status: 201
            );
        } catch (ValidationException $e) {
            DB::rollback();
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.validation_failed',
                data: $e->errors()
            );
        } catch (AuthorizationException) {
            DB::rollback();
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.permission_denied',
                data: []
            );
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id): JsonResponse
    {
        try {
            $banner = Banner::with(['product', 'category', 'brand'])->find($id);
            if (!$banner) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: 'labels.banner_not_found',
                    data: []
                );
            }

            // Add media URL if exists
            $banner->banner_image = $banner->getFirstMediaUrl('banner') ?? null;

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: 'labels.banner_retrieved_successfully',
                data: $banner
            );
        } catch (ModelNotFoundException $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.banner_not_found',
                data: []
            );
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id): View
    {
        $banner = Banner::find($id);
        if (!$banner) {
            abort(404, "Banner Not Found");
        }
        $this->authorize('update', $banner);

        $bannerTypes = BannerTypeEnum::cases();
        $bannerPositions = BannerPositionEnum::cases();
        $scopeTypes = HomePageScopeEnum::values();
        $scopeCategory = null;
        if ($banner->scope_type === 'category') {
            $scopeCategory = Category::select('id', 'title')->where('id', $banner->scope_id)->get()->first();
        }

        return view($this->panelView('banners.form'), compact(
            'banner',
            'bannerTypes',
            'bannerPositions',
            'scopeTypes',
            'scopeCategory'
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreUpdateBannerRequest $request, $id): JsonResponse
    {
        try {
            $banner = Banner::find($id);
            if (!$banner) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: 'labels.banner_not_found',
                    data: []
                );
            }
            $this->authorize('update', $banner);
            $validated = $request->validated();

            // Set default values if not provided
            if (empty($validated['visibility_status'])) {
                $validated['visibility_status'] = 'draft';
            }
            if (empty($validated['scope_type'])) {
                $validated['scope_type'] = HomePageScopeEnum::GLOBAL();
            }
            // If scope_type is global, set scope_id to null
            if ($validated['scope_type'] === HomePageScopeEnum::GLOBAL()) {
                $validated['scope_id'] = null;
            }

            $banner->update($validated);

            // Handle media uploads
            if ($request->hasFile('banner_image')) {
                $newImageFile = $request->file('banner_image');
                $existingImage = $banner->getFirstMedia('banner');

                $newImageName = $newImageFile->getClientOriginalName();
                if (!$existingImage || $existingImage->file_name !== $newImageName) {
                    $banner->addMedia($newImageFile)->toMediaCollection(SpatieMediaCollectionName::BANNER_IMAGE());
                }
            }

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: 'labels.banner_updated_successfully',
                data: $banner
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
                message: 'labels.banner_not_found',
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
        $banner = Banner::find($id);
        if (!$banner) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.banner_not_found',
                data: []
            );
        }

        $this->authorize('delete', $banner);

        try {
            // Delete associated media
            $banner->clearMediaCollection('banner');

            $banner->delete();
            return ApiResponseType::sendJsonResponse(
                success: true,
                message: 'labels.banner_deleted_successfully',
                data: []
            );
        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'labels.failed_to_delete_banner',
                data: []
            );
        }
    }

    /**
     * Get banners data for DataTables
     */
    public function getBanners(Request $request): JsonResponse
    {
        $draw = $request->get('draw');
        $start = $request->get('start');
        $length = $request->get('length');
        $searchValue = $request->get('search')['value'] ?? '';
        $type = $request->get('type');
        $position = $request->get('position');
        $visibilityStatus = $request->get('visibility_status');
        $scopeType = $request->get('scope_type');

        $orderColumnIndex = $request->get('order')[0]['column'] ?? 0;
        $orderDirection = $request->get('order')[0]['dir'] ?? 'asc';

        $columns = ['id', 'title', 'type', 'scope_type', 'position', 'visibility_status', 'display_order', 'created_at'];
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';

        $query = Banner::query()->with(['product', 'category', 'brand', 'scopeCategory']);

        // Apply filters
        if ($type !== null) {
            $query->where('type', $type);
        }
        if ($position !== null) {
            $query->where('position', $position);
        }
        if ($visibilityStatus !== null) {
            $query->where('visibility_status', $visibilityStatus);
        }
        if ($scopeType !== null) {
            $query->where('scope_type', $scopeType);
        }

        $totalRecords = Banner::count();
        $filteredRecords = $totalRecords;
        // Apply search
        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('title', 'like', "%$searchValue%")
                    ->orWhere('slug', 'like', "%$searchValue%")
                    ->orWhere('custom_url', 'like', "%$searchValue%");
            });
            $filteredRecords = $query->count();
        };


        $data = $query
            ->orderBy($orderColumn, $orderDirection)
            ->skip($start)
            ->take($length)
            ->get()
            ->map(function ($banner) {
                $scopeDisplay = $banner->scope_type;
                if ($banner->scope_type === HomePageScopeEnum::CATEGORY() && $banner->scopeCategory) {
                    $scopeDisplay .= ' (' . $banner->scopeCategory->title . ')';
                }

                return [
                    'id' => $banner->id,
                    'title' => $banner->title,
                    'type' => view('partials.status', [
                        'status' => $banner->type,
                    ])->render(),
                    'banner_image' => view('partials.image', [
                        'image' => $banner->banner_image,
                    ])->render(),
                    'scope_type' => view('partials.status', [
                        'status' => $scopeDisplay,
                    ])->render(),
                    'position' => view('partials.status', [
                        'status' => $banner->position,
                    ])->render(),
                    'visibility_status' => view('partials.status', [
                        'status' => $banner->visibility_status,
                    ])->render(),
                    'display_order' => $banner->display_order,
                    'created_at' => $banner->created_at->format('Y-m-d'),
                    'action' => view('partials.actions', ['modelName' => 'banner', 'id' => $banner->id, 'title' => $banner->title, 'mode' => 'page_view', 'editPermission' => $this->editPermission, 'deletePermission' => $this->deletePermission, 'route' => route('admin.banners.edit', ['id' => $banner->id])])->render(),
                ];
            });

        return response()->json([
            'draw' => intval($draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }
}
