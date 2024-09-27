<?php

namespace Database\Factories;

use App\Models\PreferenceOption;
use Illuminate\Database\Eloquent\Factories\Factory;

class PreferenceOptionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PreferenceOption::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'store_id' => getRandomRow(new \App\Models\Store),
            'model_id' => $this->faker->random_int(0, 99999),
            'model_type' => 'App\Models\Classification',
            'key' => 'discount',
            'value' => 10,
            'value_type' => 'double'
        ];
    }
}
