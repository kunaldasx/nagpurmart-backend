<?php

namespace Froiden\LaravelInstaller\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use App\Services\LicenseValidator;
use Froiden\LaravelInstaller\Helpers\Reply;

class LicenseController extends Controller
{
    /**
     * Show the license form step.
     */
    public function form()
    {
        return view('vendor.installer.license');
    }

    /**
     * Verify the license against remote API and persist to .env
     */
    public function verify(Request $request)
    {
        $purchaseCode = (string) $request->get('purchase_code');
        $domainUrl = (string) $request->get('domain_url', request()->getSchemeAndHttpHost());

        if (trim($purchaseCode) === '' || trim($domainUrl) === '') {
            return Reply::error('License Error: Purchase code and domain are required.');
        }

        try {
            $validator = new LicenseValidator();
            $result = $validator->validate($purchaseCode, $domainUrl);
        } catch (\Throwable $e) {
            return Reply::error('License Error: Unable to reach license server.');
        }

        if ($result['success'] == false) {
            $msg = $result['message'] ?? 'License verification failed.';
            return Reply::error('License Error: ' . $msg);
        }

        $token = $result['data']['token'] ?? '';
        $signature = LicenseValidator::signature($purchaseCode, $domainUrl, $token);

        // Persist LICENSE_* to .env
        $envPath = base_path('.env');
        if (!file_exists($envPath)) {
            touch($envPath);
        }
        $content = file_get_contents($envPath);
        $rows = explode("\n", (string)$content);
        $unwanted = 'LICENSE_PURCHASE_CODE|LICENSE_DOMAIN_URL|LICENSE_TOKEN|LICENSE_SIGNATURE';
        $cleanArray = preg_grep("/{$unwanted}/i", $rows, PREG_GREP_INVERT);
        $cleanString = implode("\n", $cleanArray);

        $licenseBlock = 'LICENSE_PURCHASE_CODE="'.$purchaseCode.'"' . "\n" .
            'LICENSE_DOMAIN_URL="'.$domainUrl.'"' . "\n" .
            'LICENSE_TOKEN="'.$token.'"' . "\n" .
            'LICENSE_SIGNATURE="'.$signature.'"' . "\n";

        try {
            file_put_contents($envPath, rtrim($cleanString, "\n")."\n".$licenseBlock);
        } catch (\Throwable $e) {
            return Reply::error('License Error: Failed to write license to environment file.');
        }

        return Reply::redirect(route('LaravelInstaller::environment'), 'License verified successfully.');
    }
}
