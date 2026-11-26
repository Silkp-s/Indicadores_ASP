<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
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
            ConfiguracionSeeder::class,
        ]);

        $this->crearUsuarios();
    }

    /**
     * Crea (o actualiza) las cuentas de acceso con contraseñas fuertes generadas aleatoriamente.
     */
    private function crearUsuarios(): void
    {
        $adminPassword = Str::password(16);
        $usuarioPassword = Str::password(16);

        $admin = User::updateOrCreate(
            ['email' => 'admin@das.cl'],
            ['name' => 'Administrador', 'apellido' => 'DAS', 'password' => Hash::make($adminPassword)],
        );
        $admin->assignRole('Admin');

        $usuario = User::updateOrCreate(
            ['email' => 'usuario@das.cl'],
            ['name' => 'Usuario', 'apellido' => 'Operativo', 'password' => Hash::make($usuarioPassword)],
        );
        $usuario->assignRole('Usuario');

        $this->command?->info('Cuentas de acceso creadas (guarda estas credenciales):');
        $this->command?->info("  Administrador -> admin@das.cl / {$adminPassword}");
        $this->command?->info("  Usuario       -> usuario@das.cl / {$usuarioPassword}");
    }
}
