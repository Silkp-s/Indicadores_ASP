<?php

namespace Database\Seeders;

use App\Models\Establecimiento;
use Illuminate\Database\Seeder;

class EstablecimientosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $establecimientos = [
            [
                'nombre' => 'CESFAM San Vicente',
                'codigo_deis' => '119301',
                'tipo' => 'CESFAM',
                'poblacion_inscrita' => 28500,
                'is_active' => true,
            ],
            [
                'nombre' => 'CESFAM Los Pinos',
                'codigo_deis' => '119302',
                'tipo' => 'CESFAM',
                'poblacion_inscrita' => 32000,
                'is_active' => true,
            ],
            [
                'nombre' => 'CECOSF Bellavista',
                'codigo_deis' => '119303',
                'tipo' => 'CECOSF',
                'poblacion_inscrita' => 15000,
                'is_active' => true,
            ],
            [
                'nombre' => 'SAR Central',
                'codigo_deis' => '119304',
                'tipo' => 'SAR',
                'poblacion_inscrita' => 50000,
                'is_active' => true,
            ],
            [
                'nombre' => 'CESFAM Los Cerros',
                'codigo_deis' => '119308',
                'tipo' => 'CESFAM',
                'poblacion_inscrita' => 35000,
                'is_active' => true,
            ],
        ];

        foreach ($establecimientos as $item) {
            Establecimiento::updateOrCreate(
                ['nombre' => $item['nombre']],
                $item
            );
        }
    }
}
