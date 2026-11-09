<?php

namespace Database\Seeders;

use App\Models\Indicador;
use App\Models\RemMapping;
use App\Services\RemMappingDefaults;
use Illuminate\Database\Seeder;

class RemMappingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (RemMappingDefaults::all() as $item) {
            $indicador = Indicador::where('codigo', $item['codigo'])->first();

            if ($indicador) {
                RemMapping::updateOrCreate(
                    [
                        'indicadores_id' => $indicador->id,
                    ],
                    [
                        'hoja' => $item['hoja'],
                        'celda_numerador' => $item['celda_numerador'],
                        'celda_denominador' => $item['celda_denominador'],
                        'seccion' => $item['seccion'],
                        'descripcion' => $item['descripcion'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
