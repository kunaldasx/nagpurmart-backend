<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Seller;
use App\Models\Store;
use App\Models\StoreProductExpiryBatch;
use App\Models\StoreProductVariant;
use App\Services\ProductExpiryBatchService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ExpiryBatchManagementController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly ProductExpiryBatchService $batchService)
    {
    }

    public function adminIndex(Request $request): View
    {
        $this->authorize('viewAny', Product::class);

        return $this->index($request, true);
    }

    public function sellerIndex(Request $request): View
    {
        $this->authorize('viewAny', Product::class);
        $seller = $request->user()?->seller();
        abort_if(!$seller, 404, __('labels.seller_not_found'));

        return $this->index($request, false, (int) $seller->id);
    }

    public function adminReceive(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', Product::class);

        return $this->receive($request);
    }

    public function sellerReceive(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', Product::class);
        $seller = $request->user()?->seller();
        abort_if(!$seller, 404, __('labels.seller_not_found'));

        return $this->receive($request, (int) $seller->id);
    }

    public function adminUpdate(Request $request, int $batchId): RedirectResponse
    {
        $this->authorize('viewAny', Product::class);

        return $this->update($request, $batchId);
    }

    public function sellerUpdate(Request $request, int $batchId): RedirectResponse
    {
        $this->authorize('viewAny', Product::class);
        $seller = $request->user()?->seller();
        abort_if(!$seller, 404, __('labels.seller_not_found'));

        return $this->update($request, $batchId, (int) $seller->id);
    }

    private function index(Request $request, bool $isAdmin, ?int $sellerId = null): View
    {
        $filters = $request->validate([
            'status' => ['nullable', 'in:all,expired,expiring,safe,depleted'],
            'days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'seller_id' => ['nullable', 'integer', 'exists:sellers,id'],
            'store_id' => ['nullable', 'integer', 'exists:stores,id'],
            'search' => ['nullable', 'string', 'max:120'],
        ]);
        $days = (int) ($filters['days'] ?? ProductExpiryBatchService::DEFAULT_WARNING_DAYS);
        $sellerId ??= $isAdmin && !empty($filters['seller_id']) ? (int) $filters['seller_id'] : null;
        $query = $this->batchQuery($sellerId);
        $this->applyFilters($query, $filters, $days);

        $today = today()->toDateString();
        $cutoff = today()->addDays($days)->toDateString();
        $activeBatches = (clone $query)->where('quantity_remaining', '>', 0);
        $summary = [
            'expired_batches' => (clone $activeBatches)->whereDate('expiry_date', '<', $today)->count(),
            'expired_units' => (clone $activeBatches)->whereDate('expiry_date', '<', $today)->sum('quantity_remaining'),
            'expiring_batches' => (clone $activeBatches)->whereDate('expiry_date', '>=', $today)->whereDate('expiry_date', '<=', $cutoff)->count(),
            'expiring_units' => (clone $activeBatches)->whereDate('expiry_date', '>=', $today)->whereDate('expiry_date', '<=', $cutoff)->sum('quantity_remaining'),
            'safe_batches' => (clone $activeBatches)->whereDate('expiry_date', '>', $cutoff)->count(),
        ];
        $trackedStock = DB::table('store_product_expiry_batches')
            ->select('store_product_variant_id')
            ->selectRaw('SUM(quantity_remaining) AS tracked_units')
            ->groupBy('store_product_variant_id');
        $coverageSearch = trim((string) ($filters['search'] ?? ''));
        $coverageQuery = DB::table('store_product_variants as spv')
            ->leftJoinSub($trackedStock, 'expiry_totals', 'expiry_totals.store_product_variant_id', '=', 'spv.id')
            ->join('stores as stores', 'stores.id', '=', 'spv.store_id')
            ->join('product_variants as variants', 'variants.id', '=', 'spv.product_variant_id')
            ->join('products as products', 'products.id', '=', 'variants.product_id')
            ->whereNull('spv.deleted_at')
            ->whereNull('stores.deleted_at')
            ->whereNull('variants.deleted_at')
            ->whereNull('products.deleted_at')
            ->where('spv.stock', '>', 0)
            ->whereRaw('spv.stock > COALESCE(expiry_totals.tracked_units, 0)')
            ->when($sellerId, fn ($query) => $query->where('stores.seller_id', $sellerId)->where('products.seller_id', $sellerId))
            ->when(isset($filters['store_id']), fn ($query) => $query->where('spv.store_id', $filters['store_id']))
            ->when($coverageSearch !== '', function ($query) use ($coverageSearch) {
                $query->where(function ($query) use ($coverageSearch) {
                    $query->where('products.title', 'like', "%{$coverageSearch}%")
                        ->orWhere('variants.title', 'like', "%{$coverageSearch}%")
                        ->orWhere('stores.name', 'like', "%{$coverageSearch}%");
                });
            });
        $coverage = $coverageQuery
            ->selectRaw('COUNT(*) AS variants, SUM(GREATEST(spv.stock - COALESCE(expiry_totals.tracked_units, 0), 0)) AS units')
            ->first();
        $summary['untracked_variants'] = (int) ($coverage->variants ?? 0);
        $summary['untracked_units'] = (int) ($coverage->units ?? 0);

        $batches = (clone $query)
            ->orderBy('expiry_date')
            ->orderBy('id')
            ->paginate(25)
            ->withQueryString();

        $stores = Store::query()
            ->when($sellerId, fn (Builder $stores) => $stores->where('seller_id', $sellerId))
            ->orderBy('name')
            ->get(['id', 'name', 'seller_id']);

        $variants = collect();
        $search = trim((string) ($filters['search'] ?? ''));
        if (mb_strlen($search) >= 2) {
            $variants = StoreProductVariant::query()
                ->with(['store:id,name,seller_id', 'productVariant:id,product_id,title', 'productVariant.product:id,title,seller_id'])
                ->whereHas('store', fn (Builder $stores) => $stores->when($sellerId, fn (Builder $storeQuery) => $storeQuery->where('seller_id', $sellerId)))
                ->whereHas('productVariant.product', fn (Builder $products) => $products->when($sellerId, fn (Builder $productQuery) => $productQuery->where('seller_id', $sellerId)))
                ->when(isset($filters['store_id']), fn (Builder $query) => $query->where('store_id', $filters['store_id']))
                ->where(function (Builder $query) use ($search) {
                    $query->whereHas('productVariant.product', fn (Builder $products) => $products->where('title', 'like', "%{$search}%"))
                        ->orWhereHas('productVariant', fn (Builder $productVariants) => $productVariants->where('title', 'like', "%{$search}%"))
                        ->orWhereHas('store', fn (Builder $stores) => $stores->where('name', 'like', "%{$search}%"));
                })
                ->orderBy('id')
                ->limit(50)
                ->get();
        }

        $sellers = $isAdmin
            ? Seller::query()->with('user:id,name')->orderBy('id')->get(['id', 'user_id'])
            : collect();

        return view('inventory.expiry-batches.index', [
            'isAdmin' => $isAdmin,
            'filters' => $filters,
            'days' => $days,
            'summary' => $summary,
            'batches' => $batches,
            'stores' => $stores,
            'variants' => $variants,
            'sellers' => $sellers,
            'search' => $search,
            'baseRoute' => $isAdmin ? 'admin.inventory.expiry-management' : 'seller.inventory.expiry-management',
        ]);
    }

    private function receive(Request $request, ?int $sellerId = null): RedirectResponse
    {
        $validated = $request->validate([
            'store_product_variant_id' => ['required', 'integer', 'exists:store_product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'expiry_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'batch_code' => ['nullable', 'string', 'max:100'],
            'adjust_stock' => ['nullable', 'boolean'],
        ]);
        $storeVariant = $this->scopedVariant((int) $validated['store_product_variant_id'], $sellerId);

        if ($sellerId !== null) {
            $this->authorize('update', $storeVariant->productVariant->product);
        }

        $this->batchService->receive(
            (int) $storeVariant->id,
            (int) $validated['quantity'],
            $validated['expiry_date'],
            $validated['batch_code'] ?? null,
            (int) $request->user()->id,
            $request->boolean('adjust_stock', true)
        );

        return back()->with('success', 'Expiry batch recorded successfully.');
    }

    private function update(Request $request, int $batchId, ?int $sellerId = null): RedirectResponse
    {
        $validated = $request->validate([
            'expiry_date' => ['required', 'date_format:Y-m-d'],
            'batch_code' => ['nullable', 'string', 'max:100'],
            'quantity_remaining' => ['required', 'integer', 'min:0'],
            'adjust_stock' => ['nullable', 'boolean'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);
        $batchQuery = StoreProductExpiryBatch::query()
            ->with('storeProductVariant.productVariant.product');
        if ($sellerId !== null) {
            $batchQuery->whereHas(
                'storeProductVariant.store',
                fn (Builder $stores) => $stores->where('seller_id', $sellerId)
            )->whereHas(
                'storeProductVariant.productVariant.product',
                fn (Builder $products) => $products->where('seller_id', $sellerId)
            );
        }
        $batch = $batchQuery->findOrFail($batchId);
        if ($sellerId !== null) {
            $this->authorize('update', $batch->storeProductVariant->productVariant->product);
        }
        $adjustStock = $request->boolean('adjust_stock', true);
        if ($adjustStock && (int) $validated['quantity_remaining'] !== (int) $batch->quantity_remaining
            && trim((string) ($validated['reason'] ?? '')) === '') {
            throw ValidationException::withMessages([
                'reason' => 'Add a reason when changing sellable stock.',
            ]);
        }

        $this->batchService->update(
            $batch,
            [
                'expiry_date' => $validated['expiry_date'],
                'batch_code' => $validated['batch_code'] ?? null,
                'quantity_remaining' => (int) $validated['quantity_remaining'],
            ],
            $adjustStock,
            $validated['reason'] ?? null
        );

        return back()->with('success', 'Expiry batch updated successfully.');
    }

    private function batchQuery(?int $sellerId): Builder
    {
        return StoreProductExpiryBatch::query()
            ->with([
                'storeProductVariant.store:id,name,seller_id',
                'storeProductVariant.store.seller:id,user_id',
                'storeProductVariant.store.seller.user',
                'storeProductVariant.productVariant:id,product_id,title',
                'storeProductVariant.productVariant.product:id,title,seller_id',
            ])
            ->whereHas(
                'storeProductVariant.store',
                fn (Builder $stores) => $stores->when($sellerId, fn (Builder $storeQuery) => $storeQuery->where('seller_id', $sellerId))
            )
            ->whereHas(
                'storeProductVariant.productVariant.product',
                fn (Builder $products) => $products->when($sellerId, fn (Builder $productQuery) => $productQuery->where('seller_id', $sellerId))
            );
    }

    private function applyFilters(Builder $query, array $filters, int $days): void
    {
        if (isset($filters['store_id'])) {
            $query->whereHas(
                'storeProductVariant',
                fn (Builder $variants) => $variants->where('store_id', $filters['store_id'])
            );
        }
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function (Builder $query) use ($search) {
                $query->where('batch_code', 'like', "%{$search}%")
                    ->orWhereHas('storeProductVariant.store', fn (Builder $stores) => $stores->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('storeProductVariant.productVariant', fn (Builder $variants) => $variants->where('title', 'like', "%{$search}%")
                        ->orWhereHas('product', fn (Builder $products) => $products->where('title', 'like', "%{$search}%")));
            });
        }

        $today = today()->toDateString();
        $cutoff = today()->addDays($days)->toDateString();
        match ($filters['status'] ?? 'all') {
            'expired' => $query->where('quantity_remaining', '>', 0)->whereDate('expiry_date', '<', $today),
            'expiring' => $query->where('quantity_remaining', '>', 0)->whereDate('expiry_date', '>=', $today)->whereDate('expiry_date', '<=', $cutoff),
            'safe' => $query->where('quantity_remaining', '>', 0)->whereDate('expiry_date', '>', $cutoff),
            'depleted' => $query->where('quantity_remaining', 0),
            default => null,
        };
    }

    private function scopedVariant(int $variantId, ?int $sellerId): StoreProductVariant
    {
        return StoreProductVariant::query()
            ->with(['productVariant.product'])
            ->whereKey($variantId)
            ->whereHas('store', fn (Builder $stores) => $stores->when($sellerId, fn (Builder $storeQuery) => $storeQuery->where('seller_id', $sellerId)))
            ->whereHas('productVariant.product', fn (Builder $products) => $products->when($sellerId, fn (Builder $productQuery) => $productQuery->where('seller_id', $sellerId)))
            ->firstOrFail();
    }
}
