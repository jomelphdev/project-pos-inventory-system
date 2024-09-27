<?php

namespace Database\Factories;

use App\Models\Consignor;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConsignorFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Consignor::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'preference_id' => getFirstOrNew(new \App\Models\Preference),
            'name' => $this->faker->name(),
            'consignment_fee_percentage' => $this->faker->numberBetween(0, 0.10)
        ];
    }
}
