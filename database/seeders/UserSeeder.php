<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Eliminar todos los registros de la tabla usuarios
        \App\Models\User::truncate();

        // Insertar el nuevo usuario
        \App\Models\User::create([
            'name' => 'Luis Gonzalo',
            'email' => 'luis@example.com',
            'password' => Hash::make('password'),
        ]);

        // Crear más usuarios de prueba
        \App\Models\User::factory(10)->create();
    }

}

