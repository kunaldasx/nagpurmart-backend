<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StoreProductExpiryBatch;
use App\Models\StoreProductVariant;
use App\Services\ProductExpiryBatchService;
use App\Types\Api\ApiResponseType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProductExpiryBatchController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly ProductExpiryBatchService $batchService)
    {
    }

    public function sellerIndex(Request $request): JsonResponse
    {
        $seller = $request->user()?->seller();
        if (!$seller) {
            return ApiResponseType::sendJsonResponse(false, 'labels.seller_not_found', null, 404);
        }

        try {
            $this->authorize('viewAny', Product::class);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.permission_denied'), [], 403);
        }

        return $this->index($request, function (Builder $query) use ($seller) {
            $query->whereHas('storeProductVariant.store', fn (Builder $stores) => $stores->where('seller_id', $seller->id));
            $query->whereHas('storeProductVariant.productVariant.product', fn (Builder $products) => $products->where('seller_id', $seller->id));
        });
    }

    public function adminIndex(Request $request): JsonResponse
    {
        return $this->index($request, function (Builder $query) use ($request) {
            if ($request->filled('seller_id')) {
                $query->whereHas(
                    'storeProductVariant.store',
                    fn (Builder $stores) => $stores->where('seller_id', $request->integer('seller_id'))
                );
            }
        });
    }

    public function sellerSummary(Request $request): JsonResponse
    {
        $seller = $request->user()?->seller();
        if (!$seller) {
            return ApiResponseType::sendJsonResponse(false, 'labels.seller_not_found', null, 404);
        }

        try {
            $this->authorize('viewAny', Product::class);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.permission_denied'), [], 403);
        }

        return $this->summary($request, function (Builder $query) use ($seller) {
            $query->whereHas('storeProductVariant.store', fn (Builder $stores) => $stores->where('seller_id', $seller->id));
            $query->whereHas('storeProductVariant.productVariant.product', fn (Builder $products) => $products->where('seller_id', $seller->id));
        });
    }

    public function adminSummary(Request $request): JsonResponse
    {
        return $this->summary($request, function (Builder $query) use ($request) {
            if ($request->filled('seller_id')) {
                $query->whereHas(
                    'storeProductVariant.store',
                    fn (Builder $stores) => $stores->where('seller_id', $request->integer('seller_id'))
                );
            }
        });
    }

    public function sellerStore(Request $request): JsonResponse
    {
        $seller = $request->user()?->seller();
        if (!$seller) {
            return ApiResponseType::sendJsonResponse(false, 'labels.seller_not_found', null, 404);
        }

        try {
            $validated = $request->validate([
                'store_product_variant_id' => ['required', 'integer', 'exists:store_product_variants,id'],
                'quantity' => ['required', 'integer', 'min:1'],
                'expiry_date' => ['required', 'date'],
                'batch_code' => ['nullable', 'string', 'max:100'],
                'adjust_stock' => ['sometimes', 'boolean'],
            ]);

            $storeVariant = StoreProductVariant::whereKey($validated['store_product_variant_id'])
                ->whereHas('store', fn (Builder $stores) => $stores->where('seller_id', $seller->id))
                ->whereHas('productVariant.product', fn (Builder $products) => $products->where('seller_id', $seller->id))
                ->firstOrFail();
            $this->authorize('update', $storeVariant->productVariant->product);

            $batch = $this->batchService->receive(
                (int) $storeVariant->id,
                (int) $validated['quantity'],
                $validated['expiry_date'],
                $validated['batch_code'] ?? null,
                $request->user()->id,
                (bool) ($validated['adjust_stock'] ?? true)
            );

            return ApiResponseType::sendJsonResponse(true, 'Expiry batch received successfully', $this->formatBatch($batch), 201);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.permission_denied'), [], 403);
        } catch (ModelNotFoundException) {
            return ApiResponseType::sendJsonResponse(false, 'Store product variant not found', null, 404);
        } catch (Throwable $e) {
            report($e);
            return ApiResponseType::sendJsonResponse(false, 'Unable to receive expiry batch', null, 500);
        }
    }

    public function adminStore(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'store_product_variant_id' => ['required', 'integer', 'exists:store_product_variants,id'],
                'quantity' => ['required', 'integer', 'min:1'],
                'expiry_date' => ['required', 'date'],
                'batch_code' => ['nullable', 'string', 'max:100'],
                'adjust_stock' => ['sometimes', 'boolean'],
            ]);

            $batch = $this->batchService->receive(
                (int) $validated['store_product_variant_id'],
                (int) $validated['quantity'],
                $validated['expiry_date'],
                $validated['batch_code'] ?? null,
                $request->user()->id,
                (bool) ($validated['adjust_stock'] ?? true)
            );

            return ApiResponseType::sendJsonResponse(true, 'Expiry batch received successfully', $this->formatBatch($batch), 201);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        } catch (ModelNotFoundException) {
            return ApiResponseType::sendJsonResponse(false, 'Store product variant not found', null, 404);
        } catch (Throwable $e) {
            report($e);
            return ApiResponseType::sendJsonResponse(false, 'Unable to receive expiry batch', null, 500);
        }
    }

    public function sellerUpdate(Request $request, int $batchId): JsonResponse
    {
        $seller = $request->user()?->seller();
        if (!$seller) {
            return ApiResponseType::sendJsonResponse(false, 'labels.seller_not_found', null, 404);
        }

        return $this->update(
            $request,
            $batchId,
            function (Builder $query) use ($seller) {
                $query->whereHas(
                    'storeProductVariant.store',
                    fn (Builder $stores) => $stores->where('seller_id', $seller->id)
                );
                $query->whereHas(
                    'storeProductVariant.productVariant.product',
                    fn (Builder $products) => $products->where('seller_id', $seller->id)
                );
            },
            fn (StoreProductExpiryBatch $batch) => $this->authorize(
                'update',
                $batch->storeProductVariant->productVariant->product
            )
        );
    }

    public function adminUpdate(Request $request, int $batchId): JsonResponse
    {
        return $this->update($request, $batchId);
    }

    private function index(Request $request, ?callable $scope = null): JsonResponse
    {
        try {
            $validated = $request->validate([
                'status' => ['sometimes', 'in:all,expired,expiring,safe,depleted'],
                'days' => ['sometimes', 'integer', 'min:1', 'max:365'],
                'seller_id' => ['sometimes', 'integer', 'exists:sellers,id'],
                'store_id' => ['sometimes', 'integer', 'exists:stores,id'],
                'product_id' => ['sometimes', 'integer', 'exists:products,id'],
                'page' => ['sometimes', 'integer', 'min:1'],
                'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            ]);

            $query = $this->batchQuery();
            if ($scope) {
                $scope($query);
            }
            $this->applyFilters($query, $validated);

            $paginator = $query->paginate((int) ($validated['per_page'] ?? 20));
            $paginator->getCollection()->transform(fn (StoreProductExpiryBatch $batch) => $this->formatBatch(
                $batch,
                (int) ($validated['days'] ?? ProductExpiryBatchService::DEFAULT_WARNING_DAYS)
            ));

            return ApiResponseType::sendJsonResponse(true, 'Expiry batches fetched successfully', ApiResponseType::responseFromPaginator($paginator));
        } catch (ValidationException $e) {
            return $this->validationError($e);
        } catch (Throwable $e) {
            report($e);
            return ApiResponseType::sendJsonResponse(false, 'Unable to fetch expiry batches', null, 500);
        }
    }

    private function summary(Request $request, ?callable $scope = null): JsonResponse
    {
        try {
            $validated = $request->validate([
                'days' => ['sometimes', 'integer', 'min:1', 'max:365'],
                'seller_id' => ['sometimes', 'integer', 'exists:sellers,id'],
            ]);
            $days = (int) ($validated['days'] ?? ProductExpiryBatchService::DEFAULT_WARNING_DAYS);
            $today = today()->toDateString();
            $cutoff = today()->addDays($days)->toDateString();

            $query = StoreProductExpiryBatch::query()->where('quantity_remaining', '>', 0);
            if ($scope) {
                $scope($query);
            }

            $expired = (clone $query)->whereDate('expiry_date', '<', $today);
            $expiring = (clone $query)->whereDate('expiry_date', '>=', $today)->whereDate('expiry_date', '<=', $cutoff);

            return ApiResponseType::sendJsonResponse(true, 'Expiry summary fetched successfully', [
                'near_expiry_days' => $days,
                'expired_batches' => (clone $expired)->count(),
                'expired_units' => (clone $expired)->sum('quantity_remaining'),
                'expiring_batches' => (clone $expiring)->count(),
                'expiring_units' => (clone $expiring)->sum('quantity_remaining'),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        } catch (Throwable $e) {
            report($e);
            return ApiResponseType::sendJsonResponse(false, 'Unable to fetch expiry summary', null, 500);
        }
    }

    private function update(
        Request $request,
        int $batchId,
        ?callable $scope = null,
        ?callable $authorizeBatch = null
    ): JsonResponse
    {
        try {
            $validated = $request->validate([
                'expiry_date' => ['sometimes', 'date'],
                'batch_code' => ['sometimes', 'nullable', 'string', 'max:100'],
                'quantity_remaining' => ['sometimes', 'integer', 'min:0'],
                'adjust_stock' => ['sometimes', 'boolean'],
                'reason' => ['sometimes', 'nullable', 'string', 'max:255'],
            ]);

            $adjustStock = (bool) ($validated['adjust_stock'] ?? true);
            $reason = $validated['reason'] ?? null;
            unset($validated['adjust_stock']);
            unset($validated['reason']);
            if ($validated === []) {
                throw ValidationException::withMessages([
                    'batch' => 'Provide at least one batch field to update.',
                ]);
            }

            $query = StoreProductExpiryBatch::query();
            if ($scope) {
                $scope($query);
            }
            $batch = $query->findOrFail($batchId);
            if ($authorizeBatch) {
                $authorizeBatch($batch);
            }
            $batch = $this->batchService->update($batch, $validated, $adjustStock, $reason);

            return ApiResponseType::sendJsonResponse(true, 'Expiry batch updated successfully', $this->formatBatch($batch));
        } catch (ValidationException $e) {
            return $this->validationError($e);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.permission_denied'), [], 403);
        } catch (ModelNotFoundException) {
            return ApiResponseType::sendJsonResponse(false, 'Expiry batch not found', null, 404);
        } catch (Throwable $e) {
            report($e);
            return ApiResponseType::sendJsonResponse(false, 'Unable to update expiry batch', null, 500);
        }
    }

    private function batchQuery(): Builder
    {
        return StoreProductExpiryBatch::query()
            ->with([
                'storeProductVariant.store:id,name,seller_id',
                'storeProductVariant.productVariant:id,product_id,title',
                'storeProductVariant.productVariant.product:id,title',
            ])
            ->orderBy('expiry_date')
            ->orderBy('id');
    }

    private function applyFilters(Builder $query, array $validated): void
    {
        if (isset($validated['store_id'])) {
            $query->whereHas('storeProductVariant', fn (Builder $variants) => $variants->where('store_id', $validated['store_id']));
        }
        if (isset($validated['product_id'])) {
            $query->whereHas(
                'storeProductVariant.productVariant',
                fn (Builder $variants) => $variants->where('product_id', $validated['product_id'])
            );
        }

        $days = (int) ($validated['days'] ?? ProductExpiryBatchService::DEFAULT_WARNING_DAYS);
        $today = today()->toDateString();
        $cutoff = today()->addDays($days)->toDateString();
        match ($validated['status'] ?? 'all') {
            'expired' => $query->where('quantity_remaining', '>', 0)->whereDate('expiry_date', '<', $today),
            'expiring' => $query->where('quantity_remaining', '>', 0)->whereDate('expiry_date', '>=', $today)->whereDate('expiry_date', '<=', $cutoff),
            'safe' => $query->where('quantity_remaining', '>', 0)->whereDate('expiry_date', '>', $cutoff),
            'depleted' => $query->where('quantity_remaining', 0),
            default => $query,
        };
    }

    private function formatBatch(
        StoreProductExpiryBatch $batch,
        int $days = ProductExpiryBatchService::DEFAULT_WARNING_DAYS
    ): array
    {
        $batch->loadMissing([
            'storeProductVariant.store:id,name,seller_id',
            'storeProductVariant.productVariant:id,product_id,title',
            'storeProductVariant.productVariant.product:id,title',
        ]);
        $expiryDate = $batch->expiry_date->toDateString();
        $daysUntilExpiry = (int) today()->diffInDays($batch->expiry_date, false);
        $status = $batch->quantity_remaining === 0
            ? 'depleted'
            : ($expiryDate < today()->toDateString()
                ? 'expired'
                : ($daysUntilExpiry <= $days ? 'expiring' : 'safe'));
        $storeVariant = $batch->storeProductVariant;

        return [
            'id' => $batch->id,
            'store_product_variant_id' => $batch->store_product_variant_id,
            'store_id' => $storeVariant->store_id,
            'store_name' => $storeVariant->store?->name,
            'seller_id' => $storeVariant->store?->seller_id,
            'product_id' => $storeVariant->productVariant?->product_id,
            'product_name' => $storeVariant->productVariant?->product?->title,
            'variant_name' => $storeVariant->productVariant?->title,
            'batch_code' => $batch->batch_code,
            'quantity_received' => $batch->quantity_received,
            'quantity_remaining' => $batch->quantity_remaining,
            'expiry_date' => $expiryDate,
            'days_until_expiry' => $daysUntilExpiry,
            'status' => $status,
            'created_at' => $batch->created_at,
            'updated_at' => $batch->updated_at,
        ];
    }

    private function validationError(ValidationException $exception): JsonResponse
    {
        return ApiResponseType::sendJsonResponse(
            false,
            $exception->validator->errors()->first(),
            $exception->errors(),
            422
        );
    }
}
