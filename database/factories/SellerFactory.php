<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Seller;
use App\Models\User;

class SellerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Seller::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'address' => fake()->regexify('[A-Za-z0-9]{255}'),
            'city' => fake()->city(),
            'landmark' => fake()->regexify('[A-Za-z0-9]{100}'),
            'state' => fake()->regexify('[A-Za-z0-9]{100}'),
            'zipcode' => fake()->regexify('[A-Za-z0-9]{20}'),
            'country' => fake()->country(),
            'country_code' => fake()->regexify('[A-Za-z0-9]{10}'),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'business_license' => fake()->text(),
            'articles_of_incorporation' => fake()->text(),
            'national_identity_card' => fake()->text(),
            'authorized_signature' => fake()->text(),
            'verification_status' => fake()->randomElement(["approved","not_approved"]),
            'metadata' => '{}',
            'visibility_status' => fake()->randomElement(["visible","draft"]),
        ];
    }
}
