<?php

use App\Models\DeliveryBoyLocation;
use App\Services\DeliveryBoyOrderOfferService;
use App\Services\FirebaseService;

it('accepts a saved rider location even when it is older than two minutes', function () {
    $service = new DeliveryBoyOrderOfferService($this->createMock(FirebaseService::class));
    $method = new ReflectionMethod(DeliveryBoyOrderOfferService::class, 'hasSavedLocation');
    $method->setAccessible(true);

    $location = new DeliveryBoyLocation([
        'latitude' => 29.0588,
        'longitude' => 76.0856,
        'recorded_at' => now()->subHours(8),
    ]);

    expect($method->invoke($service, $location))->toBeTrue();
});

it('does not accept a rider location without saved coordinates', function () {
    $service = new DeliveryBoyOrderOfferService($this->createMock(FirebaseService::class));
    $method = new ReflectionMethod(DeliveryBoyOrderOfferService::class, 'hasSavedLocation');
    $method->setAccessible(true);

    expect($method->invoke($service, null))->toBeFalse();
    expect($method->invoke($service, new DeliveryBoyLocation([
        'latitude' => 29.0588,
        'longitude' => null,
    ])))->toBeFalse();
});
