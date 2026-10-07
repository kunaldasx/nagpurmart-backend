<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Seller;
use App\Models\SellerUser;
use App\Services\SellerUserLoginApprovalService;
use App\Traits\AuthTrait;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    use AuthTrait;
    protected string $role = 'seller';

    public function loginSeller(): \Illuminate\Http\Response
    {
        return response()->view('seller.auth.login')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    public function pendingApproval(): View|JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('seller.login');
        }

        $status = app(SellerUserLoginApprovalService::class)->getAccessStatus($user);
        if ($status['approved']) {
            return redirect()->route('seller.dashboard');
        }

        return view('seller.auth.pending-approval', ['user' => $user]);
    }

    public function pendingApprovalStatus(): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Please sign in again.'], 401);
        }

        $status = app(SellerUserLoginApprovalService::class)->getAccessStatus($user);

        return response()->json([
            'success' => true,
            'data' => [
                'approved' => $status['approved'],
                'login_approval_status' => $status['status'],
                'approved_until' => $status['approved_until']?->toISOString(),
                'redirect_url' => $status['approved'] ? route('seller.dashboard') : null,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        try {
            Auth::logout(); // Log the user out
            $request->session()->invalidate(); // Invalidate the session
            return redirect(route('seller.login'));

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('labels.logout_failed', ['error' => $e->getMessage()]),
                'data' => []
            ], 500);
        }
    }
}
