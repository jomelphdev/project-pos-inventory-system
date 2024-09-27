<?php

namespace Database\Factories;

use App\Models\AddedItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class AddedItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AddedItem::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'created_by' => getRandomRow(new \App\Models\User),
            'organization_id' => getFirstOrNew(new \App\Models\Organization),
            'classification_id' => getRandomRow(new \App\Models\Classification, 'preference_id'),
            'title' => $this->faker->sentence(4),
            'price' => $this->faker->numberBetween(1, 10000),
            'original_price' => $this->faker->numberBetween(1, 10000),
        ];
    }
}
