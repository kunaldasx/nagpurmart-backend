<?php

namespace App\Http\Controllers;

use App\Enums\AdminPermissionEnum;
use App\Enums\DefaultSystemRolesEnum;
use App\Enums\Seller\SellerVerificationStatusEnum;
use App\Enums\Seller\SellerVisibilityStatusEnum;
use App\Enums\SpatieMediaCollectionName;
use App\Events\Seller\SellerStatusUpdated;
use App\Events\Seller\SellerUpdated;
use App\Http\Requests\Seller\StoreSellerRequest;
use App\Http\Requests\Seller\UpdateSellerRequest;
use App\Models\Seller;
use App\Enums\SettingTypeEnum;
use App\Models\Setting;
use App\Services\SettingService;
use App\Services\SellerService;
use App\Traits\ChecksPermissions;
use App\Traits\PanelAware;
use App\Types\Api\ApiResponseType;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SellerController extends Controller
{
    use ChecksPermissions, PanelAware, AuthorizesRequests;

    protected bool $editPermission = false;
    protected bool $deletePermission = false;
    protected bool $createPermission = false;

    protected $sellerService;
    protected SettingService $settingService;

    public function __construct(SellerService $sellerService, SettingService $settingService)
    {
        $this->sellerService = $sellerService;
        $this->settingService = $settingService;
        if ($this->getPanel() === 'admin') {
            $this->editPermission = $this->hasPermission(AdminPermissionEnum::SELLER_EDIT());
            $this->deletePermission = $this->hasPermission(AdminPermissionEnum::SELLER_DELETE());
            $this->createPermission = $this->hasPermission(AdminPermissionEnum::SELLER_CREATE());
        }
        if (Setting::isSystemVendorTypeSingle()) {
            $this->createPermission = false;
        }
    }

    private function isDemoModeEnabled(): bool
    {
        $resource = $this->settingService->getSettingByVariable(SettingTypeEnum::SYSTEM());
        $settings = $resource ? ($resource->toArray(request())['value'] ?? []) : [];
        return (bool)($settings['demoMode'] ?? false);
    }

    private function maskMobile(?string $mobile): string
    {
        if (!$mobile) {
            return '';
        }
        // Keep last 3 digits visible, mask the rest
        $len = strlen($mobile);
        if ($len <= 3) {
            return str_repeat('*', $len);
        }
        $visible = substr($mobile, -3);
        return str_repeat('*', $len - 3) . $visible;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $this->authorize('viewAny', Seller::class);
        $columns = [
            ['data' => 'id', 'name' => 'id', 'title' => __('labels.id')],
            ['data' => 'seller', 'name' => 'seller', 'title' => __('labels.seller')],
            ['data' => 'email', 'name' => 'email', 'title' => __('labels.email')],
            ['data' => 'mobile', 'name' => 'mobile', 'title' => __('labels.mobile')],
            ['data' => 'verification_status', 'name' => 'verification_status', 'title' => __('labels.verification_status')],
            ['data' => 'visibility_status', 'name' => 'visibility_status', 'title' => __('labels.visibility_status')],
            ['data' => 'stores', 'name' => 'stores', 'title' => __('labels.stores')],
            ['data' => 'plan', 'name' => 'plan', 'title' => __('labels.plan')],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => __('labels.created_at')],
            ['data' => 'action', 'name' => 'action', 'title' => __('labels.action'), 'orderable' => false, 'searchable' => false],
        ];
        $editPermission = $this->editPermission;
        $deletePermission = $this->deletePermission;
        $createPermission = $this->createPermission;

        return view($this->panelView('sellers.index'), compact('columns', 'editPermission', 'deletePermission', 'createPermission'));
    }

    /**
     * Show the form for creating a new resource.
     * @throws AuthorizationException
     */
    public function create(): View
    {
        $this->authorize('create', Seller::class);
        return view($this->panelView('sellers.form'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSellerRequest $request): JsonResponse
    {
        try {
            $this->authorize('create', Seller::class);

            if (Setting::isSystemVendorTypeSingle()) {
                return ApiResponseType::sendJsonResponse(
                    success: false,
                    message: __('labels.seller_registration_disabled'),
                    status: 403
                );
            }

            $seller = $this->sellerService->createSeller(
                $request->validated(),
                $request->allFiles()
            );

            return ApiResponseType::sendJsonResponse(
                true,
                'labels.seller_created_successfully',
                $seller,
                201
            );
        } catch (\Exception $e) {
            $statusCode = method_exists($e, 'getCode') && $e->getCode() ? $e->getCode() : 500;
            $statusCode = in_array($statusCode, [404, 422, 500]) ? $statusCode : 500;

            $message = match ($statusCode) {
                404 => 'labels.user_not_found',
                422 => $e->getMessage() === 'Seller already exists for this user'
                    ? 'labels.seller_already_exists'
                    : 'labels.validation_failed',
                default => 'labels.error_occurred',
            };

            return ApiResponseType::sendJsonResponse(
                false,
                $message,
                ['error' => $e->getMessage()],
                $statusCode
            );
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Optional: implement show logic if needed
        return;
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id): View
    {
        $seller = Seller::with(['user', 'countryDetails'])->find($id);

        if (!$seller) {
            abort(404);
        }
        $this->authorize('update', $seller);
        $seller->business_license = $seller->getFirstMediaUrl(SpatieMediaCollectionName::BUSINESS_LICENSE()) ?? null;
        $seller->articles_of_incorporation = $seller->getFirstMediaUrl(SpatieMediaCollectionName::ARTICLES_OF_INCORPORATION()) ?? null;
        $seller->national_identity_card = $seller->getFirstMediaUrl(SpatieMediaCollectionName::NATIONAL_IDENTITY_CARD()) ?? null;
        $seller->authorized_signature = $seller->getFirstMediaUrl(SpatieMediaCollectionName::AUTHORIZED_SIGNATURE()) ?? null;
//        dd($seller);
        return view($this->panelView('sellers.form'), compact('seller'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSellerRequest $request, string $id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $seller = Seller::find($id);
            if (!$seller) {
                return ApiResponseType::sendJsonResponse(false, 'labels.seller_not_found', [], 404);
            }
            $this->authorize('update', $seller);
            $request->validated();
            $currentVerificationStatus = $seller->verification_status;
            $user = $seller->user;
            if (!$user->hasRole(DefaultSystemRolesEnum::SELLER())) {
                $user->assignRole('seller');
            }
            $user->update($request->only(['name']));

            $validated = $request->safe()->except(['business_license', 'articles_of_incorporation', 'national_identity_card', 'authorized_signature']);

            // Unset image fields before updating the Seller
            $imageFields = [
                'business_license',
                'articles_of_incorporation',
                'national_identity_card',
                'authorized_signature'
            ];

            $seller->update($validated);

            // Handle image uploads via Spatie
            foreach ($imageFields as $field) {
                if ($request->hasFile($field)) {
                    $seller->addMediaFromRequest($field)->toMediaCollection($field);
                }
            }

            DB::commit();
            if ($request->verification_status !== $currentVerificationStatus) {
                event(new SellerStatusUpdated($seller, $user));
            }
            event(new SellerUpdated($seller, $user));

            return ApiResponseType::sendJsonResponse(true, 'labels.seller_updated_successfully', $seller);
        } catch (ValidationException $e) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(false, 'labels.validation_failed', $e->errors(), 422);
        } catch (Exception $e) {
            DB::rollBack();
            return ApiResponseType::sendJsonResponse(false, 'labels.error_occurred', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $seller = Seller::find($id);
            if (!$seller) {
                return ApiResponseType::sendJsonResponse(false, 'labels.seller_not_found', [], 404);
            }
            $this->authorize('delete', $seller);
            $seller->delete();
            $seller->media()->each(function ($media) {
                $media->delete();
            });
            return ApiResponseType::sendJsonResponse(true, 'labels.seller_deleted_successfully');
        } catch (Exception $e) {
            return ApiResponseType::sendJsonResponse(false, 'labels.error_occurred', ['error' => $e->getMessage()], 500);
        }
    }

    public function getSellers(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Seller::class);

        $draw = $request->get('draw');
        $start = $request->get('start', 0);
        $length = $request->get('length', 10);
        $searchValue = $request->input('search.value', '');

        $orderColumnIndex = $request->input('order.0.column', 0);
        $orderDirection = $request->input('order.0.dir', 'asc');

        $columns = ['id', 'seller', 'email', 'mobile', 'verification_status', 'visibility_status', 'created_at'];
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';

        $query = Seller::with(['user', 'stores', 'activeSubscription.plan']);
        $totalRecords = Seller::count();
        $filteredRecords = $totalRecords;

        // Search filter
        if ($searchValue) {
            $query->where(function ($q) use ($searchValue) {
                $q->whereHas('user', function ($uq) use ($searchValue) {
                    $uq->where('name', 'like', "%{$searchValue}%")
                        ->orWhere('email', 'like', "%{$searchValue}%")
                        ->orWhere('mobile', 'like', "%{$searchValue}%");
                })
                    ->orWhere('verification_status', 'like', "%{$searchValue}%")
                    ->orWhere('visibility_status', 'like', "%{$searchValue}%");
            });
            $filteredRecords = $query->count();
        }


        if (in_array($orderColumn, ['seller', 'email', 'mobile'])) {
            $query->join('users', 'sellers.user_id', '=', 'users.id');
            if ($orderColumn === 'seller') {
                $query->orderBy('users.name', $orderDirection);
            } elseif ($orderColumn === 'email') {
                $query->orderBy('users.email', $orderDirection);
            } else {
                $query->orderBy('users.mobile', $orderDirection);
            }
            $query->select('sellers.*');
        } else {
            $query->orderBy($orderColumn, $orderDirection);
        }

        $sellers = $query->skip($start)->take($length)->get();

        $editPermission = $this->editPermission;
        $deletePermission = $this->deletePermission;

        $demo = $this->isDemoModeEnabled();
        $data = $sellers->map(function ($seller) use ($editPermission, $deletePermission, $demo) {
            $stores_count = count($seller->stores);
            $email = $seller->user->email ?? '';
            $mobile = $seller->user->mobile ?? '';
            return [
                'id' => $seller->id,
                'seller' => $seller->user->name ?? '',
                'email' => $demo ? Str::mask($email, '****', 3, 4) : $email,
                'mobile' => $demo ? Str::mask($mobile, '****', 3, 4) : $mobile,
                'verification_status' => view('partials.status', ['status' => $seller->verification_status])->render(),
                'visibility_status' => view('partials.status', ['status' => $seller->visibility_status])->render(),
                'stores' => '<a href="' . route('admin.sellers.store.show.index', ['id' => $seller->id]) . '" class="' . ($stores_count <= 0 ? 'pointer-events-none' : '') . '"><span class="badge bg-info-lt text-uppercase">' . $stores_count . " " . ($stores_count > 1 ? __('labels.stores') : __('labels.store')) . '</span></a>',
                'plan' => $seller->activeSubscription?->plan?->name ?? 'N/A',
                'created_at' => $seller->created_at->toDateTimeString(),
                'action' => view('partials.actions', [
                    'modelName' => 'seller',
                    'id' => $seller->id,
                    'title' => $seller->user->name,
                    'mode' => 'page_view',
                    'route' => route('admin.sellers.edit', ['id' => $seller->id]),
                    'editPermission' => $editPermission,
                    'deletePermission' => $deletePermission
                ])->render(),
            ];
        });

        return response()->json([
            'draw' => intval($draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $query = $request->input('search', '');
        if (!empty($request->input('find'))) {
            $sellers = Seller::whereHas('user', function ($q) use ($request) {
                $q->where('name', $request->input('find'));
            })->with('user')->get();
        } else {
            $sellers = Seller::whereHas('user', function ($q) use ($query) {
                $q->where(function ($sub) use ($query) {
                    $sub->where('name', 'like', "%$query%")
                        ->orWhere('email', 'like', "%$query%")
                        ->orWhere('mobile', 'like', "%$query%");
                });
            })
                ->with('user')
                ->limit(20)
                ->get();
        }
        // Format for TomSelect
        $demo = $this->isDemoModeEnabled();
        $results = $sellers->map(function ($seller) use ($demo) {
            $email = $seller->user->email ?? '';
            $mobile = $seller->user->mobile ?? '';
            $maskedEmail = $demo ? Str::mask($email, '****', 3, 4) : $email;
            $maskedMobile = $demo ? Str::mask($mobile, '****', 3, 4) : $mobile;
            return [
                'id' => $seller->id,
                'value' => $seller->id,
                'text' => $seller->user->name,
                'email' => $maskedEmail,
                'mobile' => $maskedMobile,
                'customProperties' => '<span class="badge bg-info-lt">' . e($maskedEmail) . '</span>',
            ];
        });

        return response()->json($results);
    }
}
