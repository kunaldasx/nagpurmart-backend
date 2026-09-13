<?php

use App\Models\Promo;
use App\Models\User;

it('returns promo banner fields for the user available promos endpoint', function () {
    $user = User::factory()->create();

    Promo::create([
        'code' => 'SAVE20',
        'description' => 'Flat 20% off on your first order',
        'heading' => 'Weekend Saver',
        'sub_heading' => 'Get 20% off above ₹499',
        'banner_image' => 'https://example.com/banner.jpg',
        'start_date' => now()->subDay(),
        'end_date' => now()->addDays(7),
        'discount_type' => 'percent',
        'discount_amount' => 20,
        'promo_mode' => 'instant',
        'usage_count' => 0,
        'max_total_usage' => 100,
        'max_usage_per_user' => 1,
        'min_order_total' => 499,
        'max_discount_value' => 250,
    ]);

    $response = $this->actingAs($user, 'sanctum')->getJson('/api/user/promos/available');

    $response->assertOk()
        ->assertJsonPath('data.0.code', 'SAVE20')
        ->assertJsonPath('data.0.heading', 'Weekend Saver')
        ->assertJsonPath('data.0.sub_heading', 'Get 20% off above ₹499')
        ->assertJsonPath('data.0.image', 'https://example.com/banner.jpg');
});
