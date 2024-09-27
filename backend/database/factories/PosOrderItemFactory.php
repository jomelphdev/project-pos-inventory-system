<?php

namespace Database\Factories;

use App\Models\PosOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;


class PosOrderItemFactory extends Factory
{
    protected $model = PosOrderItem::class;

    public function definition()
    {
        $discount = \App\Models\Discount::factory()->create();

        return [
            'pos_order_id' => \App\Models\PosOrder::factory(),
            'item_id' => \App\Models\Item::factory(),
            'discount_id' => $discount->id,
            'price' => $this->faker->numberBetween(500, 10000),
            'discount_percent' => $discount->discount,
            'quantity_ordered' => $this->faker->numberBetween(1, 1000),
            'is_ebt' => $this->faker->boolean,
        ];
    }
}