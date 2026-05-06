<?php

namespace App\Services;

use App\Types\Api\ApiResponseType;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LicenseValidator
{
    protected Client $http;

    public function __construct(?Client $http = null)
    {
        $this->http = $http ?: new Client([
            'timeout' => 10,
        ]);
    }

    public function validate(string $purchaseCode, string $domainUrl): array
    {
        $endpoint = config('license.endpoint', 'https://validator.infinitietech.com/home/validator');

        try {
            $response = $this->http->get($endpoint, [
                'query' => [
                    'purchase_code' => $purchaseCode,
                    'domain_url' => $domainUrl,
                ],
                'http_errors' => false,
            ]);

            $data = json_decode((string)$response->getBody(), true) ?: [];
        } catch (\Exception $e) {
            Log::error($e);
        }

        return ApiResponseType::toArray(success: ($data['error'] ?? false) == false ? true : false, message: $data['message'] ?? 'Error', data: $data ?? []);
    }

    public static function signature(string $purchaseCode, string $domainUrl, string $token): string
    {
        $key = env('APP_KEY', 'app-key-missing');
        return hash_hmac('sha256', $purchaseCode . '|' . $domainUrl . '|' . $token, $key);
    }
}
