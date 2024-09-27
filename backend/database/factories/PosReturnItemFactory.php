<?php

namespace Database\Factories;

use App\Models\PosReturnItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class PosReturnItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PosReturnItem::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'pos_return_id' => \App\Models\PosReturn::factory(),
            'pos_order_item_id' => \App\Models\PosOrderItem::factory(),
            'item_id' => \App\Models\Item::factory(),
            'quantity_returned' => $this->faker->numberBetween(1, 999),
            'action' => $this->faker->numberBetween(0, 1)
        ];
    }
}
