<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Commodity;
use App\Models\CommodityPrice;
use App\Models\District;

class CommodityPriceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CommodityPrice::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'district_id' => District::factory(),
            'commodity_id' => Commodity::factory(),
            'price' => fake()->randomFloat(0, 0, 9999999999.),
            'market_type' => fake()->randomElement(["producer","wholesale","retail"]),
            'recorded_date' => fake()->date(),
            'data_source' => fake()->word(),
        ];
    }
}
