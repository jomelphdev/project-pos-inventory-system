<?php

namespace Database\Factories;

use App\Models\UserFeedback;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFeedbackFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = UserFeedback::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            "organization_id" => getFirstOrNew(new \App\Models\Organization),
            "user_id" => getRandomRow(new \App\Models\User),
            "prompt" => $this->faker->text,
            "feedback" => $this->faker->text,
            "origin" => "sign-in"
        ];
    }
}
