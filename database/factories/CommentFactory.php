<?php

namespace Database\Factories;

use App\Models\Comment;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition()
    {
        return [
            'content' => $this->faker->sentence,
            'user_id' => \App\Models\User::factory(),
            'skin_id' => \App\Models\Skin::factory(),
        ];
    }
}
