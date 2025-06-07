<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Alert;
use App\Models\Commodity;
use App\Models\District;

class AlertFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Alert::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(["low_production","price_spike","security_change"]),
            'title' => fake()->sentence(4),
            'message' => fake()->text(),
            'district_id' => District::factory(),
            'commodity_id' => Commodity::factory(),
            'severity' => fake()->randomElement(["info","warning","critical"]),
            'is_resolved' => fake()->boolean(),
            'resolved_at' => fake()->dateTime(),
        ];
    }
}
