<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\DeliveryBoy;
use App\Models\DeliveryBoyLocation;

class DeliveryBoyLocationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = DeliveryBoyLocation::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'delivery_boy_id' => DeliveryBoy::factory(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'recorded_at' => fake()->dateTime(),
        ];
    }
}
