<?php

use App\Models\DeliveryZone;
use App\Services\DeliveryZoneService;
use Carbon\Carbon;

test('a weekday with no custom hours is active all day', function () {
    $zone = new DeliveryZone();
    $zone->active_hours = ['monday' => ['start' => '09:00', 'end' => '22:00']];

    expect(DeliveryZoneService::isWithinActiveHours($zone, Carbon::parse('2026-09-28 23:30')))->toBeTrue();
});

test('custom weekday hours include the opening time and exclude the closing time', function () {
    $zone = new DeliveryZone();
    $zone->active_hours = ['monday' => ['start' => '09:00', 'end' => '22:00']];

    expect(DeliveryZoneService::isWithinActiveHours($zone, Carbon::parse('2026-09-28 09:00')))->toBeTrue()
        ->and(DeliveryZoneService::isWithinActiveHours($zone, Carbon::parse('2026-09-28 21:59')))->toBeTrue()
        ->and(DeliveryZoneService::isWithinActiveHours($zone, Carbon::parse('2026-09-28 22:00')))->toBeFalse();
});

test('zones with no active hours plan remain active all day', function () {
    $zone = new DeliveryZone();

    expect(DeliveryZoneService::isWithinActiveHours($zone, Carbon::parse('2026-09-28 02:00')))->toBeTrue();
});

test('an explicit delivery pause overrides active hours', function () {
    $zone = new DeliveryZone();
    $zone->active_hours = [];
    $zone->delivery_paused = true;

    expect(DeliveryZoneService::isDeliveryAvailableNow($zone))->toBeFalse();
});

test('after-hours delivery starts at the next configured opening', function () {
    $zone = new DeliveryZone();
    $zone->active_hours = [
        'monday' => ['start' => '09:00', 'end' => '18:00'],
        'tuesday' => ['start' => '09:00', 'end' => '21:00'],
    ];
    $now = Carbon::parse('2026-09-28 22:00');

    expect(DeliveryZoneService::getNextDeliveryStartAt($zone, $now)->toDateTimeString())
        ->toBe('2026-09-29 09:00:00')
        ->and(DeliveryZoneService::getDeliveryWaitMinutes($zone, $now))->toBe(660);
});

test('delivery wait begins after a scheduled pause ends', function () {
    $zone = new DeliveryZone();
    $zone->active_hours = [
        'monday' => ['start' => '09:00', 'end' => '18:00'],
        'tuesday' => ['start' => '09:00', 'end' => '21:00'],
    ];
    $zone->delivery_paused = true;
    $zone->delivery_paused_until = Carbon::parse('2026-09-29 10:00');
    $now = Carbon::parse('2026-09-28 22:00');

    expect(DeliveryZoneService::getNextDeliveryStartAt($zone, $now)->toDateTimeString())
        ->toBe('2026-09-29 10:00:00')
        ->and(DeliveryZoneService::getDeliveryWaitMinutes($zone, $now))->toBe(720);
});