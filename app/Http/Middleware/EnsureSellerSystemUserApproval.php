<?php

namespace App\Http\Middleware;

use App\Models\Seller;
use App\Models\SellerUser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSellerSystemUserApproval
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        if (!$user) {
            return $next($request);
        }

        $membership = SellerUser::where('user_id', $user->id)->first();
        if (!$membership) {
            return $next($request);
        }

        $seller = Seller::find($membership->seller_id);
        if ($seller && (int) $seller->user_id === (int) $user->id) {
            return $next($request);
        }

        if ($membership->login_approval_status === 'approved'
            && $membership->login_approved_until
            && $membership->login_approved_until->isFuture()) {
            return $next($request);
        }

        if ($membership->login_approval_status === 'approved') {
            $membership->update([
                'login_approval_status' => 'disapproved',
                'login_approved_until' => null,
            ]);
        }

        $user->tokens()->delete();
        Auth::logout();
        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Seller approval is required to access this account.',
                'data' => ['login_approval_status' => $membership->fresh()->login_approval_status],
            ], 403);
        }

        return redirect()->route('seller.login')->with('error', 'Seller approval is required to access this account.');
    }
}