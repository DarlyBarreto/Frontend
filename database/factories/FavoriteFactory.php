<?php

namespace Database\Factories;

use App\Models\Favorite;
use Illuminate\Database\Eloquent\Factories\Factory;

class FavoriteFactory extends Factory
{
    protected $model = Favorite::class;

    public function definition()
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'skin_id' => \App\Models\Skin::factory(),
        ];
    }
}
