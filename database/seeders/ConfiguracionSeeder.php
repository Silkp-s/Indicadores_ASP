<?php

namespace Database\Seeders;

use App\Models\Configuracion;
use Illuminate\Database\Seeder;

class ConfiguracionSeeder extends Seeder
{
    public function run(): void
    {
        Configuracion::updateOrCreate(
            ['clave' => 'umbral_critico'],
            ['valor' => (string) Configuracion::UMBRAL_CRITICO_DEFECTO],
        );

        Configuracion::updateOrCreate(
            ['clave' => 'ano_trabajo'],
            ['valor' => (string) date('Y')],
        );
    }
}
