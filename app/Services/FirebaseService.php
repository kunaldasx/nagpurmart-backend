<?php

namespace App\Services;

use App\Models\UserFcmToken;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FirebaseService
{
    protected ?Messaging $messaging = null;

    public function __construct()
    {
        $path = storage_path('app/private/settings/service-account-file.json');

        if (!file_exists($path)) {
            Log::warning('Firebase service account file not found', [
                'path' => $path,
            ]);
            return;
        }

        try {
            $json = json_decode(file_get_contents($path), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid Firebase service account JSON');
            }

            // Required keys validation
            foreach (['type', 'project_id', 'client_email', 'private_key'] as $key) {
                if (empty($json[$key])) {
                    throw new \Exception("Firebase service account missing key: {$key}");
                }
            }

            $firebase = (new Factory)->withServiceAccount($json);
            $this->messaging = $firebase->createMessaging();

        } catch (\Throwable $e) {
            Log::error('Firebase initialization failed', [
                'error' => $e->getMessage(),
            ]);

            $this->messaging = null; // explicitly disable
        }
    }

    /**
     * Send single notification
     */
    public function sendNotification($token, $title, $body, $image = "", $data = []): array
    {
        if ($this->messaging === null) {
            return [
                'success' => false,
                'error' => 'Firebase is not configured',
            ];
        }

        try {
            $notification = Notification::create(
                title: $title,
                body: $body,
                imageUrl: $image
            );

            $message = CloudMessage::new()
                ->withNotification($notification)
                ->withData($data)
                ->withDefaultSounds()
                ->toToken($token);

            $this->messaging->send($message);

            return ['success' => true];

        } catch (\Throwable $e) {
            Log::error('Firebase sendNotification failed', [
                'error' => $e->getMessage(),
                'token' => $token,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send bulk notifications
     */
    public function sendBulkNotification(
        array $tokens,
        string $title,
        string $body,
        string $image = null,
        array $data = [],
        int $chunkSize = 50
    ): array {
        if ($this->messaging === null) {
            Log::warning('Firebase bulk send skipped: not configured');

            return [
                'success' => 0,
                'failure' => count($tokens),
                'error' => 'Firebase is not configured',
            ];
        }

        $results = [
            'success' => 0,
            'failure' => 0,
            'removed_tokens' => [],
        ];

        $notification = Notification::create(
            title: $title,
            body: $body,
            imageUrl: $image
        );

        Collection::make($tokens)->chunk($chunkSize)->each(function ($chunk) use (&$results, $notification, $data) {
            try {
                $message = CloudMessage::new()
                    ->withNotification($notification)
                    ->withData($data)
                    ->withDefaultSounds();

                $multicastResult = $this->messaging->sendMulticast(
                    $message,
                    $chunk->toArray()
                );

                $results['success'] += $multicastResult->successes()->count();
                $results['failure'] += $multicastResult->failures()->count();

                $invalidTokens = $multicastResult->invalidTokens();

                if (!empty($invalidTokens)) {
                    UserFcmToken::whereIn('fcm_token', $invalidTokens)->delete();
                    $results['removed_tokens'] = array_merge(
                        $results['removed_tokens'],
                        $invalidTokens
                    );
                }

            } catch (\Throwable $e) {
                Log::error('Firebase bulk send chunk failed', [
                    'error' => $e->getMessage(),
                ]);

                $results['failure'] += $chunk->count();
            }
        });

        return $results;
    }
}
