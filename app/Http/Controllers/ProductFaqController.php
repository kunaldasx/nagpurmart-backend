<?php

namespace App\Http\Controllers;

use App\Enums\DefaultSystemRolesEnum;
use App\Enums\SellerPermissionEnum;
use App\Http\Requests\ProductFaq\StoreUpdateProductFaqRequest;
use App\Models\ProductFaq;
use App\Traits\ChecksPermissions;
use App\Traits\PanelAware;
use App\Types\Api\ApiResponseType;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductFaqController extends Controller
{
    use PanelAware, AuthorizesRequests, ChecksPermissions;

    protected bool $editPermission = false;
    protected bool $deletePermission = false;
    protected bool $createPermission = false;

    public float $sellerId;

    public function __construct()
    {
        $user = auth()->user();
        $seller = $user?->seller();
        $this->sellerId = $seller ? $seller->id : 0;
        if ($this->getPanel() === 'seller') {
            $this->editPermission = $this->hasPermission(SellerPermissionEnum::PRODUCT_FAQ_EDIT()) || $user->hasRole(DefaultSystemRolesEnum::SELLER());
            $this->deletePermission = $this->hasPermission(SellerPermissionEnum::PRODUCT_FAQ_DELETE()) || $user->hasRole(DefaultSystemRolesEnum::SELLER());
            $this->createPermission = $this->hasPermission(SellerPermissionEnum::PRODUCT_FAQ_CREATE()) || $user->hasRole(DefaultSystemRolesEnum::SELLER());
        }
    }

    /**
     * Display a listing of the product FAQs.
     */
    public function index(): View
    {
        $this->authorize('viewAny', ProductFaq::class);
        $columns = [
            ['data' => 'id', 'name' => 'id', 'title' => __('labels.id')],
            ['data' => 'product_title', 'name' => 'product_title', 'title' => __('labels.product')],
            ['data' => 'question', 'name' => 'question', 'title' => __('labels.question')],
            ['data' => 'answer', 'name' => 'answer', 'title' => __('labels.answer')],
            ['data' => 'status', 'name' => 'status', 'title' => __('labels.status')],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => __('labels.created_at')],
            ['data' => 'action', 'name' => 'action', 'title' => __('labels.action'), 'orderable' => false, 'searchable' => false],
        ];
        $createPermission = $this->createPermission;
        $editPermission = $this->editPermission;

        return view($this->panelView('product_faqs.index'), compact('columns', 'createPermission', 'editPermission'));
    }

    /**
     * Store a newly created product FAQ in storage.
     */
    public function store(StoreUpdateProductFaqRequest $request): JsonResponse
    {
        try {
            $this->authorize('create', ProductFaq::class);
            $validated = $request->validated();

            $productFaq = ProductFaq::create($validated);

            return ApiResponseType::sendJsonResponse(
                true,
                'Product FAQ created successfully.',
                $productFaq,
            );
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(
                false,
                'You do not have permission to create product FAQs.',
                [],
            );
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(
                false,
                'Failed to create product FAQ.',
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Display the specified product FAQ.
     */
    public function show($id): JsonResponse
    {
        try {
            $this->authorize('view', ProductFaq::class);

            $productFaq = ProductFaq::with('product')->findOrFail($id);
            return ApiResponseType::sendJsonResponse(
                true,
                'Product FAQ fetched successfully.',
                $productFaq
            );
        } catch (ModelNotFoundException) {
            return ApiResponseType::sendJsonResponse(
                false,
                'Product FAQ not found.'
            );
        }
    }

    /**
     * Show the form for editing the specified product FAQ.
     */
    public function edit($id): JsonResponse
    {
        try {
            $productFaq = ProductFaq::with('product')->findOrFail($id);
            return ApiResponseType::sendJsonResponse(
                true,
                'Product FAQ fetched successfully.',
                $productFaq
            );
        } catch (ModelNotFoundException) {
            return ApiResponseType::sendJsonResponse(
                false,
                'Product FAQ not found.'
            );
        }
    }

    /**
     * Update the specified product FAQ in storage.
     */
    public function update(StoreUpdateProductFaqRequest $request, $id): JsonResponse
    {
        try {
            $productFaq = ProductFaq::findOrFail($id);
            $this->authorize('update', $productFaq);
            $validated = $request->validated();

            $productFaq->update($validated);

            return ApiResponseType::sendJsonResponse(
                true,
                'Product FAQ updated successfully.',
                $productFaq
            );
        } catch (ModelNotFoundException) {
            return ApiResponseType::sendJsonResponse(
                false,
                'Product FAQ not found.'
            );
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(
                false,
                'You do not have permission to update this product FAQ.',
                [],
            );
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(
                false,
                'Failed to update product FAQ.',
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Remove the specified product FAQ from storage.
     */
    public function destroy($id): JsonResponse
    {
        try {
            $productFaq = ProductFaq::find($id);

            if (!$productFaq) {
                return ApiResponseType::sendJsonResponse(
                    false,
                    'Product FAQ not found.'
                );
            }

            $this->authorize('delete', $productFaq);
            $productFaq->delete();

            return ApiResponseType::sendJsonResponse(
                true,
                'Product FAQ deleted successfully.'
            );
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(
                false,
                'You do not have permission to delete this product FAQ.',
                [],
            );
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(
                false,
                'Failed to delete product FAQ.',
                ['error' => $e->getMessage()]
            );
        }
    }

    public function getProductFaqs(Request $request): JsonResponse
    {
        try {
            $this->authorize('viewAny', ProductFaq::class);

            $draw = $request->get('draw');
            $start = $request->get('start');
            $length = $request->get('length');
            $status = $request->get('status');
            $productId = $request->get('product_id');
            $searchValue = $request->get('search')['value'] ?? '';

            $orderColumnIndex = $request->get('order')[0]['column'] ?? 0;
            $orderDirection = $request->get('order')[0]['dir'] ?? 'asc';
            $columns = ['id', 'question', 'answer', 'status', 'created_at'];
            $orderColumn = $columns[$orderColumnIndex] ?? 'id';

            $query = ProductFaq::with(['product']);

            // Filter by seller if in seller panel
            if ($this->getPanel() === 'seller') {
                $query->whereHas('product', function ($q) {
                    $q->where('seller_id', $this->sellerId ?? "0");
                });
            }
            $totalRecords = ProductFaq::count();
            if (!empty($status)) {
                $query->where('status', $status);
            }

            if (!empty($productId)) {
                $query->where('product_id', $productId);
            }
            // Search filter
            if (!empty($searchValue)) {
                $query->where(function ($q) use ($searchValue) {
                    $q->where('question', 'like', "%{$searchValue}%")
                        ->orWhere('answer', 'like', "%{$searchValue}%")
                        ->orWhereHas('product', function ($p) use ($searchValue) {
                            $p->where('title', 'like', "%{$searchValue}%");
                        });
                });
            }


            $filteredRecords = $query->count();
            $data = $query
                ->orderBy($orderColumn, $orderDirection)
                ->skip($start)
                ->take($length)
                ->get()
                ->map(function ($faq) {
                    return [
                        'id' => $faq->id,
                        'product_title' => !empty($faq->product?->title)
                            ? "<a href='" . route($this->getPanel() . '.products.show', $faq->product->id) . "'>" . $faq->product->title . "</a>"
                            : '-',
                        'question' => $faq->question,
                        'answer' => $faq->answer,
                        'status' => view('partials.status', ['status' => $faq->status ?? ""])->render(),
                        'created_at' => $faq->created_at->format('Y-m-d'),
                        'action' => view('partials.actions', [
                            'modelName' => 'product-faq',
                            'id' => $faq->id,
                            'title' => $faq->question,
                            'mode' => 'model_view',
                            'editPermission' => $this->editPermission,
                            'deletePermission' => $this->deletePermission
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
        } catch (AuthorizationException $e) {
            return ApiResponseType::sendJsonResponse(success: false, message: 'labels.permission_denied', data: []);
        } catch (\Exception $e) {
            return ApiResponseType::sendJsonResponse(success: false, message: 'labels.failed_to_fetch_product_faqs: ' . $e->getMessage(), data: []);
        }
    }
}
