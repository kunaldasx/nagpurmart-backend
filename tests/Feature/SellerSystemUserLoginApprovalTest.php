<?php

namespace Tests\Feature;

use App\Enums\GuardNameEnum;
use App\Models\Seller;
use App\Models\SellerUser;
use App\Models\User;
use App\Services\SellerUserLoginApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerSystemUserLoginApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_unapproved_system_user_login_is_blocked_and_creates_a_pending_request(): void
    {
        [$seller, $systemUser] = $this->createSellerSystemUser('not_requested');

        $response = app(SellerUserLoginApprovalService::class)->requestApprovalIfRequired($systemUser);

        $this->assertSame(403, $response?->getStatusCode());
        $this->assertDatabaseHas('seller_user', [
            'user_id' => $systemUser->id,
            'seller_id' => $seller->id,
            'login_approval_status' => 'pending',
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $seller->user_id,
        ]);
    }

    public function test_approved_system_user_can_continue_login(): void
    {
        [, $systemUser] = $this->createSellerSystemUser('approved');

        $response = app(SellerUserLoginApprovalService::class)->requestApprovalIfRequired($systemUser);

        $this->assertNull($response);
    }

    private function createSellerSystemUser(string $approvalStatus): array
    {
        $sellerOwner = User::create([
            'name' => 'Seller Owner',
            'email' => uniqid('seller') . '@example.com',
            'password' => bcrypt('password123'),
            'status' => true,
            'access_panel' => GuardNameEnum::SELLER(),
        ]);

        $seller = Seller::create([
            'user_id' => $sellerOwner->id,
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

        $systemUser = User::create([
            'name' => 'System User',
            'email' => uniqid('system') . '@example.com',
            'password' => bcrypt('password123'),
            'status' => true,
            'access_panel' => GuardNameEnum::SELLER(),
        ]);

        SellerUser::create([
            'user_id' => $systemUser->id,
            'seller_id' => $seller->id,
            'login_approval_status' => $approvalStatus,
        ]);

        return [$seller, $systemUser];
    }
}