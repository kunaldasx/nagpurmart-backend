<?php

namespace App\Http\Middleware;

use App\Enums\DefaultSystemRolesEnum;
use App\Enums\GuardNameEnum;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ValidateAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param \Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Check if user is authenticated
        if (!$user) {
            return redirect()->route('admin.login');
        }
        // Check if user has admin guard or Super Admin role
        if ($user->getDefaultGuardName() == GuardNameEnum::ADMIN() || $user->hasRole(DefaultSystemRolesEnum::SUPER_ADMIN())) {
            return $next($request);
        }

        // If user doesn't have admin access, log them out and redirect to login
        Auth::logout();
        return redirect()->route('admin.login')->with('error', 'You do not have permission to access the admin panel.');
    }
}
