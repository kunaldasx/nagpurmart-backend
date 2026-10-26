<?php

use App\Models\SellerOrderPopupPresence;
use App\Services\FirebaseService;
use App\Services\SellerOrderPopupOfferService;

it('allows an active system user to dispatch using the saved location after the app closes', function () {
    $service = new SellerOrderPopupOfferService($this->createMock(FirebaseService::class));
    $method = new ReflectionMethod(SellerOrderPopupOfferService::class, 'hasActivePresenceLocation');
    $method->setAccessible(true);

    $presence = new SellerOrderPopupPresence([
        'active' => true,
        'latitude' => 29.0588,
        'longitude' => 76.0856,
        'last_seen_at' => now()->subHours(8),
    ]);

    expect($method->invoke($service, $presence))->toBeTrue();
});

it('does not consider an inactive or location-less system user dispatch eligible', function () {
    $service = new SellerOrderPopupOfferService($this->createMock(FirebaseService::class));
    $method = new ReflectionMethod(SellerOrderPopupOfferService::class, 'hasActivePresenceLocation');
    $method->setAccessible(true);

    expect($method->invoke($service, new SellerOrderPopupPresence([
        'active' => false,
        'latitude' => 29.0588,
        'longitude' => 76.0856,
    ])))->toBeFalse();

    expect($method->invoke($service, new SellerOrderPopupPresence([
        'active' => true,
        'latitude' => null,
        'longitude' => null,
    ])))->toBeFalse();
});
