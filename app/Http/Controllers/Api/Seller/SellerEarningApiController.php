<?php

namespace App\Http\Controllers\Api\Seller;

use App\Enums\Seller\SellerSettlementStatusEnum;
use App\Enums\Seller\SellerSettlementTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\SellerStatement;
use App\Services\CurrencyService;
use App\Types\Api\ApiResponseType;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Seller Earnings')]
class SellerEarningApiController extends Controller
{
    use AuthorizesRequests;

    public function __construct(protected CurrencyService $currencyService)
    {
    }

    /**
     * List unsettled seller earnings (credit statements) for the authenticated seller.
     */
    #[QueryParameter('page', description: 'Page number', type: 'int', default: 1, example: 1)]
    #[QueryParameter('per_page', description: 'Items per page', type: 'int', default: 15, example: 15)]
    #[QueryParameter('search', description: 'Search in reference, description, product/store name', type: 'string')]
    #[QueryParameter('store_id', description: 'Filter by store id', type: 'int')]
    #[QueryParameter('sort_by', description: 'Sort column: id|amount|posted_at', type: 'string', default: 'id')]
    #[QueryParameter('sort_dir', description: 'Sort direction: asc|desc', type: 'string', default: 'desc')]
    public function unsettled(Request $request): JsonResponse
    {
        try {
            $this->authorize('viewAny', SellerStatement::class);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.permission_denied') ?? __('messages.unauthorized_action'), [], 403);
        }

        $user = auth()->user();
        $seller = $user?->seller();
        if (!$seller) {
            return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found') ?? 'Seller not found', null, 404);
        }

        $perPage = (int)$request->integer('per_page', 15);
        $searchValue = trim((string)$request->input('search', ''));
        $storeId = $request->input('store_id');
        $sortBy = in_array($request->input('sort_by'), ['id', 'amount', 'posted_at']) ? $request->input('sort_by') : 'id';
        $sortDir = strtolower($request->input('sort_dir')) === 'asc' ? 'asc' : 'desc';

        $query = SellerStatement::with(['orderItem.store', 'orderItem.sellerOrderItem'])
            ->where('seller_id', $seller->id)
            ->where('entry_type', SellerSettlementTypeEnum::CREDIT())
            ->where('settlement_status', SellerSettlementStatusEnum::PENDING());

        if (!empty($storeId)) {
            $query->whereHas('orderItem', function ($qi) use ($storeId) {
                $qi->where('store_id', $storeId);
            });
        }

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('reference_id', 'like', "%{$searchValue}%")
                    ->orWhere('description', 'like', "%{$searchValue}%")
                    ->orWhereHas('orderItem', function ($qi) use ($searchValue) {
                        $qi->where('title', 'like', "%{$searchValue}%")
                            ->orWhere('variant_title', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('orderItem.store', function ($qs) use ($searchValue) {
                        $qs->where('name', 'like', "%{$searchValue}%");
                    });
            });
        }

        $paginator = $query->orderBy($sortBy, $sortDir)->paginate($perPage);

        $paginator->getCollection()->transform(function ($st) {
            $orderItem = $st->orderItem;
            return [
                'id' => $st->id,
                'entry_type' => $st->entry_type,
                'reference_type' => $st->reference_type,
                'reference_id' => $st->reference_id,
                'description' => $st->description,
                'order' => [
                    'order_id' => $st->order_id,
                    'order_item_id' => $st->order_item_id,
                    'seller_order_id' => $orderItem->sellerOrderItem?->seller_order_id,
                    'product_title' => $orderItem?->title,
                    'variant_title' => $orderItem?->variant_title,
                    'store_name' => $orderItem?->store?->name,
                ],
                'amount' => [
                    'raw' => (float)$st->amount,
                    'formatted' => $this->currencyService->format($st->amount),
                ],
                'posted_at' => $st->posted_at,
                'settlement_status' => $st->settlement_status,
            ];
        });

        return ApiResponseType::sendJsonResponse(true, __('labels.unsettled_commissions_retrieved_successfully') ?? 'Unsettled commissions retrieved successfully', [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'data' => $paginator->items(),
        ]);
    }

