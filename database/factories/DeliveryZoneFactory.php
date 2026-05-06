<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\DeliveryZone;

class DeliveryZoneFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = DeliveryZone::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'slug' => fake()->slug(),
            'center_latitude' => fake()->randomFloat(8, 0, 99.99999999),
            'center_longitude' => fake()->randomFloat(8, 0, 999.99999999),
            'radius_km' => fake()->randomFloat(0, 0, 9999999999.),
            'boundary_json' => '{}',
            'status' => fake()->randomElement(["active","inactive"]),
        ];
    }
}
