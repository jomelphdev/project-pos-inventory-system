<?php

namespace Database\Factories;

use App\Models\CheckoutStation;
use Illuminate\Database\Eloquent\Factories\Factory;

class CheckoutStationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CheckoutStation::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'preference_id' => getFirstOrNew(new \App\Models\Preference),
            'store_id' => getRandomRow(new \App\Models\Store),
            'name' => $this->faker->name(),
            'terminal' => null,
            'drawer_balance' => null,
            'last_balanced' => null,
        ];
    }
}
