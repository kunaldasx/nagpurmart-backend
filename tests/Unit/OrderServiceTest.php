<?php

use App\Models\User;
use App\Services\DeliveryBoyService;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Services\SellerStatementService;
use App\Services\SettingService;
use App\Services\SmsService;
use App\Services\StockService;
use App\Services\WalletService;

it('falls back to an empty string when a user has no email', function () {
    $service = new OrderService(
        $this->createMock(StockService::class),
        $this->createMock(DeliveryBoyService::class),
        $this->createMock(SettingService::class),
        $this->createMock(PaymentService::class),
        $this->createMock(SellerStatementService::class),
        $this->createMock(WalletService::class),
        $this->createMock(SmsService::class),
    );

    $user = new User();
    $user->email = null;

    $method = new ReflectionMethod(OrderService::class, 'resolveOrderEmail');
    $method->setAccessible(true);

    expect($method->invoke($service, $user))->toBe('');
});
