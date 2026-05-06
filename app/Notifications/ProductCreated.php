<?php

namespace App\Notifications;

use App\Events\Product\ProductAfterCreate;
use App\Enums\NotificationTypeEnum;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProductCreated extends Notification implements ShouldQueue
{
    use Queueable;

    protected $event;

    /**
     * Create a new notification instance.
     */
    public function __construct(ProductAfterCreate $event)
    {
        $this->event = $event;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $product = $this->event->product;

        return (new MailMessage)
            ->subject('New Product Created - ' . $product->title)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('A new product has been created.')
            ->line('Product: ' . $product->title)
            ->line('Description: ' . $product->short_description)
            ->line('Price: ' . app(\App\Services\CurrencyService::class)->getSymbol() . number_format($product->price ?? 0, 2))
            ->line('Status: ' . $product->status)
            ->action('View Product', url('admin/products/' . $product->id))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $product = $this->event->product;

        return [
            'product_id' => $product->id,
            'title' => 'New Product Created',
            'message' => 'A new product "' . $product->title . '" has been created.',
            'type' => 'product_created',
            'metadata' => [
                'product_title' => $product->title,
                'product_price' => $product->price,
                'product_status' => $product->status,
                'seller_id' => $product->seller_id,
            ]
        ];
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        $product = $this->event->product;

        // Return payload for Laravel's default notifications table (JSON `data` column)
        return [
            'title' => 'New Product Created',
            'message' => 'A new product "' . $product->title . '" has been created.',
            'type' => NotificationTypeEnum::PRODUCT,
            'sent_to' => 'admin',
            'user_id' => $notifiable->id,
            'store_id' => $product->seller_id,
            'order_id' => null,
            'metadata' => [
                'product_id' => $product->id,
                'product_title' => $product->title,
                'product_price' => $product->price,
                'product_status' => $product->status,
                'seller_id' => $product->seller_id,
            ],
        ];
    }
}
