<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\AuthTrait;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    use AuthTrait;
    protected string $role = 'admin';

    public function loginAdmin(): View
    {
        return view('admin.auth.login');
    }

    public function logout(Request $request)
    {
        try {
            Auth::logout(); // Log the user out
            $request->session()->invalidate(); // Invalidate the session
            return redirect(route('admin.login'));

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('labels.logout_failed', ['error' => $e->getMessage()]),
                'data' => []
            ], 500);
        }
    }
}
