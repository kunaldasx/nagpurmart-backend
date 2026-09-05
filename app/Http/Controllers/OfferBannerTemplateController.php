<?php

namespace App\Http\Controllers;

use App\Http\Requests\OfferBanner\StoreUpdateOfferBannerTemplateRequest;
use App\Models\OfferBannerTemplate;
use App\Traits\ChecksPermissions;
use App\Traits\PanelAware;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class OfferBannerTemplateController extends Controller
{
    use ChecksPermissions, PanelAware;

    protected bool $editPermission = false;
    protected bool $deletePermission = false;
    protected bool $createPermission = false;

    public function __construct()
    {
        $this->editPermission = $this->hasPermission('offer_banner.edit');
        $this->deletePermission = $this->hasPermission('offer_banner.delete');
        $this->createPermission = $this->hasPermission('offer_banner.create');
    }

    public function index(): View
    {
        if (!$this->editPermission && !$this->createPermission) abort(403);
        $templates = OfferBannerTemplate::orderBy('display_order')->orderBy('id')->get();
        return view($this->panelView('offer-banner-templates.index'), compact('templates'));
    }

    public function create(): View
    {
        if (!$this->createPermission) abort(403);
        return view($this->panelView('offer-banner-templates.form'));
    }

    public function store(StoreUpdateOfferBannerTemplateRequest $request): JsonResponse
    {
        if (!$this->createPermission) return $this->unauthorizedResponse();
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active');
        $validated['preview_path'] = $request->file('preview_image')->store('offer-banner-templates', 'public');
        unset($validated['preview_image']);

        $template = OfferBannerTemplate::create($validated);
        return response()->json(['success' => true, 'message' => 'Template created successfully.', 'data' => ['id' => $template->id, 'redirect_url' => route('admin.offer-banner-templates.index')]], 201);
    }

    public function edit($id): View
    {
        if (!$this->editPermission) abort(403);
        $template = OfferBannerTemplate::findOrFail($id);
        return view($this->panelView('offer-banner-templates.form'), compact('template'));
    }

    public function update(StoreUpdateOfferBannerTemplateRequest $request, $id): JsonResponse
    {
        if (!$this->editPermission) return $this->unauthorizedResponse();
        $template = OfferBannerTemplate::findOrFail($id);
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('preview_image')) {
            if (!str_starts_with($template->preview_path, 'assets/')) {
                Storage::disk('public')->delete($template->preview_path);
            }
            $validated['preview_path'] = $request->file('preview_image')->store('offer-banner-templates', 'public');
        }

        unset($validated['preview_image']);
        $template->update($validated);
        return response()->json(['success' => true, 'message' => 'Template updated successfully.', 'data' => ['id' => $template->id, 'redirect_url' => route('admin.offer-banner-templates.index')]]);
    }

    public function destroy($id): JsonResponse
    {
        if (!$this->deletePermission) return $this->unauthorizedResponse();
        $template = OfferBannerTemplate::findOrFail($id);
        if (OfferBanner::where('template_code', $template->code)->exists()) {
            return response()->json(['success' => false, 'message' => 'This template is used by one or more offer banners. Deactivate it instead.'], 422);
        }
        if (!str_starts_with($template->preview_path, 'assets/')) {
            Storage::disk('public')->delete($template->preview_path);
        }
        $template->delete();
        return response()->json(['success' => true, 'message' => 'Template deleted successfully.', 'data' => ['redirect_url' => route('admin.offer-banner-templates.index')]]);
    }
}