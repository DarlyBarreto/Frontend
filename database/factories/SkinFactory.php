<?php

namespace Database\Factories;

use App\Models\Skin;
use Illuminate\Database\Eloquent\Factories\Factory;

class SkinFactory extends Factory
{
    protected $model = Skin::class;

    public function definition()
    {
        return [
            'name' => $this->faker->word,
            'image_path' => '/images/skin' . $this->faker->unique()->numberBetween(1, 10000) . '.png',
            'user_id' => \App\Models\User::factory(),
            'category_id' => \App\Models\Category::factory(),
            'likes' => $this->faker->numberBetween(0, 100),
            'dislikes' => $this->faker->numberBetween(0, 100),
        ];
    }
}
