<?php

namespace App\Broadcasting;

use App\Services\FirebaseService;
use Illuminate\Notifications\Notification;

class FirebaseChannel
{
    protected $firebase;

    public function __construct(FirebaseService $firebase)
    {
        $this->firebase = $firebase;
    }

    /**
     * Send the given notification.
     */
    public function send($notifiable, Notification $notification)
    {
        if (!method_exists($notifiable, 'routeNotificationForFirebase')) {
            return;
        }

        $tokens = $notifiable->routeNotificationForFirebase();

        if (empty($tokens)) {
            return;
        }

        $message = $notification->toFirebase($notifiable);

        // Supports both single and multiple tokens
        if (is_array($tokens)) {
            return $this->firebase->sendBulkNotification(
                tokens: $tokens,
                title: $message['title'],
                body: $message['body'],
                image: $message['image'],
                data: $message['data'] ?? []
            );
        } else {
            return $this->firebase->sendNotification(
                token: $tokens,
                title: $message['title'],
                body: $message['body'],
                data:$message['data'] ?? []
            );
        }
    }
}
