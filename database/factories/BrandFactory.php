<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Brand;

class BrandFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Brand::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'uuid' => fake()->uuid(),
            'slug' => fake()->slug(),
            'title' => fake()->sentence(4),
            'description' => fake()->text(),
            'image' => fake()->regexify('[A-Za-z0-9]{255}'),
            'banner' => fake()->regexify('[A-Za-z0-9]{255}'),
            'status' => fake()->randomElement(["0","1"]),
            'metadata' => '{}',
        ];
    }
}
