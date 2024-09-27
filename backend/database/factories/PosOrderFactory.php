<?php

namespace Database\Factories;

use App\Models\PosOrder;
use Illuminate\Database\Eloquent\Factories\Factory;


class PosOrderFactory extends Factory
{
    protected $model = PosOrder::class;

    public function definition()
    {
        return [
            'created_by' => getRandomRow(new \App\Models\User),
            'checkout_station_id' => null,
            'organization_id' => getFirstOrNew(new \App\Models\Organization),
            'store_id' => getRandomRow(new \App\Models\Store),
            'cash' => $this->faker->numberBetween(0, 10000),
            'card' => $this->faker->numberBetween(0, 10000),
            'ebt' => $this->faker->numberBetween(0, 10000),
            'sub_total' => $this->faker->numberBetween(0, 10000),
            'tax' => $this->faker->numberBetween(0, 10000),
            'total' => $this->faker->numberBetween(0, 10000),
            'amount_paid' => $this->faker->numberBetween(0, 10000),
            'change' => $this->faker->numberBetween(0, 10000),
            'tax_rate' => $this->faker->numberBetween(0.01, 0.12),
        ];
    }
}