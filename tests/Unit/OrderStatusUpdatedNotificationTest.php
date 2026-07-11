<?php

use App\Enums\Order\OrderStatusEnum;
use App\Events\Order\OrderStatusUpdated;
use App\Listeners\Order\OrderStatusUpdatedNotification;

it('handles order-level status updates without an order item', function () {
    $listener = new OrderStatusUpdatedNotification();

    $event = new OrderStatusUpdated(
        orderItem: null,
        oldStatus: OrderStatusEnum::PENDING(),
        newStatus: OrderStatusEnum::CANCELLED()
    );

    expect(fn () => $listener->handle($event))->not->toThrow(Exception::class);
});
