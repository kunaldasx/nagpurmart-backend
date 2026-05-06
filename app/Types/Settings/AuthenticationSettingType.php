<?php

namespace App\Types\Settings;

use App\Interfaces\SettingInterface;
use App\Traits\SettingTrait;

class AuthenticationSettingType implements SettingInterface
{
    use SettingTrait;

    public bool $customSms = false;
    public string $customSmsUrl = '';
    public string $customSmsMethod = '';
    public string $googleRecaptchaSiteKey = '';
    public string $customSmsTokenAccountSid = '';
    public string $customSmsAuthToken = '';
    public string $customSmsTextFormatData = "";
    public array $customSmsHeaderKey = [];
    public array $customSmsHeaderValue = [];
    public array $customSmsParamsKey = [];
    public array $customSmsParamsValue = [];
    public array $customSmsBodyKey = [];
    public array $customSmsBodyValue = [];
    public bool $firebase = false;
    public string $fireBaseApiKey = "";
    public string $fireBaseAuthDomain = "";
    public string $fireBaseDatabaseURL = "";
    public string $fireBaseProjectId = "";
    public string $fireBaseStorageBucket = "";
    public string $fireBaseMessagingSenderId = "";
    public string $fireBaseAppId = "";
    public string $fireBaseMeasurementId = "";
    public bool $appleLogin = false;
    public bool $googleLogin = false;
    public bool $facebookLogin = false;
    public string $googleApiKey = '';
    public string $smsGateway = '';

    protected static function getValidationRules(): array
    {
        return [
            'customSms' => 'nullable|boolean',
            'customSmsUrl' => 'required_if:customSms,true|nullable|url',
            'customSmsMethod' => 'required_if:customSms,true|nullable|in:GET,POST',
            'customSmsTokenAccountSid' => 'required_if:customSms,true|nullable|string',
            'customSmsAuthToken' => 'required_if:customSms,true|nullable|string',
            'customSmsTextFormatData' => 'required_if:customSms,true|nullable|string',
            'customSmsHeaderKey' => 'required_if:customSms,true|array|nullable',
            'customSmsHeaderValue' => 'required_if:customSms,true|array|nullable',
            'customSmsParamsKey' => 'required_if:customSms,true|array|nullable',
            'customSmsParamsValue' => 'required_if:customSms,true|array|nullable',
            'customSmsBodyKey' => 'required_if:customSms,true|array|nullable',
            'customSmsBodyValue' => 'required_if:customSms,true|array|nullable',
            'firebase' => 'nullable|boolean',
            'fireBaseApiKey' => 'required_if:firebase,true|nullable|string',
            'fireBaseAuthDomain' => 'required_if:firebase,true|nullable|string',
            'fireBaseDatabaseURL' => 'required_if:firebase,true|nullable|url',
            'fireBaseProjectId' => 'required_if:firebase,true|nullable|string',
            'fireBaseStorageBucket' => 'required_if:firebase,true|nullable|string',
            'fireBaseMessagingSenderId' => 'required_if:firebase,true|nullable|string',
            'fireBaseAppId' => 'required_if:firebase,true|nullable|string',
            'fireBaseMeasurementId' => 'required_if:firebase,true|nullable|string',
            'googleRecaptchaSiteKey' => 'nullable|string',
            'appleLogin' => 'boolean',
            'googleLogin' => 'boolean',
//            'facebookLogin' => 'boolean',
            'googleApiKey' => 'nullable|string',
            'smsGateway' => 'nullable|string|in:custom,firebase',
        ];
    }

    /**
     * Custom validation to ensure SMS gateway priority is respected
     */
    public static function validateMutuallyExclusiveSettings(array $data): array
    {
        $errors = [];

        // No validation restrictions - allow both Firebase and Custom SMS to be enabled
        // The system will use Custom SMS gateway when Custom SMS is enabled (priority)

        return $errors;
    }

    /**
     * Get SMS gateway based on enabled services with priority logic
     * Custom SMS has priority over Firebase when both are enabled
     */
    public static function getSmsGateway(array $data): string
    {
        $customSmsEnabled = isset($data['customSms']) && filter_var($data['customSms'], FILTER_VALIDATE_BOOLEAN);
        $firebaseEnabled = isset($data['firebase']) && filter_var($data['firebase'], FILTER_VALIDATE_BOOLEAN);

        // Custom SMS has priority - if enabled, use custom gateway
        if ($customSmsEnabled) {
            return 'custom';
        }
        // If custom SMS is disabled but Firebase is enabled, use Firebase
        elseif ($firebaseEnabled) {
            return 'firebase';
        }

        return '';
    }

