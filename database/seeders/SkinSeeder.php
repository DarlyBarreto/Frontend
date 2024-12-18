<?php

namespace Database\Seeders;

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Skin;

class SkinSeeder extends Seeder
{
    public function run()
    {
        Skin::factory(10)->create();
    }
}

