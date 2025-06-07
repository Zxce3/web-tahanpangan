<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\District;
use App\Models\SecurityLevelHistory;
use App\Models\User;

class SecurityLevelHistoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SecurityLevelHistory::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'district_id' => District::factory(),
            'previous_level' => fake()->randomElement(["low","medium","high","critical"]),
            'new_level' => fake()->randomElement(["low","medium","high","critical"]),
            'change_reason' => fake()->text(),
            'changed_by' => User::factory()->create()->changed_by,
            'changed_at' => fake()->dateTime(),
            'changed_by_id' => User::factory(),
        ];
    }
}