    protected static function getValidationMessages(): array
    {
        return [
            // Custom SMS General
            'customSms.boolean' => 'The custom SMS option must be true or false.',

            // Custom SMS URL
            'customSmsUrl.required_if' => 'The SMS provider URL is required when Custom SMS is enabled.',
            'customSmsUrl.url'         => 'The SMS provider URL must be a valid URL (including http:// or https://).',

            // Custom SMS Method
            'customSmsMethod.required_if' => 'The HTTP method (GET/POST) is required when Custom SMS is enabled.',
            'customSmsMethod.in'          => 'The SMS request method must be either GET or POST.',

            // Twilio-like Credentials (Sid & Token)
            'customSmsTokenAccountSid.required_if' => 'The Account SID (or equivalent) is required when Custom SMS is enabled.',
            'customSmsTokenAccountSid.string'      => 'The Account SID must be a valid string.',

            'customSmsAuthToken.required_if' => 'The Auth Token (or API key) is required when Custom SMS is enabled.',
            'customSmsAuthToken.string'      => 'The Auth Token must be a valid string.',

            // Message Format
            'customSmsTextFormatData.required_if' => 'The message text format/template is required when Custom SMS is enabled.',
            'customSmsTextFormatData.string'      => 'The message format must be a valid string.',

            // Headers
            'customSmsHeaderKey.required_if' => 'HTTP header keys are required when Custom SMS is enabled.',
            'customSmsHeaderKey.array'       => 'HTTP header keys must be an array.',

            'customSmsHeaderValue.required_if' => 'HTTP header values are required when Custom SMS is enabled.',
            'customSmsHeaderValue.array'       => 'HTTP header values must be an array.',

            // Query Parameters
            'customSmsParamsKey.required_if' => 'Query parameter keys are required when Custom SMS is enabled.',
            'customSmsParamsKey.array'       => 'Query parameter keys must be an array.',

            'customSmsParamsValue.required_if' => 'Query parameter values are required when Custom SMS is enabled.',
            'customSmsParamsValue.array'       => 'Query parameter values must be an array.',

            // Body Parameters (for POST JSON/form-data)
            'customSmsBodyKey.required_if' => 'Request body keys are required when Custom SMS is enabled.',
            'customSmsBodyKey.array'       => 'Request body keys must be an array.',

            'customSmsBodyValue.required_if' => 'Request body values are required when Custom SMS is enabled.',
            'customSmsBodyValue.array'       => 'Request body values must be an array.',

            // Firebase
            'firebase.boolean' => 'The Firebase option must be true or false.',

            'fireBaseApiKey.required_if'          => 'Firebase API Key is required when Firebase is enabled.',
            'fireBaseAuthDomain.required_if'      => 'Firebase Auth Domain is required when Firebase is enabled.',
            'fireBaseDatabaseURL.required_if'     => 'Firebase Database URL is required when Firebase is enabled.',
            'fireBaseDatabaseURL.url'             => 'Firebase Database URL must be a valid URL.',
            'fireBaseProjectId.required_if'       => 'Firebase Project ID is required when Firebase is enabled.',
            'fireBaseStorageBucket.required_if'   => 'Firebase Storage Bucket is required when Firebase is enabled.',
            'fireBaseMessagingSenderId.required_if' => 'Firebase Messaging Sender ID is required when Firebase is enabled.',
            'fireBaseAppId.required_if'           => 'Firebase App ID is required when Firebase is enabled.',
            'fireBaseMeasurementId.required_if'  => 'Firebase Measurement ID is required when Firebase is enabled.',

            // reCAPTCHA
            'googleRecaptchaSiteKey.string' => 'The Google reCAPTCHA site key must be a valid string.',

            // Social Logins
            'appleLogin.boolean'    => 'Apple Login must be enabled or disabled (true/false).',
            'googleLogin.boolean'   => 'Google Login must be enabled or disabled (true/false).',
            'facebookLogin.boolean' => 'Facebook Login must be enabled or disabled (true/false).',

            // Google API Key (for Google Login)
            'googleApiKey.string' => 'The Google API Key must be a valid string.',

            // SMS Gateway
            'smsGateway.in' => 'The SMS Gateway must be either "custom" or "firebase".',
        ];
    }
}
