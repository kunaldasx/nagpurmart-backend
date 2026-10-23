<?php

namespace Tests\Feature;

use App\Enums\DeliveryBoy\DeliveryBoyVerificationStatusEnum;
use App\Enums\GuardNameEnum;
use App\Models\DeliveryBoy;
use App\Models\Seller;
use App\Models\Setting;
use App\Models\User;
use App\Services\DeliveryBoyLoginService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DeliveryBoyLoginFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_linked_rider_password_login_waits_for_approval_then_expires_with_grant(): void
    {
        [$seller, $rider, $owner] = $this->createSellerAndRider();
        $this->setLoginDuration(12);

        $pendingResponse = $this->postJson('/api/delivery-boy/login', [
            'email' => $rider->email,
            'password' => 'password123',
        ]);

        $pendingResponse->assertForbidden()
            ->assertJsonPath('data.login_approval_status', 'pending');
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $owner->id,
        ]);

        $deliveryBoy = DeliveryBoy::where('user_id', $rider->id)->firstOrFail();
        app(DeliveryBoyLoginService::class)->approvePasswordLogin($deliveryBoy);
        $approvedUntil = $deliveryBoy->fresh()->login_approved_until->timestamp;
        $response = $this->postJson('/api/delivery-boy/login', [
            'email' => $rider->email,
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'Bearer');
        $this->assertSame($approvedUntil, $deliveryBoy->fresh()->login_approved_until->timestamp);
    }

    public function test_qr_login_uses_hashed_expiring_credential_and_rotates_after_expiry(): void
    {
        [, $rider] = $this->createSellerAndRider();
        $this->setLoginDuration(12);
        $deliveryBoy = DeliveryBoy::where('user_id', $rider->id)->firstOrFail();
        $service = app(DeliveryBoyLoginService::class);
        $firstQr = $service->issueOrGetQr($deliveryBoy);

        $this->assertSame(hash('sha256', $firstQr['token']), $deliveryBoy->fresh()->qr_login_token_hash);
        $this->assertSame($firstQr['token'], $service->issueOrGetQr($deliveryBoy)['token']);
        $this->assertSame($deliveryBoy->id, $service->findValidQrRider($firstQr['token'])?->id);

        $loginResponse = $this->postJson('/api/delivery-boy/login/qr', ['qr_token' => $firstQr['token']]);
        $loginResponse->assertOk()->assertJsonPath('success', true);
        $accessToken = $loginResponse->json('data.access_token');

        $this->travel(13)->hours();
        $this->withToken($accessToken)
            ->getJson('/api/delivery-boy/profile')
            ->assertUnauthorized();

        $secondQr = $service->issueOrGetQr($deliveryBoy);
        $this->assertNotSame($firstQr['token'], $secondQr['token']);
        $this->assertNull($service->findValidQrRider($firstQr['token']));
    }

    public function test_platform_managed_rider_password_login_keeps_existing_flow(): void
    {
        [, $rider] = $this->createSellerAndRider();
        DeliveryBoy::where('user_id', $rider->id)->update(['seller_id' => null]);

        $this->postJson('/api/delivery-boy/login', [
            'email' => $rider->email,
            'password' => 'password123',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.expires_at', null);
    }

    private function createSellerAndRider(): array
    {
        $owner = User::create([
            'name' => 'Seller Owner',
            'email' => uniqid('owner') . '@example.com',
            'password' => Hash::make('password123'),
            'status' => true,
            'access_panel' => GuardNameEnum::SELLER(),
        ]);

        $seller = Seller::create([
            'user_id' => $owner->id,
            'address' => 'Main Street',
            'city' => 'Nagpur',
            'landmark' => 'Market',
            'state' => 'Maharashtra',
            'zipcode' => '440001',
            'country' => 'India',
            'country_code' => 'IN',
            'business_license' => 'license',
            'articles_of_incorporation' => 'articles',
            'national_identity_card' => 'identity',
            'authorized_signature' => 'signature',
            'verification_status' => 'approved',
            'metadata' => [],
            'visibility_status' => 'visible',
        ]);

        $rider = User::create([
            'name' => 'Rider User',
            'email' => uniqid('rider') . '@example.com',
            'password' => Hash::make('password123'),
            'status' => true,
        ]);

        DeliveryBoy::create([
            'user_id' => $rider->id,
            'seller_id' => $seller->id,
            'status' => 'active',
            'full_name' => $rider->name,
            'verification_status' => DeliveryBoyVerificationStatusEnum::VERIFIED(),
        ]);

        return [$seller, $rider, $owner];
    }

    private function setLoginDuration(int $hours): void
    {
        Setting::updateOrCreate(
            ['variable' => 'delivery_boy'],
            ['value' => json_encode(['riderLoginDurationHours' => $hours])],
        );
    }
}
