<?php

namespace App\Http\Controllers;

use App\Enums\AdminPermissionEnum;
use App\Models\CatalogProductRequest;
use App\Services\CatalogProductRequestService;
use App\Traits\ChecksPermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogProductRequestController extends Controller
{
    use ChecksPermissions;

    public function sellerIndex(Request $request): View
    {
        $seller = $request->user()?->seller();
        abort_unless($seller, 404, 'Seller not found.');

        $requests = CatalogProductRequest::query()
            ->latest()
            ->paginate(15);

        return view('seller.catalog_product_requests.index', compact('requests'));
    }

    public function sellerStore(Request $request, CatalogProductRequestService $service): RedirectResponse
    {
        $seller = $request->user()?->seller();
        abort_unless($seller, 404, 'Seller not found.');

        $validated = $request->validate($this->requestRules());
        $service->submit($seller, $request->user(), $validated);

        return redirect()->route('seller.product-requests.index')
            ->with('success', 'Product request submitted successfully.');
    }

    public function adminIndex(): View
    {
        abort_unless($this->hasPermission(AdminPermissionEnum::PRODUCT_VIEW()), 403);

        $requests = CatalogProductRequest::query()
            ->with(['seller.user', 'user', 'processor'])
            ->latest()
            ->paginate(25);
        $canProcess = $this->hasPermission(AdminPermissionEnum::PRODUCT_STATUS_UPDATE());

        return view('admin.catalog_product_requests.index', compact('requests', 'canProcess'));
    }

    public function decide(Request $request, int $id): RedirectResponse
    {
        abort_unless($this->hasPermission(AdminPermissionEnum::PRODUCT_STATUS_UPDATE()), 403);

        $validated = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'admin_remark' => ['nullable', 'string', 'max:2000'],
        ]);

        $productRequest = CatalogProductRequest::findOrFail($id);
        if ($productRequest->status !== 'pending') {
            return back()->with('error', 'This product request has already been processed.');
        }

        $productRequest->update([
            'status' => $validated['status'],
            'admin_remark' => $validated['admin_remark'] ?? null,
            'processed_by' => $request->user()->id,
            'processed_at' => now(),
        ]);

        return redirect()->route('admin.product-requests.index')
            ->with('success', 'Product request updated successfully.');
    }

    private function requestRules(): array
    {
        return [
            'product_name' => ['required', 'string', 'max:255'],
            'brand_name' => ['nullable', 'string', 'max:255'],
            'category_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'reference_url' => ['nullable', 'url', 'max:2048'],
            'request_note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}