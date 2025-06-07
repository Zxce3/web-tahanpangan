<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Commodity;
use App\Models\District;
use App\Models\PriceAlert;
use App\Models\User;

class PriceAlertFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PriceAlert::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'commodity_id' => Commodity::factory(),
            'district_id' => District::factory(),
            'threshold_price' => fake()->randomFloat(0, 0, 9999999999.),
            'alert_type' => fake()->randomElement(["above","below"]),
            'is_active' => fake()->boolean(),
            'created_by' => User::factory()->create()->created_by,
            'created_by_id' => User::factory(),
        ];
    }
}
