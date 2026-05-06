<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DefaultSystemRolesEnum;
use App\Enums\GuardNameEnum;
use App\Enums\SettingTypeEnum;
use App\Enums\SystemVendorTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\Seller;
use App\Models\SellerUser;
use App\Models\Setting;
use App\Models\User;
use App\Services\SettingService;
use App\Types\Api\ApiResponseType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class VendorTypeController extends Controller
{
    protected SettingService $settingService;

    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    /**
     * Show the system vendor type selection form.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $resource = $this->settingService->getSettingByVariable(SettingTypeEnum::SYSTEM());
        $settings = $resource?->toArray($request)['value'] ?? [];

        // If already selected, redirect to dashboard with info
        if (!empty($settings['systemVendorType'])) {
            return redirect()->route('admin.dashboard')->with('info', __('labels.system_vendor_type_already_selected') ?? 'System vendor type already selected.');
        }

        return view('admin.vendor', compact('settings'));
    }

    /**
     * Persist the selected system vendor type.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'systemVendorType' => 'required|in:' . implode(',', SystemVendorTypeEnum::values()),
        ]);

        // Additional validation when Single Vendor is selected
        if ($request->input('systemVendorType') === SystemVendorTypeEnum::SINGLE()) {
            $request->validate([
                'seller_name' => 'required|string|max:255',
                'seller_email' => 'required|email:rfc,dns|unique:users,email',
                'seller_password' => 'required|string|min:8|confirmed',
                'seller_mobile' => 'required|string|max:20|unique:users,mobile',
            ]);
        }

        $resource = Setting::find(SettingTypeEnum::SYSTEM());
        $current = $resource?->toArray($request)['value'] ?? [];

        if (!empty($current['systemVendorType'])) {
            return ApiResponseType::sendJsonResponse(false, __('labels.system_vendor_type_already_selected'));
        }

        $selectedType = $request->input('systemVendorType');
        $newValues = $current;
        $newValues['systemVendorType'] = $selectedType;

        try {
            DB::transaction(function () use ($newValues, $selectedType, $request) {
                // Save setting
                $this->settingService->saveSetting(SettingTypeEnum::SYSTEM(), $newValues);

                // If Single Vendor is selected, create a brand-new Seller User (do not reuse existing user)
                if ($selectedType === SystemVendorTypeEnum::SINGLE()) {
                    // Create new seller user
                    /** @var User $newSellerUser */
                    $newSellerUser = User::create([
                        'name' => $request->input('seller_name'),
                        'email' => $request->input('seller_email'),
                        'mobile' => $request->input('seller_mobile'),
                        'password' => $request->input('seller_password'),
                        'status' => true,
                        'access_panel' => GuardNameEnum::SELLER(),
                    ]);

                    // Assign default Seller role (guard will resolve from access_panel)
                    try {
                        $newSellerUser->assignRole(DefaultSystemRolesEnum::SELLER());
                    } catch (\Throwable $e) {
                        Log::warning('Failed to assign SELLER role to new seller user', ['error' => $e->getMessage()]);
                    }

                    // Create seller entry and attach the created user in seller_user pivot as super admin owner
                    $seller = Seller::create([
                        'user_id' => $newSellerUser->id,
                    ]);

                    // Link the new user with the newly created seller in pivot table
                    SellerUser::create([
                        'user_id' => Auth::user()?->id,
                        'seller_id' => $seller->id,
                    ]);
                }
            });
        } catch (\Throwable $e) {
            Log::error('Failed to save system vendor type: ' . $e->getMessage(), ['exception' => $e]);
            return ApiResponseType::sendJsonResponse(false, __('labels.unexpected_error') . ': ' . $e->getMessage());
        }

        return ApiResponseType::sendJsonResponse(true, __('labels.system_vendor_type_saved'));
    }
}
