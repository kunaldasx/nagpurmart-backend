<?php

namespace App\Http\Controllers;

use App\Services\LicenseValidator;
use App\Types\Api\ApiResponseType;
use Froiden\LaravelInstaller\Helpers\Reply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class LicenseRevalidateController extends Controller
{
    public function form(Request $request)
    {
        $domain = request()->getSchemeAndHttpHost();
        $intended = $request->get('intended', url('/'));
        return view('license.revalidate', compact('domain', 'intended'));
    }

    public function verify(Request $request)
    {
        $validated = $request->validate([
            'purchase_code' => 'required',
        ]);
        $purchaseCode = $validated['purchase_code'];
        $domainUrl = request()->getSchemeAndHttpHost();
        $intended = (string)$request->get('intended', url('/'));

        try {
            $validator = new LicenseValidator();
            $result = $validator->validate($purchaseCode, $domainUrl);
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(success: false, message: 'License Error: Unable to reach license server.');
        }

        if (!($result['success'] ?? false)) {
            $msg = $result['message'] ?? 'License verification failed.';
            return ApiResponseType::sendJsonResponse(false, $msg);
        }

        $token = $result['data']['token'] ?? '';
        $signature = LicenseValidator::signature($purchaseCode, $domainUrl, $token);

        // Persist LICENSE_* to .env
        $envPath = base_path('.env');
        if (!file_exists($envPath)) {
            touch($envPath);
        }
        $content = file_get_contents($envPath) ?: '';
        $rows = explode("\n", (string)$content);
        $unwanted = 'LICENSE_PURCHASE_CODE|LICENSE_DOMAIN_URL|LICENSE_TOKEN|LICENSE_SIGNATURE';
        $cleanArray = preg_grep("/{$unwanted}/i", $rows, PREG_GREP_INVERT);
        $cleanString = implode("\n", $cleanArray);

        $licenseBlock = 'LICENSE_PURCHASE_CODE="' . $purchaseCode . '"' . "\n" .
            'LICENSE_DOMAIN_URL="' . $domainUrl . '"' . "\n" .
            'LICENSE_TOKEN="' . $token . '"' . "\n" .
            'LICENSE_SIGNATURE="' . $signature . '"' . "\n";

        try {
            file_put_contents($envPath, rtrim($cleanString, "\n") . "\n" . $licenseBlock);
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(
                success: false,
                message: 'License Error: Failed to write license to environment file.'
            );
        }

        // Clear any cached recheck timestamp so middleware won't immediately recheck
        try {
            Cache::forget('license_recheck_ts');
        } catch (\Throwable $e) {
        }
        return ApiResponseType::sendJsonResponse(
            success: true,
            message: 'License verified successfully.',
            data: [
                'redirect_url' => $intended ?: url('/')
            ]
        );
    }
}
