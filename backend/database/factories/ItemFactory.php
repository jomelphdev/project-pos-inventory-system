<?php

namespace Database\Factories;

use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;


class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition()
    {
        return [
            'created_by' => getRandomRow(new \App\Models\User),
            'organization_id' => getFirstOrNew(new \App\Models\Organization),
            'classification_id' => getRandomRow(new \App\Models\Classification, 'preference_id'),
            'condition_id' => getRandomRow(new \App\Models\Condition, 'preference_id'),
            'consignor_id' => null,
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->text,
            'price' => $this->faker->numberBetween(1, 10000),
            'original_price' => $this->faker->numberBetween(1, 10000),
            'sku' => strval($this->faker->numberBetween(1000000000, 9999999999)),
            'upc' => strval($this->faker->numberBetween(100000000000, 999999999999)),
            'asin' => "test",
            'mpn' => substr($this->faker->word, 0, 100),
            'merchant_name' => $this->faker->word,
            'merchant_price' => $this->faker->numberBetween(1, 10000),
            'brand' => $this->faker->word,
            'color' => $this->faker->word,
            'ean' => substr($this->faker->word, 0, 13),
            'elid' => substr($this->faker->word, 0, 12),
            'condition_description' => $this->faker->word,
            'consignment_fee' => null
        ];
    }

    public function withConsignor()
    {
        return $this->state(function (array $attributes) {
            return [
                'consignor_id' => \App\Models\Consignor::factory(),
                'consignment_fee' => $this->faker->numberBetween(1, 1000)
            ];
        });
    }
}