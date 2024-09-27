<?php

namespace Database\Factories;

use App\Models\Quantity;
use Illuminate\Database\Eloquent\Factories\Factory;


class QuantityFactory extends Factory
{
    protected $model = Quantity::class;

    public function definition()
    {
        return [
            'created_by' => getRandomRow(new \App\Models\User),
            'store_id' => getRandomRow(new \App\Models\Store),
            'quantity_received' => $this->faker->numberBetween(1, 999),
            'message' => 'Quantity created',
            // 'manifest_number' => $faker->randomNumber(),
        ];
    }

    public function withItem()
    {
        return $this->state(function (array $attributes) {
            return [
                'item_id' => \App\Models\Item::factory(),
            ];
        });
    }
}