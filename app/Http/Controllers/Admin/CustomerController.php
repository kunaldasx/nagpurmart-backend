<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdminPermissionEnum;
use App\Enums\DefaultSystemRolesEnum;
use App\Enums\SettingTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\GroceryList;
use App\Models\User;
use App\Services\CurrencyService;
use App\Services\SettingService;
use App\Traits\ChecksPermissions;
use App\Types\Api\ApiResponseType;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CustomerController extends Controller
{
    use AuthorizesRequests, ChecksPermissions;

    protected SettingService $settingService;
    protected CurrencyService $currencyService;

    public function __construct(SettingService $settingService, CurrencyService $currencyService)
    {
        $this->settingService = $settingService;
        $this->currencyService = $currencyService;
    }

    protected function isDemoModeEnabled(): bool
    {
        try {
            $resource = $this->settingService->getSettingByVariable(SettingTypeEnum::SYSTEM());
            $settings = $resource ? ($resource->toArray(request())['value'] ?? []) : [];
            return (bool)($settings['demoMode'] ?? false);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function index(): View
    {
        // Customer listing is controlled by explicit permission, not by the SystemUser policy
        if (!$this->hasPermission(AdminPermissionEnum::CUSTOMER_VIEW())) {
            abort(403, trans('labels.permission_denied'));
        }
        $columns = [
            ['data' => 'id', 'name' => 'id', 'title' => __('labels.id')],
            ['data' => 'name', 'name' => 'name', 'title' => __('labels.name')],
            ['data' => 'details', 'name' => 'details', 'title' => __('labels.details')],
            ['data' => 'wallet_balance', 'name' => 'wallet_balance', 'title' => __('labels.wallet_balance')],
            ['data' => 'grocery_lists', 'name' => 'grocery_lists', 'title' => 'Grocery lists'],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => __('labels.created_at')],
        ];

        return view('admin.customers.index', compact('columns'));
    }

    public function datatable(Request $request): JsonResponse
    {
        if (!$this->hasPermission(AdminPermissionEnum::CUSTOMER_VIEW())) {
            return ApiResponseType::sendJsonResponse(false, 'labels.permission_denied', [], 403);
        }

        $draw = $request->get('draw');
        $start = (int)$request->get('start', 0);
        $length = (int)$request->get('length', 10);
        $searchValue = $request->get('search')['value'] ?? '';

        $orderColumnIndex = $request->get('order')[0]['column'] ?? 0;
        $orderDirection = $request->get('order')[0]['dir'] ?? 'asc';

        $columns = ['id', 'name', 'email', 'mobile', 'created_at'];
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';

        // Customers: users that have role 'customer' and do not have admin/seller access panel
        $query = User::query()->with('wallet')->withCount('groceryLists')
            ->where(function ($q) {
                $q->whereNull('access_panel')
                    ->orWhere('access_panel', 'web');
            });

        $totalRecords = $query->count();

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('name', 'like', "%{$searchValue}%")
                    ->orWhere('email', 'like', "%{$searchValue}%");
            });
        }

        $filteredRecords = $query->count();

        $demo = $this->isDemoModeEnabled();
        $data = $query
            ->orderBy($orderColumn, $orderDirection)
            ->skip($start)
            ->take($length)
            ->get()
            ->map(function ($user) use ($demo) {
                $email = $user->email ?? '';
                $mobile = $user->mobile ?? '';
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'details' => $demo
                        ? Str::mask($email, '****', 3, 4) . ' / ' . Str::mask($mobile, '****', 3, 4)
                        : $email . ' / ' . $mobile,
                    'wallet_balance' => $this->currencyService->format($user?->wallet->balance ?? 0),
                    'grocery_lists' => '<a href="' . route('admin.customers.grocery-lists', $user->id) . '">' . $user->grocery_lists_count . '</a>',
                    'created_at' => $user->created_at?->format('Y-m-d'),
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

    public function groceryLists(int $id): View
    {
        if (!$this->hasPermission(AdminPermissionEnum::CUSTOMER_VIEW())) {
            abort(403, trans('labels.permission_denied'));
        }

        $customer = User::findOrFail($id);
        $lists = GroceryList::with(['items.product'])
            ->where('user_id', $customer->id)
            ->latest()
            ->get();

        return view('admin.customers.grocery_lists', compact('customer', 'lists'));
    }

    /**
     * Export customers as CSV
     */
    public function export(Request $request)
    {
        if (!$this->hasPermission(AdminPermissionEnum::CUSTOMER_EXPORT())) {
            return ApiResponseType::sendJsonResponse(false, 'labels.permission_denied', [], 403);
        }

        $filename = 'customers_' . now()->format('Y_m_d_H_i_s') . '.csv';

        $callback = function () {
            $handle = fopen('php://output', 'w');
            // CSV Header
            fputcsv($handle, ['ID', 'Name', 'Email', 'Mobile', 'Created At']);

            User::query()
                ->where(function ($q) {
                    $q->whereNull('access_panel')
                        ->orWhere('access_panel', 'web');
                })
                ->orderBy('id', 'desc')
                ->chunk(500, function ($users) use ($handle) {
                    foreach ($users as $user) {
                        fputcsv($handle, [
                            $user->id,
                            $user->name,
                            $user->email,
                            $user->mobile,
                            optional($user->created_at)->format('Y-m-d H:i:s'),
                        ]);
                    }
                });

            fclose($handle);
        };

        return Response::stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
