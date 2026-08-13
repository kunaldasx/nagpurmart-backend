<?php

use App\Services\DeliveryZoneService;

test('delivery estimate matches the fixed prep plus distance formula', function () {
    expect(DeliveryZoneService::calculateExpectedDeliveryMinutes(0))->toBe(5)
        ->and(DeliveryZoneService::calculateExpectedDeliveryMinutes(0.5))->toBe(8)
        ->and(DeliveryZoneService::calculateExpectedDeliveryMinutes(1.2))->toBe(11);
});
