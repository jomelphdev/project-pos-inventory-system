<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ItemSpecificDiscountsFactory>
 */

class ItemSpecificDiscountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            "item_id" => \App\Models\Item::factory(),
			"quantity" => $this->faker->numberBetween(1, 10),
			"discount_amount" => $this->faker->numberBetween(1000, 5000),
            "times_applicable" => $this->faker->numberBetween(0, 2),
            "active_at" => null,
            "expires_at" => null,
            "deleted_at" => null,
        ];
    }
}
