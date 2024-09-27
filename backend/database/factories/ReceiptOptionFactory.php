<?php

namespace Database\Factories;

use App\Models\ReceiptOption;
use Illuminate\Database\Eloquent\Factories\Factory;


class ReceiptOptionFactory extends Factory
{
    protected $model = ReceiptOption::class;

    public function definition()
    {
        return [
            'preference_id' => getFirstOrNew(new \App\Models\Preference),
            'name' => $this->faker->name,
            'image_url' => $this->faker->word,
            'footer' => $this->faker->word,
        ];
    }
}