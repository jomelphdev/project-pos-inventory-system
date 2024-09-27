<?php

namespace Database\Factories;

use App\Models\ManifestItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class ManifestItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ManifestItem::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'organization_id' => getFirstOrNew(new \App\Models\Organization),
            'manifest_id' => \App\Models\Manifest::factory(),
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->text,
            'price' => $this->faker->numberBetween(1, 10000),
            'quantity' => $this->faker->numberBetween(1, 10),
            'upc' => strval($this->faker->numberBetween(100000000000, 999999999999)),
            'asin' => "test",
            'mpn' => substr($this->faker->word, 0, 100),
            'cost' => $this->faker->numberBetween(1, 5000),
            'lpn' => $this->faker->word,
        ];
    }
}
