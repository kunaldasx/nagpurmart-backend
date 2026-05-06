<?php

namespace App\Http\Controllers;

use App\Enums\Order\OrderItemReturnStatusEnum;
use App\Models\OrderItemReturn;
use App\Models\Seller;
use App\Types\Api\ApiResponseType;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ReturnRequestController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display seller returns list page.
     */
    public function index(): View
    {
        $this->authorize('viewAny', OrderItemReturn::class);

        $columns = [
            ['title' => __('labels.id'), 'data' => 'id'],
            ['title' => __('labels.order_date'), 'data' => 'order_date'],
            ['title' => __('labels.order_details'), 'data' => 'order_details'],
            ['title' => __('labels.refund_amount'), 'data' => 'refund_amount'],
            ['title' => __('labels.pickup_status'), 'data' => 'pickup_status'],
            ['title' => __('labels.return_status'), 'data' => 'return_status'],
            ['title' => __('labels.actions'), 'data' => 'actions', 'orderable' => false, 'searchable' => false],
        ];

        return view('seller.returns.index', compact('columns'));
    }

    /**
     * Datatable endpoint for seller returns
     */
    public function datatable(Request $request): JsonResponse
    {
        $this->authorize('viewAny', OrderItemReturn::class);

        $draw = (int)$request->get('draw');
        $start = (int)$request->get('start', 0);
        $length = (int)$request->get('length', 10);
        $searchValue = $request->get('search')['value'] ?? '';

        $orderColumnIndex = $request->get('order')[0]['column'] ?? 0;
        $orderDirection = $request->get('order')[0]['dir'] ?? 'desc';

        $columns = ['id', 'created_at', 'order_details', 'refund_amount', 'pickup_status', 'return_status', 'actions'];
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';

        $seller = $request->user()->seller();
        if (empty($seller)) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: __('messages.seller_not_found'),
                data: []
            );
        }

        $query = OrderItemReturn::with(['order', 'orderItem.product', 'orderItem.variant', 'store', 'user'])
            ->where('seller_id', $seller->id)
            ->where('return_status', '!=', OrderItemReturnStatusEnum::CANCELLED());

        $totalRecords = $query->count();

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('id', 'like', "%{$searchValue}%")
                    ->orWhere('reason', 'like', "%{$searchValue}%")
                    ->orWhere('seller_comment', 'like', "%{$searchValue}%")
                    ->orWhereHas('order', fn($qo) => $qo->where('order_id', 'like', "%{$searchValue}%"));
            });
        }

        $filteredRecords = $query->count();

        $results = $query->orderBy($orderColumn === 'order_details' ? 'created_at' : $orderColumn, $orderDirection)
            ->skip($start)
            ->take($length)
            ->get();

        $data = $results->map(fn($ret) => $this->formatReturnRow($ret))->toArray();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ]);
    }

    /**
     * Approve / Reject action from seller
     */
    public function decision(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'action' => 'required|in:approve,reject',
            'seller_comment' => 'nullable|string|max:1000'
        ]);

        $user = Auth::user();
        $seller = Seller::where('user_id', $user->id)->first();

        if (!$seller) {
            return ApiResponseType::sendJsonResponse(false, __('messages.seller_not_found'), [], 404);
        }

        $orderReturn = OrderItemReturn::where('id', $id)
            ->where('seller_id', $seller->id)
            ->first();

        if (!$orderReturn) {
            return ApiResponseType::sendJsonResponse(false, __('messages.return_not_found'), [], 404);
        }

        // Authorization: ensure seller can take decision on this return
        $this->authorize('decide', $orderReturn);

        // prevent repeated decisions for already finalized returns
        if (in_array($orderReturn->return_status, [
            OrderItemReturnStatusEnum::SELLER_APPROVED(),
            OrderItemReturnStatusEnum::SELLER_REJECTED(),
            OrderItemReturnStatusEnum::CANCELLED()
        ], true)) {
            return ApiResponseType::sendJsonResponse(false, __('messages.return_already_processed'), [], 422);
        }

        $action = $request->post('action');
        $sellerComment = $request->post('seller_comment');

        if ($action === 'approve') {
            $orderReturn->return_status = OrderItemReturnStatusEnum::SELLER_APPROVED();
            $orderReturn->seller_approved_at = Carbon::now();
            $message = __('messages.return_approved_successfully');
        } else {
            $orderReturn->return_status = OrderItemReturnStatusEnum::SELLER_REJECTED();
            $message = __('messages.return_rejected_successfully');
        }

        if (!empty($sellerComment)) {
            $orderReturn->seller_comment = Str::limit($sellerComment, 1000);
        }

        $orderReturn->save();

        return ApiResponseType::sendJsonResponse(true, $message, $this->formatReturnRow($orderReturn), 200);
    }

    /**
     * Format a single OrderItemReturn to datatable row (HTML where needed)
     */
    private function formatReturnRow(OrderItemReturn $r): array
    {
        $created = $r->created_at;
        $orderDateHtml = "<div><p class='m-0 fw-medium'>{$created->diffForHumans()}</p>{$created->format('Y-m-d H:i:s')}</div>";

        $productTitle = $r->orderItem && $r->orderItem->product
            ? e($r->orderItem->product->title)
            : __('labels.n_a');

        $variantTitle = $r->orderItem && $r->orderItem->variant ? e($r->orderItem->variant->title) : '';
        $images = '';
        if (!empty($r->images)) {
            foreach ($r->images as $image) {
                $images .= view('partials.image', ['image' => $image])->render();
            }
        }

        $orderDetails = "<div>
                <p class='m-0 fw-medium'>{$productTitle} " . ($variantTitle ? "<small>{$variantTitle}</small>" : "") . "</p>
                <small class='text-muted'>" . __('labels.order_id') . " - {$r->order_id}</small> |
                <small class='text-muted'>" . __('labels.order_item_id') . " - {$r->order_item_id}</small>
                <div class='mt-2 d-flex align-items-center gap-1'>{$images}</div>
            </div>";

        $refundAmount = '<div>' . number_format($r->refund_amount, 2) . '</div>';
        $pickupStatus = view('partials.order-status', [
            'status' => e($r->pickup_status),
        ])->render();
        $returnStatus = view('partials.order-status', [
            'status' => Str::replace("_", " ", e($r->return_status)),
        ])->render();
        // actions: approve / reject if requested, otherwise show label
        $actions = '';
        if ($r->return_status === (string)OrderItemReturnStatusEnum::REQUESTED()) {
            $actions .= "
        <div class='d-flex me-2 mt-2'>
            <button class='btn btn-success me-2' data-bs-toggle='modal' data-bs-target='#acceptModel' data-id='{$r->id}'>
                " . __('labels.approve') . "
            </button>
            <button class='btn btn-outline-danger' data-bs-toggle='modal' data-bs-target='#rejectModel' data-id='{$r->id}'>
                " . __('labels.reject') . "
            </button>
        </div>
    ";
        }


        return [
            'id' => $r->id,
            'order_date' => $orderDateHtml,
            'order_details' => $orderDetails,
            'refund_amount' => $refundAmount,
            'pickup_status' => $pickupStatus,
            'return_status' => $returnStatus,
            'actions' => $actions,
        ];
    }
}
