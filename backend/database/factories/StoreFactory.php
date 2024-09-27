<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;


class StoreFactory extends Factory
{
    protected $model = Store::class;

    public function definition()
    {
        return [
            'preference_id' => getFirstOrNew(new \App\Models\Preference),
            'organization_id' => getFirstOrNew(new \App\Models\Organization),
            'receipt_option_id' => \App\Models\ReceiptOption::factory(),
            'state_id' => \App\Models\State::factory(),
            'city' => substr($this->faker->city, 0, 25),
            'address' => $this->faker->word,
            'zip' => strval($this->faker->postcode),
            'name' => $this->faker->name,
            'phone' => "9998889999",
            'tax_rate' => $this->faker->randomFloat(2, 0.01, 0.12),
        ];
    }
}
