<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
        ]);

        // Create Admin user
        $admin = User::factory()->create([
            'name' => 'Admin',
            'apellido' => 'DAS',
            'email' => 'admin@das.cl',
            'password' => bcrypt('password'),
        ]);
        $admin->assignRole('Admin');

        // Create regular Usuario
        $usuario = User::factory()->create([
            'name' => 'Usuario',
            'apellido' => 'Prueba',
            'email' => 'usuario@das.cl',
            'password' => bcrypt('password'),
        ]);
        $usuario->assignRole('Usuario');

        // Additional test users
        User::factory(13)->create()->each(function ($user) {
            $user->assignRole('Usuario');
        });
    }
}
