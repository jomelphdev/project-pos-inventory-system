<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BlogPostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $categories = ['sales', 'marketing', 'operations'];

        return [
            'title' => $this->faker->sentence,
            'sub_heading' => $this->faker->sentence,
            'slug' => $this->faker->slug,
            'content' => $this->faker->text,
            'image' => 'https://picsum.photos/256/256',
            'category' => $categories[array_rand($categories)],
            'is_published' => $this->faker->boolean,
            'meta_title' => $this->faker->title,
            'meta_description' => $this->faker->sentence,
            'meta_image_alt' => $this->faker->title,
        ];
    }
}
