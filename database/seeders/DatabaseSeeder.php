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

        // prueba seeders metas sanitarias
        Indicator::create([
            'codigo' => 'META-1',
            'nombre' => 'Recuperación del Desarrollo Psicomotor (12 a 23 meses)',
            'tipo' => 'Meta Sanitaria',
            'numerador_description' => 'Nº de niñas y niños de 12 a 23 meses diagnosticados con riesgo del DSM recuperados[cite: 24]',
            'denominador_description' => 'Nº de niñas y niños de 12 a 23 meses diagnosticados en su primera evaluación con riesgo[cite: 24]',
            'target_value' => 90.00, // 90% Meta Nacional[cite: 24]
            'periodicidad' => 'Mensual',
            'data_source' => 'REM-A03, Sección A.2',
            'is_active' => true,
        ]);
    }
}
