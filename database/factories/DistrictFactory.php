<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\District;

class DistrictFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = District::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'code' => fake()->word(),
            'polygon_coordinates' => '{}',
            'security_level' => fake()->randomElement(["low","medium","high","critical"]),
            'population' => fake()->numberBetween(-10000, 10000),
            'area_hectares' => fake()->randomFloat(0, 0, 9999999999.),
            'administrative_level' => fake()->randomElement(["province","regency","district","village"]),
            'parent_district_id' => District::factory(),
            'is_active' => fake()->boolean(),
            'parent_district_id_id' => District::factory(),
        ];
    }
}
