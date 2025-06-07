<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Alert;
use App\Models\AlertSubscription;
use App\Models\User;

class AlertSubscriptionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AlertSubscription::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'alert_id' => Alert::factory(),
            'notification_method' => fake()->randomElement(["email","sms","in_app"]),
            'is_read' => fake()->boolean(),
            'sent_at' => fake()->dateTime(),
        ];
    }
}
