<?php

use App\Services\DeliveryZoneService;

test('delivery estimate uses prep time configured speed and buffer', function () {
    expect(DeliveryZoneService::calculateEstimatedDeliveryMinutes(0, 5))->toBe(5)
        ->and(DeliveryZoneService::calculateEstimatedDeliveryMinutes(0.5, 4, 3))->toBe(10)
        ->and(DeliveryZoneService::calculateEstimatedDeliveryMinutes(1.2, 4, 3))->toBe(13)
        ->and(DeliveryZoneService::calculateEstimatedDeliveryMinutes(2, 4, 3, 8))->toBe(19);
});
