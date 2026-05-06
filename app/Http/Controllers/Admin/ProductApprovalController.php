<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdminPermissionEnum;
use App\Enums\Product\ProductVarificationStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Traits\ChecksPermissions;
use App\Types\Api\ApiResponseType;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductApprovalController extends Controller
{
    use ChecksPermissions, AuthorizesRequests;

    protected bool $viewPermission = false;
    protected bool $processPermission = false;

    public function __construct()
    {
        $this->viewPermission = $this->hasPermission(AdminPermissionEnum::PRODUCT_VIEW());
        // Use PRODUCT_EDIT for processing approvals (adjust if you have a dedicated permission)
        $this->processPermission = $this->hasPermission(AdminPermissionEnum::PRODUCT_STATUS_UPDATE());
    }

    /**
     * Display a listing of products pending admin approval
     */
    public function index(): View
    {
        $this->authorize('viewAny', Product::class);
        $columns = [
            ['data' => 'id', 'name' => 'id', 'title' => __('labels.id')],
            ['data' => 'title', 'name' => 'title', 'title' => __('labels.title')],
            ['data' => 'seller', 'name' => 'seller', 'title' => __('labels.seller')],
            ['data' => 'category', 'name' => 'category', 'title' => __('labels.category')],
            ['data' => 'status', 'name' => 'status', 'title' => __('labels.status')],
            ['data' => 'verification_status', 'name' => 'verification_status', 'title' => __('labels.admin_approval_status')],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => __('labels.created_at')],
            ['data' => 'action', 'name' => 'action', 'title' => __('labels.action'), 'orderable' => false, 'searchable' => false],
        ];

        return view('admin.product_approvals.index', compact('columns'));
    }

    /**
     * Get pending products for DataTable
     */
    public function getPendingProducts(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        $draw = $request->get('draw');
        $start = $request->get('start');
        $length = $request->get('length');
        $searchValue = $request->get('search')['value'] ?? '';

        $orderColumnIndex = $request->get('order')[0]['column'] ?? 0;
        $orderDirection = $request->get('order')[0]['dir'] ?? 'asc';
        $columns = ['id', 'title', 'seller_id', 'category_id', 'status', 'verification_status', 'created_at'];
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';

        $query = Product::with(['seller.user', 'category'])
            ->where('verification_status', ProductVarificationStatusEnum::PENDING());

        $totalRecords = $query->count();
        $filteredRecords = $totalRecords;

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('id', 'like', "%{$searchValue}%")
                    ->orWhere('title', 'like', "%{$searchValue}%")
                    ->orWhere('description', 'like', "%{$searchValue}%")
                    ->orWhereHas('category', function ($q) use ($searchValue) {
                        $q->where('title', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('seller.user', function ($q) use ($searchValue) {
                        $q->where('name', 'like', "%{$searchValue}%");
                    });
            });
            $filteredRecords = $query->count();
        }

        $data = $query->orderBy($orderColumn, $orderDirection)
            ->skip($start)
            ->take($length)
            ->get()
            ->map(function (Product $product) {
                return [
                    'id' => $product->id,
                    'title' => $product->title,
                    'seller' => $product->seller->user->name ?? 'N/A',
                    'category' => $product->category->title ?? 'N/A',
                    'status' => view('partials.status', ['status' => $product->status ?? ''])->render(),
                    'verification_status' => view('partials.status', ['status' => $product->verification_status ?? ''])->render(),
                    'created_at' => $product->created_at?->format('Y-m-d H:i:s'),
                    'action' => view('admin.product_approvals.actions', [
                        'id' => $product->id,
                        'processPermission' => $this->processPermission,
                    ])->render(),
                ];
            })
            ->toArray();

        return response()->json([
            'draw' => intval($draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    /**
     * Approve a product
     */
    public function approve(int $id): JsonResponse
    {
        try {
            $product = Product::findOrFail($id);
            $this->authorize('verifyProduct', $product);

            $product->verification_status = ProductVarificationStatusEnum::APPROVED();
            $product->rejection_reason = null;
            $product->save();

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: __('labels.product_approved_successfully'),
                data: ['id' => $product->id]
            );
        } catch (AuthorizationException $e) {
            return ApiResponseType::sendJsonResponse(success: false, message: __('labels.permission_denied'), data: []);
        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(success: false, message: __('labels.something_went_wrong'), data: ['error' => $e->getMessage()]);
        }
    }

    /**
     * Reject a product with reason
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate([
                'reason' => 'required|string|max:500',
            ]);
            $product = Product::findOrFail($id);
            $this->authorize('verifyProduct', $product);

            $product->verification_status = ProductVarificationStatusEnum::REJECTED();
            $product->rejection_reason = $request->input('reason');
            $product->save();

            return ApiResponseType::sendJsonResponse(
                success: true,
                message: __('labels.product_rejected_successfully'),
                data: ['id' => $product->id]
            );
        } catch (ValidationException $e) {
            return ApiResponseType::sendJsonResponse(success: false, message: $e->getMessage(), data: $e->errors());
        } catch (AuthorizationException $e) {
            return ApiResponseType::sendJsonResponse(success: false, message: __('labels.permission_denied'), data: []);
        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(success: false, message: __('labels.something_went_wrong'), data: ['error' => $e->getMessage()]);
        }
    }
}
