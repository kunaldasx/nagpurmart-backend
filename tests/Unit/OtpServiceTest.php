<?php

use App\Models\UserOtp;
use App\Services\OtpService;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('uses a default OTP for the configured test mobile without sending SMS', function () {
    config()->set('services.default_otp_mobile', '+911000000000');
    config()->set('services.default_otp_code', '123456');

    $smsService = Mockery::mock(SmsService::class);
    $smsService->shouldNotReceive('sendSms');

    $service = new OtpService($smsService);
    $result = $service->sendOtp('+911000000000');

    expect($result['success'])->toBeTrue()
        ->and($result['message'])->toBe('OTP sent successfully')
        ->and(UserOtp::where('mobile', '1000000000')->value('otp'))->toBe('123456');
});

it('verifies the default OTP without requiring a stored record', function () {
    config()->set('services.default_otp_mobile', '+911000000000');
    config()->set('services.default_otp_code', '123456');

    $smsService = Mockery::mock(SmsService::class);
    $service = new OtpService($smsService);

    $result = $service->verifyOtp('+911000000000', '123456');

    expect($result['success'])->toBeTrue()
        ->and($result['message'])->toBe('OTP verified successfully');
});
