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
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.delivery_boy.status', 'active');
        $this->assertSame($approvedUntil, $deliveryBoy->fresh()->login_approved_until->timestamp);
        $this->assertSame('active', $deliveryBoy->fresh()->status);

        $this->withToken($response->json('data.access_token'))
            ->postJson('/api/delivery-boy/logout')
            ->assertOk();
        $this->assertSame('inactive', $deliveryBoy->fresh()->status);
        $this->assertDatabaseCount('personal_access_tokens', 0);
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
        $loginResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.delivery_boy.status', 'active');
        $accessToken = $loginResponse->json('data.access_token');
        $this->assertSame('active', $deliveryBoy->fresh()->status);

        $this->travel(13)->hours();
        $this->withToken($accessToken)
            ->getJson('/api/delivery-boy/profile')
            ->assertUnauthorized();

        $this->assertSame(1, $service->expireLoginSessions());
        $this->assertSame('inactive', $deliveryBoy->fresh()->status);

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

    public function test_seller_can_list_and_claim_an_existing_unassigned_rider(): void
    {
        [$seller, $rider, $owner] = $this->createSellerAndRider();
        $deliveryBoy = DeliveryBoy::where('user_id', $rider->id)->firstOrFail();
        $deliveryBoy->update([
            'seller_id' => null,
            'login_approval_status' => 'approved',
            'login_approved_at' => now(),
            'login_approved_until' => now()->addHours(12),
        ]);
        $rider->createToken('rider-password');

        $this->actingAs($owner, 'sanctum')
            ->getJson('/api/seller/delivery-boys')
            ->assertOk()
            ->assertJsonPath('data.available_riders.total', 1)
            ->assertJsonPath('data.available_riders.data.0.id', $deliveryBoy->id);

        $this->postJson('/api/seller/delivery-boys/' . $deliveryBoy->id . '/claim')
            ->assertOk()
            ->assertJsonPath('data.rider.id', $deliveryBoy->id);

        $this->assertDatabaseHas('delivery_boys', [
            'id' => $deliveryBoy->id,
            'seller_id' => $seller->id,
            'status' => 'inactive',
            'login_approval_status' => 'disapproved',
            'login_approved_until' => null,
            'qr_login_token_hash' => null,
        ]);
        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->getJson('/api/seller/delivery-boys')
            ->assertOk()
            ->assertJsonPath('data.assigned_riders.total', 1)
            ->assertJsonPath('data.available_riders.total', 0);
    }

    public function test_seller_can_set_a_custom_qr_duration_within_the_platform_limit(): void
    {
        [, $rider, $owner] = $this->createSellerAndRider();
        $this->setLoginDuration(12);
        $deliveryBoy = DeliveryBoy::where('user_id', $rider->id)->firstOrFail();

        $response = $this->actingAs($owner, 'sanctum')
            ->postJson('/api/seller/delivery-boys/' . $deliveryBoy->id . '/rotate-login-qr', [
                'duration_hours' => 6,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true);
        $this->assertEqualsWithDelta(
            now()->addHours(6)->timestamp,
            \Illuminate\Support\Carbon::parse($response->json('data.expires_at'))->timestamp,
            2
        );

        $this->postJson('/api/seller/delivery-boys/' . $deliveryBoy->id . '/rotate-login-qr', [
            'duration_hours' => 13,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('duration_hours');
    }

    public function test_seller_can_choose_password_approval_duration_and_defaults_to_twelve_hours(): void
    {
        [, $rider, $owner] = $this->createSellerAndRider();
        $this->setLoginDuration(24);
        $deliveryBoy = DeliveryBoy::where('user_id', $rider->id)->firstOrFail();
        $this->actingAs($owner, 'sanctum');

        $customResponse = $this->postJson('/api/seller/delivery-boys/' . $deliveryBoy->id . '/approve-login', [
            'duration_hours' => 6,
        ]);
        $customResponse->assertOk()
            ->assertJsonPath('data.duration_hours', 6);
        $this->assertEqualsWithDelta(
            now()->addHours(6)->timestamp,
            \Illuminate\Support\Carbon::parse($customResponse->json('data.login_approved_until'))->timestamp,
            2
        );

        $defaultResponse = $this->postJson('/api/seller/delivery-boys/' . $deliveryBoy->id . '/approve-login');
        $defaultResponse->assertOk()
            ->assertJsonPath('data.duration_hours', 12)
            ->assertJsonPath('data.status', 'inactive');
        $this->assertEqualsWithDelta(
            now()->addHours(12)->timestamp,
            \Illuminate\Support\Carbon::parse($defaultResponse->json('data.login_approved_until'))->timestamp,
            2
        );

        $this->setLoginDuration(8);
        $cappedDefaultResponse = $this->postJson('/api/seller/delivery-boys/' . $deliveryBoy->id . '/approve-login');
        $cappedDefaultResponse->assertOk()
            ->assertJsonPath('data.duration_hours', 8);

        $this->postJson('/api/seller/delivery-boys/' . $deliveryBoy->id . '/approve-login', [
            'duration_hours' => 25,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('duration_hours');
    }

    public function test_seller_disapproval_marks_rider_inactive_and_revokes_active_login(): void
    {
        [, $rider, $owner] = $this->createSellerAndRider();
        $deliveryBoy = DeliveryBoy::where('user_id', $rider->id)->firstOrFail();
        app(DeliveryBoyLoginService::class)->approvePasswordLogin($deliveryBoy, 6);

        $login = $this->postJson('/api/delivery-boy/login', [
            'email' => $rider->email,
            'password' => 'password123',
        ]);
        $login->assertOk();
        $this->assertSame('active', $deliveryBoy->fresh()->status);

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/seller/delivery-boys/' . $deliveryBoy->id . '/reject-login')
            ->assertOk()
            ->assertJsonPath('data.status', 'inactive');

        $this->assertSame('inactive', $deliveryBoy->fresh()->status);
        $this->assertDatabaseCount('personal_access_tokens', 0);
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
            'status' => 'inactive',
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
