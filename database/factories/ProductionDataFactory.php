<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Commodity;
use App\Models\District;
use App\Models\ProductionData;
use App\Models\User;

class ProductionDataFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ProductionData::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'district_id' => District::factory(),
            'commodity_id' => Commodity::factory(),
            'production_volume' => fake()->randomFloat(0, 0, 9999999999.),
            'harvest_area' => fake()->randomFloat(0, 0, 9999999999.),
            'yield_per_hectare' => fake()->randomFloat(0, 0, 9999999999.),
            'month' => fake()->numberBetween(-10000, 10000),
            'year' => fake()->numberBetween(-10000, 10000),
            'data_source' => fake()->randomElement(["survey","estimation","report"]),
            'verified_at' => fake()->dateTime(),
            'verified_by' => User::factory()->create()->verified_by,
            'verified_by_id' => User::factory(),
        ];
    }
}
