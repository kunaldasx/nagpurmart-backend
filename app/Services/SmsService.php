<?php

namespace App\Services;

use App\Enums\SettingTypeEnum;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    protected SettingService $settingService;

    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    /**
     * Send SMS using configured provider
     */
    public function sendSms(string $mobile, string $message): array
    {
        $authSetting = $this->settingService->getRawSetting(SettingTypeEnum::AUTHENTICATION());
        $config = $authSetting?->value ?? [];

        // Try to find From number in Body keys if not set explicitly
        if (empty($config['customSmsFromNumber']) && !empty($config['customSmsBodyKey'])) {
            $bodyKeys = $config['customSmsBodyKey'];
            $bodyValues = $config['customSmsBodyValue'] ?? [];
            
            foreach ($bodyKeys as $index => $key) {
                // Check for various capitalizations of "From"
                if (in_array(strtolower(trim($key)), ['from', 'from_number', 'sender_id']) && isset($bodyValues[$index])) {
                    $config['customSmsFromNumber'] = $bodyValues[$index];
                    break;
                }
            }
        }

        // Debug: Show what we received
        $configDebug = [
            'customSms' => $config['customSms'] ?? 'not set',
            'has_sid' => !empty($config['customSmsTokenAccountSid']),
            'has_token' => !empty($config['customSmsAuthToken']),
            'has_from' => !empty($config['customSmsFromNumber']),
            'sid_value' => isset($config['customSmsTokenAccountSid']) ? substr($config['customSmsTokenAccountSid'], 0, 10) . '...' : 'not set',
            'token_value' => isset($config['customSmsAuthToken']) ? 'length: ' . strlen($config['customSmsAuthToken']) : 'not set',
            'from_value' => $config['customSmsFromNumber'] ?? 'not set',
            'from_source' => empty($authSetting?->value['customSmsFromNumber']) && !empty($config['customSmsFromNumber']) ? 'extracted_from_body' : 'direct_setting',
            'raw_body_keys' => $config['customSmsBodyKey'] ?? [],
            'config_keys' => array_keys($config),
        ];

        if (empty($config['customSms'])) {
            return [
                'success' => false,
                'message' => 'Custom SMS is not enabled',
                'debug' => $configDebug
            ];
        }

        // Check for Twilio specifics (using SID/Token)
        if (!empty($config['customSmsTokenAccountSid']) && !empty($config['customSmsAuthToken']) && !empty($config['customSmsFromNumber'])) {
            return $this->sendTwilioSms($mobile, $message, $config);
        }

        // Fallback to generic custom SMS
        $configDebug['routing'] = 'Using generic custom SMS (Twilio fields not complete)';
        return array_merge(
            $this->sendCustomSms($mobile, $message, $config),
            ['config_debug' => $configDebug]
        );
    }

    /**
     * Send SMS via Twilio API
     */
    private function sendTwilioSms(string $mobile, string $message, array $config): array
    {
        try {
            $sid = $config['customSmsTokenAccountSid'] ?? null;
            $token = $config['customSmsAuthToken'] ?? null;
            $from = $config['customSmsFromNumber'] ?? null;

            // Validate required fields
            if (empty($sid) || empty($token) || empty($from)) {
                $debug = [
                    'has_sid' => !empty($sid),
                    'has_token' => !empty($token),
                    'has_from' => !empty($from),
                    'sid_value' => $sid ? substr($sid, 0, 10) . '...' : 'null',
                    'from_value' => $from ?? '+18597105161'
                ];
                
                Log::error('Twilio SMS missing credentials', $debug);
                
                return [
                    'success' => false,
                    'message' => 'Twilio credentials not configured properly',
                    'debug' => $debug
                ];
            }

            $url = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json";

            // Add + prefix to phone numbers if not present
            $to = (str_starts_with($mobile, '+')) ? $mobile : '+' . $mobile;
            $fromNumber = (str_starts_with($from, '+')) ? $from : '+' . $from;

            $requestData = [
                'to' => $to,
                'from' => $fromNumber,
                'sid' => substr($sid, 0, 10) . '...',
                'token_length' => strlen($token),
                'url' => $url
            ];

            Log::info('Sending Twilio SMS', $requestData);

            $response = Http::asForm()
                ->withBasicAuth($sid, $token)
                ->post($url, [
                    'To' => $to,
                    'From' => $fromNumber,
                    'Body' => $message,
                ]);

            if ($response->successful()) {
                Log::info('Twilio SMS sent successfully');
                return [
                    'success' => true,
                    'message' => 'SMS sent via Twilio',
                    'debug' => $requestData
                ];
            }

            $errorData = $response->json();
            $debugInfo = [
                'status' => $response->status(),
                'error' => $errorData,
                'request' => $requestData
            ];
            
            Log::error('Twilio SMS failed', $debugInfo);

            return [
                'success' => false,
                'message' => 'Twilio Gateway error: ' . $response->status(),
                'debug' => $debugInfo
            ];

        } catch (\Exception $e) {
            $error = [
                'exception' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ];
            
            Log::error('Twilio SMS exception', $error);
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'debug' => $error
            ];
        }
    }

    /**
     * Send SMS via Generic Custom HTTP API
     */
    private function sendCustomSms(string $mobile, string $message, array $config): array
    {
        try {
            $url = $config['customSmsUrl'] ?? '';
            $method = strtoupper($config['customSmsMethod'] ?? 'GET');
            
            if (empty($url)) {
                return ['success' => false, 'message' => 'SMS Gateway URL is empty'];
            }

            // Prepare headers
            $headers = [];
            if (!empty($config['customSmsHeaderKey'])) {
                foreach ($config['customSmsHeaderKey'] as $index => $key) {
                    if (!empty($key)) {
                        $headers[$key] = $config['customSmsHeaderValue'][$index] ?? '';
                    }
                }
            }

            // Prepare params (Query/Body)
            $params = [];
            if (!empty($config['customSmsParamsKey'])) {
                foreach ($config['customSmsParamsKey'] as $index => $key) {
                    if (!empty($key)) {
                        $params[$key] = $config['customSmsParamsValue'][$index] ?? '';
                    }
                }
            }
            
            // Body params
            if (!empty($config['customSmsBodyKey'])) {
                foreach ($config['customSmsBodyKey'] as $index => $key) {
                    if (!empty($key)) {
                        $params[$key] = $config['customSmsBodyValue'][$index] ?? '';
                    }
                }
            }

            // Replace placeholders in params
            foreach ($params as $key => $value) {
                if (is_string($value)) {
                    $params[$key] = str_replace(['{mobile}', '{message}'], [$mobile, $message], $value);
                }
            }

            // Also replace in URL if needed
            $url = str_replace(['{mobile}', '{message}'], [urlencode($mobile), urlencode($message)], $url);

            $request = Http::withHeaders($headers);

            if ($method === 'POST') {
                $response = $request->post($url, $params);
            } else {
                $response = $request->get($url, $params);
            }

            if ($response->successful()) {
                return ['success' => true, 'message' => 'SMS sent via Custom Gateway'];
            }

            Log::error('Custom SMS failed', ['response' => $response->body()]);
            return ['success' => false, 'message' => 'Custom Gateway error: ' . $response->status()];

        } catch (\Exception $e) {
            Log::error('Custom SMS exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
