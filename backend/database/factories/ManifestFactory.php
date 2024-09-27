<?php

namespace Database\Factories;

use App\Models\Manifest;
use Illuminate\Database\Eloquent\Factories\Factory;

class ManifestFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Manifest::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'organization_id' => getFirstOrNew(new \App\Models\Organization),
            'manifest_name' => substr($this->faker->name, 0, 20),
        ];
    }
}
