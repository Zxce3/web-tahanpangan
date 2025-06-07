<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\ActivityLog;
use App\Models\User;

class ActivityLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ActivityLog::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => fake()->word(),
            'model_type' => fake()->word(),
            'model_id' => fake()->numberBetween(-10000, 10000),
            'old_values' => '{}',
            'new_values' => '{}',
            'ip_address' => fake()->word(),
            'user_agent' => fake()->word(),
        ];
    }
}
