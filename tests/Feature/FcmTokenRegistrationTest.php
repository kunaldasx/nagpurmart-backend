<?php

namespace Tests\Feature;

use App\Enums\GuardNameEnum;
use App\Models\User;
use App\Models\UserFcmToken;
use App\Traits\AuthTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class FcmTokenRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registering_a_token_already_owned_by_another_user_transfers_it_without_duplicate_error(): void
    {
        $previousUser = $this->createUser('previous');
        $currentUser = $this->createUser('current');
        $token = 'shared-device-token';

        UserFcmToken::query()->create([
            'user_id' => $previousUser->id,
            'fcm_token' => $token,
            'device_type' => 'android',
        ]);

        $registrar = new class {
            use AuthTrait {
                storeFcmToken as public;
            }
        };

        $registrar->storeFcmToken(
            Request::create('/api/user/fcm-token', 'POST', [
                'fcm_token' => $token,
                'device_type' => 'android',
            ]),
            $currentUser
        );

        $this->assertDatabaseCount('user_fcm_tokens', 1);
        $this->assertDatabaseHas('user_fcm_tokens', [
            'user_id' => $currentUser->id,
            'fcm_token' => $token,
            'device_type' => 'android',
        ]);
    }

    public function test_registering_a_new_device_token_keeps_the_users_existing_token(): void
    {
        $user = $this->createUser('multi-device');

        UserFcmToken::query()->create([
            'user_id' => $user->id,
            'fcm_token' => 'existing-device-token',
            'device_type' => 'android',
        ]);

        $registrar = new class {
            use AuthTrait {
                storeFcmToken as public;
            }
        };

        $registrar->storeFcmToken(
            Request::create('/api/user/fcm-token', 'POST', [
                'fcm_token' => 'new-device-token',
                'device_type' => 'ios',
            ]),
            $user
        );

        $this->assertDatabaseCount('user_fcm_tokens', 2);
        $this->assertDatabaseHas('user_fcm_tokens', [
            'user_id' => $user->id,
            'fcm_token' => 'existing-device-token',
        ]);
        $this->assertDatabaseHas('user_fcm_tokens', [
            'user_id' => $user->id,
            'fcm_token' => 'new-device-token',
            'device_type' => 'ios',
        ]);
    }

    private function createUser(string $suffix): User
    {
        return User::query()->create([
            'name' => 'FCM ' . $suffix,
            'email' => $suffix . '-' . uniqid() . '@example.com',
            'password' => bcrypt('password123'),
            'status' => true,
            'access_panel' => GuardNameEnum::SELLER(),
        ]);
    }
}
