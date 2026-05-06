<?php

namespace App\Http\Middleware;

use App\Enums\GuardNameEnum;
use App\Enums\Seller\SellerVerificationStatusEnum;
use App\Enums\SystemVendorTypeEnum;
use App\Models\Setting;
use App\Services\SettingService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;

class ValidateSeller
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param \Closure $next
     * @return Response
     */

    protected string $systemType;

    public function __construct()
    {
        $this->systemType = Setting::systemType();
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Try authenticating from token if no session user exists
        if (!$user) {
            $authResult = $this->authenticateFromToken($request);
            if ($authResult instanceof Response) {
                return $authResult; // redirect after stripping token
            }
            $user = Auth::user();
        }

        // Ensure user is authenticated
        if (!$user) {
            return redirect()->route('seller.login');
        }

        // Ensure user is using the seller guard
        if ($user->getDefaultGuardName() !== GuardNameEnum::SELLER() && $this->systemType === SystemVendorTypeEnum::MULTIPLE()) {
            Auth::logout();
            return redirect()->route('seller.login')->with('error', 'You do not have permission to access the seller panel.');
        }

        // Ensure user is associated to a seller model
        $seller = $user->seller();
        if (!$seller) {
            return $this->deny($request, __('labels.not_a_seller') ?? 'Not a seller account.');
        }

        // Ensure seller is approved
        $isImpersonating = session('impersonating_as_seller', false);
        if (!$isImpersonating && $seller->verification_status !== SellerVerificationStatusEnum::Approved()) {
            $message = __('labels.account_not_verified') ?? 'Your seller account is not approved yet.';
            if (!$request->expectsJson() && !$request->wantsJson()) {
                Auth::logout();
            }
            return $this->deny($request, $message, 403, [
                'verification_status' => $seller->verification_status,
            ]);
        }
        return $next($request);
    }

    /**
     * Attempt to authenticate user from a query param token.
     * Returns Response when redirect is needed to strip token, or null otherwise.
     */
    private function authenticateFromToken(Request $request): ?Response
    {
        $rawToken = $request->query('token');
        if (empty($rawToken)) {
            return null;
        }

        $accessToken = PersonalAccessToken::findToken($rawToken);
        if ($accessToken && $accessToken->tokenable instanceof User) {
            Auth::login($accessToken->tokenable);
            // Remove token from the URL to avoid leaking it via referrers
            return redirect()->to($request->url());
        }

        // Invalid/expired token
        return redirect()->route('seller.login')->with('error', __('labels.invalid_or_expired_token') ?? 'Invalid or expired token.');
    }

    /**
     * Unified denial handler for JSON and web requests.
     */
    private function deny(Request $request, string $message, int $status = 403, array $data = [])
    {
        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'data' => $data,
            ], $status);
        }
        return redirect()->route('seller.login')->with('error', $message);
    }
}
