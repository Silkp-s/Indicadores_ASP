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
                'codigo_deis' => '119302',
                'tipo' => 'CESFAM',
                'poblacion_inscrita' => 28500,
                'is_active' => true,
            ],
            [
                'nombre' => 'CESFAM Alcalde Leocán Portus Govinden',
                'codigo_deis' => '119303',
                'tipo' => 'CESFAM',
                'poblacion_inscrita' => 26000,
                'is_active' => true,
            ],
            [
                'nombre' => 'CESFAM Paulina Avendaño Pereda',
                'codigo_deis' => '119307',
                'tipo' => 'CESFAM',
                'poblacion_inscrita' => 45600,
                'is_active' => true,
            ],
            [
                'nombre' => 'CESFAM Los Cerros',
                'codigo_deis' => '119308',
                'tipo' => 'CESFAM',
                'poblacion_inscrita' => 28000,
                'is_active' => true,
            ],
            [
                'nombre' => 'CECOSF 8 de Mayo',
                'codigo_deis' => '119702',
                'tipo' => 'CECOSF',
                'poblacion_inscrita' => 5000,
                'is_active' => true,
            ],
            [
                'nombre' => 'CECOSF Los Lobos - La Gloria',
                'codigo_deis' => '119711',
                'tipo' => 'CECOSF',
                'poblacion_inscrita' => 3500,
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
