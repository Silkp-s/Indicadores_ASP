<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

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
            EstablecimientosSeeder::class,
            IndicadorSeeder::class,
            AlertaReglaSeeder::class,
        ]);

        // En producción la clave "password" del repositorio público dejaría la cuenta Admin abierta:
        // ahí se generan claves aleatorias y se muestran una sola vez en la consola.
        $produccion = app()->isProduction();
        $claveAdmin = $produccion ? Str::password(16) : 'password';
        $claveUsuario = $produccion ? Str::password(16) : 'password';

        // Create Admin user
        $admin = User::factory()->create([
            'name' => 'Admin',
            'apellido' => 'DAS',
            'email' => 'admin@das.cl',
            'password' => bcrypt($claveAdmin),
        ]);
        $admin->assignRole('Admin');

        // Create regular Usuario
        $usuario = User::factory()->create([
            'name' => 'Usuario',
            'apellido' => 'Prueba',
            'email' => 'usuario@das.cl',
            'password' => bcrypt($claveUsuario),
        ]);
        $usuario->assignRole('Usuario');

        if ($produccion) {
            $this->command?->warn("Clave de admin@das.cl: {$claveAdmin}");
            $this->command?->warn("Clave de usuario@das.cl: {$claveUsuario}");

            return;
        }

        // Additional test users
        User::factory(13)->create()->each(function ($user) {
            $user->assignRole('Usuario');
        });
    }
}
