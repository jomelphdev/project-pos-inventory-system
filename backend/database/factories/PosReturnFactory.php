<?php

namespace Database\Factories;

use App\Models\PosReturn;
use Illuminate\Database\Eloquent\Factories\Factory;

class PosReturnFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PosReturn::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'created_by' => getRandomRow(new \App\Models\User),
            'checkout_station_id' => null,
            'organization_id' => getFirstOrNew(new \App\Models\Organization),
            'store_id' => getRandomRow(new \App\Models\Store),
            'pos_order_id' => \App\Models\PosOrder::factory(),
            'cash' => $this->faker->numberBetween(0, 10000),
            'card' => $this->faker->numberBetween(0, 10000),
            'ebt' => $this->faker->numberBetween(0, 10000),
            'sub_total' => $this->faker->numberBetween(0, 10000),
            'tax' => $this->faker->numberBetween(0, 10000),
            'total' => $this->faker->numberBetween(0, 10000),
        ];
    }
}