    /**
     * List unsettled debit statements for the authenticated seller (e.g., returns to be debited).
     */
    #[QueryParameter('page', description: 'Page number', type: 'int', default: 1, example: 1)]
    #[QueryParameter('per_page', description: 'Items per page', type: 'int', default: 15, example: 15)]
    #[QueryParameter('search', description: 'Search query', type: 'string')]
    #[QueryParameter('store_id', description: 'Filter by store id', type: 'int')]
    #[QueryParameter('sort_by', description: 'Sort column: id|amount|posted_at', type: 'string', default: 'id')]
    #[QueryParameter('sort_dir', description: 'Sort direction: asc|desc', type: 'string', default: 'desc')]
    public function unsettledDebits(Request $request): JsonResponse
    {
        try {
            $this->authorize('viewAny', SellerStatement::class);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.permission_denied') ?? __('messages.unauthorized_action'), [], 403);
        }

        $user = auth()->user();
        $seller = $user?->seller();
        if (!$seller) {
            return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found') ?? 'Seller not found', null, 404);
        }

        $perPage = (int)$request->integer('per_page', 15);
        $searchValue = trim((string)$request->input('search', ''));
        $storeId = $request->input('store_id');
        $sortBy = in_array($request->input('sort_by'), ['id', 'amount', 'posted_at']) ? $request->input('sort_by') : 'id';
        $sortDir = strtolower($request->input('sort_dir')) === 'asc' ? 'asc' : 'desc';

        $query = SellerStatement::with(['orderItem.store', 'orderItem.sellerOrderItem'])
            ->where('seller_id', $seller->id)
            ->where('entry_type', SellerSettlementTypeEnum::DEBIT())
            ->where('settlement_status', SellerSettlementStatusEnum::PENDING());

        if (!empty($storeId)) {
            $query->whereHas('orderItem', function ($qi) use ($storeId) {
                $qi->where('store_id', $storeId);
            });
        }

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('reference_id', 'like', "%{$searchValue}%")
                    ->orWhere('description', 'like', "%{$searchValue}%")
                    ->orWhereHas('orderItem', function ($qi) use ($searchValue) {
                        $qi->where('title', 'like', "%{$searchValue}%")
                            ->orWhere('variant_title', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('orderItem.store', function ($qs) use ($searchValue) {
                        $qs->where('name', 'like', "%{$searchValue}%");
                    });
            });
        }

        $paginator = $query->orderBy($sortBy, $sortDir)->paginate($perPage);

        $paginator->getCollection()->transform(function ($st) {
            $orderItem = $st->orderItem;
            return [
                'id' => $st->id,
                'entry_type' => $st->entry_type,
                'reference_type' => $st->reference_type,
                'reference_id' => $st->reference_id,
                'description' => $st->description,
                'order' => [
                    'order_id' => $st->order_id,
                    'order_item_id' => $st->order_item_id,
                    'seller_order_id' => $orderItem->sellerOrderItem?->seller_order_id,
                    'product_title' => $orderItem?->title,
                    'variant_title' => $orderItem?->variant_title,
                    'store_name' => $orderItem?->store?->name,
                ],
                'amount' => [
                    'raw' => (float)$st->amount,
                    'formatted' => $this->currencyService->format($st->amount),
                ],
                'posted_at' => $st->posted_at,
                'settlement_status' => $st->settlement_status,
            ];
        });

        return ApiResponseType::sendJsonResponse(true, __('labels.unsettled_debits_retrieved_successfully') ?? 'Unsettled debits retrieved successfully', [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'data' => $paginator->items(),
        ]);
    }

    /**
     * List settled statements (credits and debits) for the authenticated seller.
     */
    #[QueryParameter('page', description: 'Page number', type: 'int', default: 1, example: 1)]
    #[QueryParameter('per_page', description: 'Items per page', type: 'int', default: 15, example: 15)]
    #[QueryParameter('search', description: 'Search query', type: 'string')]
    #[QueryParameter('store_id', description: 'Filter by store id', type: 'int')]
    #[QueryParameter('sort_by', description: 'Sort column: id|amount|settled_at', type: 'string', default: 'id')]
    #[QueryParameter('sort_dir', description: 'Sort direction: asc|desc', type: 'string', default: 'desc')]
    public function history(Request $request): JsonResponse
    {
        try {
            $this->authorize('viewAny', SellerStatement::class);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, __('labels.permission_denied') ?? __('messages.unauthorized_action'), [], 403);
        }

        $user = auth()->user();
        $seller = $user?->seller();
        if (!$seller) {
            return ApiResponseType::sendJsonResponse(false, __('labels.seller_not_found') ?? 'Seller not found', null, 404);
        }

        $perPage = (int)$request->integer('per_page', 15);
        $searchValue = trim((string)$request->input('search', ''));
        $storeId = $request->input('store_id');
        $sortBy = in_array($request->input('sort_by'), ['id', 'amount', 'settled_at']) ? $request->input('sort_by') : 'id';
        $sortDir = strtolower($request->input('sort_dir')) === 'asc' ? 'asc' : 'desc';

        $query = SellerStatement::with(['orderItem.store', 'orderItem.sellerOrderItem'])
            ->where('seller_id', $seller->id)
            ->where('settlement_status', SellerSettlementStatusEnum::SETTLED());

        if (!empty($storeId)) {
            $query->whereHas('orderItem', function ($qi) use ($storeId) {
                $qi->where('store_id', $storeId);
            });
        }

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('reference_id', 'like', "%{$searchValue}%")
                    ->orWhere('description', 'like', "%{$searchValue}%")
                    ->orWhereHas('orderItem', function ($qi) use ($searchValue) {
                        $qi->where('title', 'like', "%{$searchValue}%")
                            ->orWhere('variant_title', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('orderItem.store', function ($qs) use ($searchValue) {
                        $qs->where('name', 'like', "%{$searchValue}%");
                    });
            });
        }

        $paginator = $query->orderBy($sortBy, $sortDir)->paginate($perPage);

        $paginator->getCollection()->transform(function ($st) {
            $orderItem = $st->orderItem;
            return [
                'id' => $st->id,
                'entry_type' => $st->entry_type,
                'reference_type' => $st->reference_type,
                'reference_id' => $st->reference_id,
                'description' => $st->description,
                'order' => [
                    'order_id' => $st->order_id,
                    'order_item_id' => $st->order_item_id,
                    'seller_order_id' => $orderItem->sellerOrderItem?->seller_order_id,
                    'product_title' => $orderItem?->title,
                    'variant_title' => $orderItem?->variant_title,
                    'store_name' => $orderItem?->store?->name,
                ],
                'amount' => [
                    'raw' => (float)$st->amount,
                    'formatted' => $this->currencyService->format($st->amount),
                ],
                'posted_at' => $st->posted_at,
                'settled_at' => $st->settled_at,
                'settlement_status' => $st->settlement_status,
            ];
        });

        return ApiResponseType::sendJsonResponse(true, __('labels.settled_commissions_retrieved_successfully') ?? 'Settled statements retrieved successfully', [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'data' => $paginator->items(),
        ]);
    }
}
