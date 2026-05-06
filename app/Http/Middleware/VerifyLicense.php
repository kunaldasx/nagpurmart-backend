<?php

namespace App\Http\Middleware;

use App\Services\LicenseValidator;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class VerifyLicense
{
    public function handle(Request $request, Closure $next)
    {
        // Skip installer routes
        if ($request->is('install*') || $request->is('installer*') || $request->is('license/revalidate*')) {
            return $next($request);
        }

        $purchase = config('app.license_purchase');
        $domain = url('/');
        $token = config('app.license_token');
        $signature = config('app.license_signature');

        if (!$purchase || !$domain || !$token || !$signature) {
            // Redirect to revalidation page with intended URL
            $intended = $request->fullUrl();
            return redirect()->route('license.revalidate', ['intended' => $intended, 'message' => 'Application is not licensed.']);
        }

        // Verify signature integrity to make tampering harder
        $domain = str_replace('/public', '', $domain);
        $expected = LicenseValidator::signature($purchase, $domain, $token);

        if (hash_equals($expected, (string)$signature) !== true) {
            $intended = $request->fullUrl();
            return redirect()->route('license.revalidate', ['intended' => $intended, 'message' => 'License signature mismatch.']);
        }

        // Periodic remote revalidation
        $cacheKey = 'license_recheck_ts';
        $last = Cache::get($cacheKey);
        $interval = (int)config('license.recheck_minutes', 720);
        $now = now()->timestamp;
        if (!$last || ($now - (int)$last) > ($interval * 60)) {
            $client = new LicenseValidator();
            $res = $client->validate($purchase, $domain);
            if ($res['success'] == false) {
                $intended = $request->fullUrl();
                return redirect()->route('license.revalidate', ['intended' => $intended, 'message' => 'License validation failed: ' . (($res['data']['message'] ?? 'Unknown'))]);
            }
            // Optionally update token if server returns a new one
            $newToken = $res['data']['token'] ?? null;
            if ($newToken && $newToken !== $token) {
                // Refresh signature in runtime (cannot write .env here safely)
                config(['app.license_token' => $newToken]);
            }
            Cache::put($cacheKey, $now, now()->addMinutes($interval));
        }

        return $next($request);
    }
}
