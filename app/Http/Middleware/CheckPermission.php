<?php

namespace App\Http\Middleware;

use App\Enums\DefaultSystemRolesEnum;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $permission
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = Auth::user();
        $panel = $request->segment(1);

        // Check if the user is authenticated
        if (!$user) {
            // Redirect to the appropriate login based on panel
            if ($panel === 'seller') {
                return redirect()->route('seller.login');
            }
            return redirect()->route('admin.login');
        }
        // If seller panel, allow default SELLER role to access full module
        if ($panel === 'seller' && $user->hasRole(DefaultSystemRolesEnum::SELLER())) {
            return $next($request);
        }

        // Check if the user has the required permission or is Super Admin
        if ($user->hasPermissionTo($permission) || $user->hasRole(DefaultSystemRolesEnum::SUPER_ADMIN())) {
            return $next($request);
        }

        // If user doesn't have the required permission, return a 403 response
        return response()->view('errors.403', ['message' => 'You do not have permission to access this resource.'], 403);
    }
}
