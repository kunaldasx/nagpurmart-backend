<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\FollowingSeller;
use App\Models\UserSeller;

class FollowingSellerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = FollowingSeller::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => fake()->word(),
            'seller_id' => fake()->word(),
            'user_seller_id' => UserSeller::factory(),
        ];
    }
}
