<?php

use App\Models\DeliveryTimeSlot;
use Carbon\Carbon;

afterEach(function () {
    Carbon::setTestNow();
});

it('allows booking today until the slot start time', function () {
    Carbon::setTestNow(Carbon::parse('2026-09-30 18:59:00'));
    $slot = new DeliveryTimeSlot(['start_time' => '19:00', 'end_time' => '22:00']);

    expect($slot->isBookableAt(Carbon::today()))->toBeTrue();
});

it('closes a same-day slot when its start time is reached', function () {
    Carbon::setTestNow(Carbon::parse('2026-09-30 19:00:00'));
    $slot = new DeliveryTimeSlot(['start_time' => '19:00', 'end_time' => '22:00']);

    expect($slot->isBookableAt(Carbon::today()))->toBeFalse();
});

it('keeps a future-date slot bookable after that time today', function () {
    Carbon::setTestNow(Carbon::parse('2026-09-30 19:00:00'));
    $slot = new DeliveryTimeSlot(['start_time' => '19:00', 'end_time' => '22:00']);

    expect($slot->isBookableAt(Carbon::tomorrow()))->toBeTrue();
});