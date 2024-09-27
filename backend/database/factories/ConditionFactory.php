<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

namespace Database\Factories;

use App\Models\Condition;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConditionFactory extends Factory
{
    protected $model = Condition::class;

    public function definition()
    {
        return [
            'preference_id' => getFirstOrNew(new \App\Models\Preference),
            'name' => $this->faker->name,
        ];
    }
}