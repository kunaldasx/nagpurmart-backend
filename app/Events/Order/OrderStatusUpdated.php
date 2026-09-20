<?php

namespace App\Events\Order;

use App\Models\OrderItem;
use App\Models\SellerOrderItem;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ?OrderItem $orderItem;
    public string $oldStatus;
    public string $newStatus;
    public ?string $oldOrderStatus;
    public ?string $orderStatus;

    /**
     * Create a new event instance.
     */
    public function __construct(?OrderItem $orderItem, string $oldStatus, string $newStatus, ?string $oldOrderStatus = null)
    {
        $this->orderItem = $orderItem;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
        $this->oldOrderStatus = $oldOrderStatus;
        $this->orderStatus = $orderItem?->order?->status;
    }
}
