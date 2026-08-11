<?php

namespace App\Http\Controllers;

use App\Enums\AdminPermissionEnum;
use App\Enums\Banner\BannerPositionEnum;
use App\Enums\Banner\BannerVisibilityStatusEnum;
use App\Enums\HomePageScopeEnum;
use App\Enums\SpatieMediaCollectionName;
use App\Http\Requests\OfferBanner\StoreUpdateOfferBannerRequest;
use App\Models\Category;
use App\Models\OfferBanner;
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

class OfferBannerController extends Controller
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

    public function index(): View
    {
        $columns = [
            ['data' => 'id', 'name' => 'id', 'title' => __('labels.id')],
            ['data' => 'title', 'name' => 'title', 'title' => __('labels.title')],
            ['data' => 'images', 'name' => 'images', 'title' => __('labels.banner_images')],
            ['data' => 'position', 'name' => 'position', 'title' => __('labels.position')],
            ['data' => 'scope_type', 'name' => 'scope_type', 'title' => __('labels.scope_type')],
            ['data' => 'visibility_status', 'name' => 'visibility_status', 'title' => __('labels.visibility_status')],
            ['data' => 'display_order', 'name' => 'display_order', 'title' => __('labels.display_order')],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => __('labels.created_at')],
            ['data' => 'action', 'name' => 'action', 'title' => __('labels.action'), 'orderable' => false, 'searchable' => false],
        ];

        $bannerPositions = BannerPositionEnum::values();
        $scopeTypes = HomePageScopeEnum::values();

        return view($this->panelView('offer-banners.index'), compact(
            'columns',
            'editPermission',
            'deletePermission',
            'createPermission',
            'bannerPositions',
            'scopeTypes'
        ));
    }

    public function create(): View
    {
        $this->authorize('create', OfferBanner::class);

        $bannerPositions = BannerPositionEnum::cases();
        $scopeTypes = HomePageScopeEnum::values();

        return view($this->panelView('offer-banners.form'), compact(
            'bannerPositions',
            'scopeTypes'
        ));
    }

    public function store(StoreUpdateOfferBannerRequest $request): JsonResponse
    {
        try {
            $this->authorize('create', OfferBanner::class);
            $validated = $request->validated();

            if (empty($validated['visibility_status'])) {
                $validated['visibility_status'] = BannerVisibilityStatusEnum::DRAFT();
            }
            if (empty($validated['scope_type'])) {
                $validated['scope_type'] = HomePageScopeEnum::GLOBAL();
            }
            if ($validated['scope_type'] === HomePageScopeEnum::GLOBAL()) {
                $validated['scope_id'] = null;
            }
            DB::beginTransaction();
            $banner = OfferBanner::create($validated);

            if ($request->hasFile('banner_images')) {
                foreach ($request->file('banner_images') as $image) {
                    $banner->addMedia($image)->toMediaCollection(SpatieMediaCollectionName::BANNER_IMAGE());
                }
            }

            DB::commit();

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: 'labels.offer_banner_created_successfully',
                data: $banner->fresh(),
                status: 201
            );
        } catch (ValidationException $e) {
            DB::rollback();
            return ApiResponseType::sendJsonResponse(success: false, message: 'labels.validation_failed', data: $e->errors());
        } catch (AuthorizationException) {
            DB::rollback();
            return ApiResponseType::sendJsonResponse(success: false, message: 'labels.permission_denied', data: []);
        }
    }

    public function edit($id): View
    {
        $banner = OfferBanner::with('scopeCategory')->find($id);
        if (!$banner) {
            abort(404, __('labels.banner_not_found'));
        }

        $this->authorize('update', $banner);

        $bannerPositions = BannerPositionEnum::cases();
        $scopeTypes = HomePageScopeEnum::values();

        return view($this->panelView('offer-banners.form'), compact(
            'banner',
            'bannerPositions',
            'scopeTypes'
        ));
    }

    public function update(StoreUpdateOfferBannerRequest $request, $id): JsonResponse
    {
        try {
            $banner = OfferBanner::find($id);
            if (!$banner) {
                return ApiResponseType::sendJsonResponse(success: false, message: 'labels.banner_not_found', data: []);
            }
            $this->authorize('update', $banner);

            $validated = $request->validated();

            if (empty($validated['visibility_status'])) {
                $validated['visibility_status'] = BannerVisibilityStatusEnum::DRAFT();
            }
            if (empty($validated['scope_type'])) {
                $validated['scope_type'] = HomePageScopeEnum::GLOBAL();
            }
            if ($validated['scope_type'] === HomePageScopeEnum::GLOBAL()) {
                $validated['scope_id'] = null;
            }

            DB::beginTransaction();
            $banner->update($validated);

            if (!empty($validated['deleted_banner_images'])) {
                foreach ($validated['deleted_banner_images'] as $mediaId) {
                    $media = $banner->media()->find($mediaId);
                    if ($media) {
                        $media->delete();
                    }
                }
            }

            if ($request->hasFile('banner_images')) {
                foreach ($request->file('banner_images') as $image) {
                    $banner->addMedia($image)->toMediaCollection(SpatieMediaCollectionName::BANNER_IMAGE());
                }
            }

            DB::commit();

            return ApiResponseType::sendJsonResponse(success: true, message: 'labels.offer_banner_updated_successfully', data: $banner->fresh());
        } catch (ValidationException $e) {
            DB::rollback();
            return ApiResponseType::sendJsonResponse(success: false, message: 'labels.validation_failed', data: $e->errors());
        } catch (AuthorizationException) {
            DB::rollback();
            return ApiResponseType::sendJsonResponse(success: false, message: 'labels.permission_denied', data: []);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $banner = OfferBanner::find($id);
            if (!$banner) {
                return ApiResponseType::sendJsonResponse(success: false, message: 'labels.banner_not_found', data: []);
            }
            $this->authorize('delete', $banner);
            $banner->delete();

            return ApiResponseType::sendJsonResponse(success: true, message: 'labels.offer_banner_deleted_successfully', data: []);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(success: false, message: 'labels.permission_denied', data: []);
        }
    }

    public function getOfferBanners(Request $request): JsonResponse
    {
        $draw = $request->get('draw');
        $start = $request->get('start');
        $length = $request->get('length');
        $searchValue = $request->get('search')['value'] ?? '';
        $position = $request->get('position');
        $visibilityStatus = $request->get('visibility_status');
        $scopeType = $request->get('scope_type');

        $orderColumnIndex = $request->get('order')[0]['column'] ?? 0;
        $orderDirection = $request->get('order')[0]['dir'] ?? 'asc';

        $columns = ['id', 'title', 'position', 'scope_type', 'visibility_status', 'display_order', 'created_at'];
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';

        $query = OfferBanner::query()->with('scopeCategory');

        if ($position !== null) {
            $query->where('position', $position);
        }
        if ($visibilityStatus !== null) {
            $query->where('visibility_status', $visibilityStatus);
        }
        if ($scopeType !== null) {
            $query->where('scope_type', $scopeType);
        }

        $totalRecords = OfferBanner::count();
        $filteredRecords = $totalRecords;

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('title', 'like', "%$searchValue%")
                    ->orWhere('slug', 'like', "%$searchValue%");
            });
            $filteredRecords = $query->count();
        }

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
                    'images' => view('partials.image', ['image' => $banner->banner_images[0]['url'] ?? ''])->render(),
                    'scope_type' => view('partials.status', ['status' => $scopeDisplay])->render(),
                    'position' => view('partials.status', ['status' => $banner->position])->render(),
                    'visibility_status' => view('partials.status', ['status' => $banner->visibility_status])->render(),
                    'display_order' => $banner->display_order,
                    'created_at' => $banner->created_at->format('Y-m-d'),
                    'action' => view('partials.actions', ['modelName' => 'offer-banner', 'id' => $banner->id, 'title' => $banner->title, 'mode' => 'page_view', 'editPermission' => $this->editPermission, 'deletePermission' => $this->deletePermission, 'route' => route('admin.offer-banners.edit', ['id' => $banner->id])])->render(),
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
