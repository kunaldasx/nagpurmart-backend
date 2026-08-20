<?php

namespace App\Http\Controllers;

use App\Http\Requests\OfferBanner\StoreUpdateOfferBannerRequest;
use App\Models\Category;
use App\Models\OfferBanner;
use App\Models\OfferBannerItem;
use App\Traits\PanelAware;
use App\Traits\ChecksPermissions;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OfferBannerController extends Controller
{
    use ChecksPermissions, PanelAware, AuthorizesRequests;

    protected bool $editPermission = false;
    protected bool $deletePermission = false;
    protected bool $createPermission = false;

    public function __construct()
    {
        if ($this->getPanel() === 'admin') {
            $this->editPermission = $this->hasPermission('offer_banner.edit');
            $this->deletePermission = $this->hasPermission('offer_banner.delete');
            $this->createPermission = $this->hasPermission('offer_banner.create');
        }
    }

    public function index(): View
    {
        $columns = [
            ['data' => 'id', 'name' => 'id', 'title' => __('labels.id')],
            ['data' => 'title', 'name' => 'title', 'title' => __('labels.title')],
            ['data' => 'banner_images', 'name' => 'banner_images', 'title' => __('labels.banner_images')],
            ['data' => 'position', 'name' => 'position', 'title' => __('labels.position')],
            ['data' => 'scope_type', 'name' => 'scope_type', 'title' => __('labels.scope_type')],
            ['data' => 'visibility_status', 'name' => 'visibility_status', 'title' => __('labels.visibility_status')],
            ['data' => 'display_order', 'name' => 'display_order', 'title' => __('labels.display_order')],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => __('labels.created_at')],
            ['data' => 'action', 'name' => 'action', 'title' => __('labels.action'), 'orderable' => false, 'searchable' => false],
        ];

        $scopeTypes = ['global', 'category'];
        $editPermission = $this->editPermission;
        $deletePermission = $this->deletePermission;
        $createPermission = $this->createPermission;

        return view($this->panelView('offer-banners.index'), compact('columns', 'scopeTypes', 'editPermission', 'deletePermission', 'createPermission'));
    }

    public function create(): View
    {
        if ($this->getPanel() === 'admin' && !$this->createPermission) abort(403);
        $scopeTypes = ['global', 'category'];
        return view($this->panelView('offer-banners.form'), compact('scopeTypes'));
    }

    public function store(StoreUpdateOfferBannerRequest $request): JsonResponse
    {
        if ($this->getPanel() === 'admin' && !$this->createPermission) return $this->unauthorizedResponse();
        $validated = $request->validated();
        if (empty($validated['visibility_status'])) $validated['visibility_status'] = 'draft';
        if (empty($validated['scope_type'])) $validated['scope_type'] = 'global';
        if ($validated['scope_type'] === 'global') $validated['scope_id'] = null;

        DB::beginTransaction();
        try {
            $banner = OfferBanner::create($validated);

            // Items
            if (!empty($validated['offer_items']) && is_array($validated['offer_items'])) {
                foreach ($validated['offer_items'] as $item) {
                    $title = trim($item['title'] ?? '');
                    $itemId = $item['item_id'] ?? null;
                    if (empty($title) && empty($itemId)) continue; // skip empty rows
                    $banner->items()->create([
                        'title' => $title ?: null,
                        'subtitle' => trim($item['subtitle'] ?? '') ?: null,
                        'item_type' => $item['item_type'] ?? null,
                        'item_id' => $itemId ?: null,
                    ]);
                }
            }

            // Images
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $banner->addMedia($file)->toMediaCollection('offer_banner_images');
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => __('labels.offer_banner_created_successfully'), 'data' => ['id' => $banner->id, 'redirect_url' => route('admin.offer-banners.index')]], 201);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => __('labels.failed_to_create_offer_banner'), 'error' => $e->getMessage()], 500);
        }
    }

    public function edit($id): View
    {
        $banner = OfferBanner::with('items')->findOrFail($id);
        $scopeTypes = ['global', 'category'];
        $scopeCategory = null;
        if ($banner->scope_type === 'category') {
            $scopeCategory = Category::select('id', 'title')->where('id', $banner->scope_id)->first();
        }
        return view($this->panelView('offer-banners.form'), compact('banner', 'scopeTypes', 'scopeCategory'));
    }

    public function update(StoreUpdateOfferBannerRequest $request, $id): JsonResponse
    {
        if ($this->getPanel() === 'admin' && !$this->editPermission) return $this->unauthorizedResponse();
        $banner = OfferBanner::findOrFail($id);
        $validated = $request->validated();
        if (empty($validated['visibility_status'])) $validated['visibility_status'] = 'draft';
        if (empty($validated['scope_type'])) $validated['scope_type'] = 'global';
        if ($validated['scope_type'] === 'global') $validated['scope_id'] = null;

        DB::beginTransaction();
        try {
            $banner->update($validated);

            // Replace items
            $banner->items()->delete();
            if (!empty($validated['offer_items']) && is_array($validated['offer_items'])) {
                foreach ($validated['offer_items'] as $item) {
                    $title = trim($item['title'] ?? '');
                    $itemId = $item['item_id'] ?? null;
                    if (empty($title) && empty($itemId)) continue; // skip empty rows
                    $banner->items()->create([
                        'title' => $title ?: null,
                        'subtitle' => trim($item['subtitle'] ?? '') ?: null,
                        'item_type' => $item['item_type'] ?? null,
                        'item_id' => $itemId ?: null,
                    ]);
                }
            }

            // Images
            if ($request->hasFile('images')) {
                // clear existing and upload new
                $banner->clearMediaCollection('offer_banner_images');
                foreach ($request->file('images') as $file) {
                    $banner->addMedia($file)->toMediaCollection('offer_banner_images');
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => __('labels.offer_banner_updated_successfully'), 'data' => ['id' => $banner->id, 'redirect_url' => route('admin.offer-banners.index')]]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => __('labels.failed_to_update_offer_banner'), 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        if ($this->getPanel() === 'admin' && !$this->deletePermission) return $this->unauthorizedResponse();
        $banner = OfferBanner::find($id);
        if (!$banner) return response()->json(['success' => false, 'message' => __('labels.offer_banner_not_found')], 404);
        $banner->clearMediaCollection('offer_banner_images');
        $banner->delete();
        return response()->json(['success' => true, 'message' => __('labels.offer_banner_deleted_successfully')]);
    }

    public function getOfferBanners(Request $request): JsonResponse
    {
        $draw = $request->get('draw');
        $start = $request->get('start');
        $length = $request->get('length');
        $searchValue = $request->get('search')['value'] ?? '';
        $scopeType = $request->get('scope_type');

        $columns = ['id', 'title', 'position', 'scope_type', 'visibility_status', 'display_order', 'created_at'];
        $orderColumnIndex = $request->get('order')[0]['column'] ?? 0;
        $orderDirection = $request->get('order')[0]['dir'] ?? 'asc';
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';

        $query = OfferBanner::query()->with('items');
        if ($scopeType !== null) {
            $query->where('scope_type', $scopeType);
        }
        if (!empty($searchValue)) {
            $query->where('title', 'like', "%$searchValue%");
        }

        $totalRecords = OfferBanner::count();
        $filtered = $query->count();

        $data = $query->orderBy($orderColumn, $orderDirection)->skip($start)->take($length)->get()->map(function ($b) {
            return [
                'id' => $b->id,
                'title' => $b->title,
                'banner_images' => view('partials.image', ['image' => $b->getFirstMediaUrl('offer_banner_images')])->render(),
                'position' => view('partials.status', ['status' => $b->position])->render(),
                'scope_type' => view('partials.status', ['status' => $b->scope_type])->render(),
                'visibility_status' => view('partials.status', ['status' => $b->visibility_status])->render(),
                'display_order' => $b->display_order,
                'created_at' => $b->created_at->format('Y-m-d'),
                'action' => view('partials.actions', ['modelName' => 'offer-banner', 'id' => $b->id, 'title' => $b->title, 'mode' => 'page_view', 'editPermission' => true, 'deletePermission' => true, 'route' => route('admin.offer-banners.edit', ['id' => $b->id])])->render(),
            ];
        });

        return response()->json(['draw' => intval($draw), 'recordsTotal' => $totalRecords, 'recordsFiltered' => $filtered, 'data' => $data]);
    }
}
