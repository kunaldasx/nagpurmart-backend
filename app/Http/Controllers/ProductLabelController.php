<?php

namespace App\Http\Controllers;

use App\Enums\AdminPermissionEnum;
use App\Http\Requests\ProductLabelRequest;
use App\Models\ProductLabel;
use App\Traits\ChecksPermissions;
use App\Traits\PanelAware;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProductLabelController extends Controller
{
    use ChecksPermissions, PanelAware;

    public function index(): View
    {
        $this->ensurePermission();

        return view($this->panelView('product-labels.index'), [
            'labels' => ProductLabel::query()->orderBy('name')->get(),
            'label' => null,
        ]);
    }

    public function edit(int $id): View
    {
        $this->ensurePermission();

        return view($this->panelView('product-labels.index'), [
            'labels' => ProductLabel::query()->orderBy('name')->get(),
            'label' => ProductLabel::findOrFail($id),
        ]);
    }

    public function store(ProductLabelRequest $request): RedirectResponse
    {
        $this->ensurePermission();
        ProductLabel::create($request->validated());

        return redirect()->route('admin.product-labels.index')->with('success', 'Product label created successfully.');
    }

    public function update(ProductLabelRequest $request, int $id): RedirectResponse
    {
        $this->ensurePermission();
        ProductLabel::findOrFail($id)->update($request->validated());

        return redirect()->route('admin.product-labels.index')->with('success', 'Product label updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->ensurePermission();
        $label = ProductLabel::findOrFail($id);
        $label->products()->update([
            'label' => null,
            'product_label_id' => null,
        ]);
        $label->delete();

        return redirect()->route('admin.product-labels.index')->with('success', 'Product label deleted successfully.');
    }

    private function ensurePermission(): void
    {
        abort_unless($this->hasPermission(AdminPermissionEnum::PRODUCT_VIEW()), 403);
    }
}
