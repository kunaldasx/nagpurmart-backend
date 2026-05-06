<?php

namespace App\Notifications\Store;

use App\Broadcasting\FirebaseChannel;
use App\Enums\NotificationTypeEnum;
use App\Enums\DefaultSystemRolesEnum;
use App\Models\Store;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewStoreCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Store $store)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail', FirebaseChannel::class];
    }

    public function toMail(object $notifiable): ?MailMessage
    {
        $store = $this->store;
        $subject = 'New Store Created';
        $greeting = 'Hello ' . ($notifiable->name ?? '');
        $line = 'A new store has been created: ' . ($store->name ?? '-') . '.';

        return (new MailMessage)
            ->subject($subject)
            ->greeting($greeting)
            ->line($line)
            ->line('City: ' . ($store->city ?? '-'))
            ->line('Contact: ' . ($store->contact_number ?? '-'));
    }

    public function toFirebase($notifiable): array
    {
        $store = $this->store;
        return [
            'title' => 'New Store Created',
            'body'  => 'Store ' . ($store->name ?? '-') . ' has been created.',
            'image' => $store->getFirstMediaUrl() ?? null,
            'data'  => [
                'store_id' => $store->id ?? null,
                'store_slug' => $store->slug ?? null,
                'type' => NotificationTypeEnum::SYSTEM(),
            ],
        ];
    }

    public function toDatabase(object $notifiable): array
    {
        $store = $this->store;
        $isSeller = method_exists($notifiable, 'hasRole') && $notifiable->hasRole(DefaultSystemRolesEnum::SELLER());
        $isAdmin = method_exists($notifiable, 'hasRole') && $notifiable->hasRole(DefaultSystemRolesEnum::SUPER_ADMIN());
        return [
            'title' => 'New Store Created',
            'message' => 'Store ' . ($store->name ?? '-') . ' has been created.',
            'type' => NotificationTypeEnum::SYSTEM(),
            'sent_to' => $isSeller ? 'seller' : ($isAdmin ? 'admin' : 'user'),
            'user_id' => $notifiable->id ?? null,
            'store_id' => $store->id ?? null,
            'metadata' => [
                'store_id' => $store->id ?? null,
                'store_slug' => $store->slug ?? null,
                'seller_id' => $store->seller_id ?? null,
            ],
        ];
    }
}
