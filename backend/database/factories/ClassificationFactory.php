<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

namespace Database\Factories;

use App\Models\Classification;
use App\Models\Preference;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClassificationFactory extends Factory
{
    protected $model = Classification::class;

    public function definition()
    {
        return [
            'preference_id' => getFirstOrNew(new \App\Models\Preference),
            'name' => $this->faker->name,
        ];
    }
}