<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DefaultSystemRolesEnum;
use App\Http\Controllers\Controller;
use App\Models\SellerUser;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    /**
     * Start impersonating as the linked seller (Single Vendor mode only).
     */
    public function toSeller(): RedirectResponse
    {
        $user = Auth::user();

        // Allowed only when system is single vendor and user is Super Admin
        if (!Setting::isSystemVendorTypeSingle() || !$user?->hasRole(DefaultSystemRolesEnum::SUPER_ADMIN())) {
            return redirect()->back()->with('error', __('labels.permission_denied') ?? 'Permission denied');
        }

        // Ensure super admin is attached to a seller via pivot
        $pivot = SellerUser::where('user_id', $user->id)->first();
        if (!$pivot) {
            return redirect()->back()->with('error', __('labels.seller_not_found') ?? 'Seller not found');
        }

        // Set session flags for UI/logic where needed
        session([
            'impersonating_as_seller' => true,
            'impersonated_seller_id' => $pivot->seller_id,
        ]);

        return redirect()->route('seller.dashboard')->with('success', __('labels.switched_to_seller') ?? 'Switched to Seller');
    }

    /**
     * Stop impersonation and go back to admin.
     */
    public function toAdmin(): RedirectResponse
    {
        // Clear any impersonation flags
        session()->forget(['impersonating_as_seller', 'impersonated_seller_id']);
        return redirect()->route('admin.dashboard')->with('success', __('labels.back_to_admin') ?? 'Back to Admin');
    }
}
