<?php

namespace Database\Seeders;

use App\Models\Indicador;
use App\Models\RemMapping;
use Illuminate\Database\Seeder;

class RemMappingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mappings = [
            // Metas Sanitarias
            [
                'codigo' => 'MS-01',
                'hoja' => 'A03',
                'celda_numerador' => 'J26:M26, J28:M28',
                'celda_denominador' => 'J23:M23',
                'seccion' => 'Sección A.2',
                'descripcion' => 'Evaluación del Desarrollo Psicomotor: Niños recuperados de riesgo o retraso (12 a 23 meses)',
            ],
            [
                'codigo' => 'MS-02',
                'hoja' => 'P06',
                'celda_numerador' => 'G21',
                'celda_denominador' => null,
                'seccion' => 'Sección B.1',
                'descripcion' => 'Cobertura de PAP vigente en mujeres de 25 a 64 años',
            ],
            [
                'codigo' => 'MS-03',
                'hoja' => 'A03',
                'celda_numerador' => 'F206:Y207',
                'celda_denominador' => null,
                'seccion' => 'Sección D.7',
                'descripcion' => 'Salud bucal infantil / alta odontológica en adolescentes',
            ],
            [
                'codigo' => 'MS-04',
                'hoja' => 'P06',
                'celda_numerador' => 'G23',
                'celda_denominador' => null,
                'seccion' => 'Sección B.1',
                'descripcion' => 'Cobertura efectiva DM2 en personas de 15 años y más compensadas',
            ],
            [
                'codigo' => 'MS-05',
                'hoja' => 'P06',
                'celda_numerador' => 'G24',
                'celda_denominador' => null,
                'seccion' => 'Sección B.1',
                'descripcion' => 'Cobertura efectiva HTA en personas de 15 años y más compensadas',
            ],
            [
                'codigo' => 'MS-06',
                'hoja' => 'A03',
                'celda_numerador' => 'L142, P142',
                'celda_denominador' => 'L141, P141',
                'seccion' => 'Sección B.2',
                'descripcion' => 'Lactancia materna exclusiva al sexto mes',
            ],
            [
                'codigo' => 'MS-07',
                'hoja' => 'A06',
                'celda_numerador' => 'L34, M34, L36, M36',
                'celda_denominador' => null,
                'seccion' => 'Sección A.3',
                'descripcion' => 'Compensación de personas de 15 años y más con Asma y EPOC',
            ],
            [
                'codigo' => 'MS-08',
                'hoja' => 'A26',
                'celda_numerador' => 'I25',
                'celda_denominador' => 'H25',
                'seccion' => 'Sección C',
                'descripcion' => 'Estrategia de participación comunitaria en salud',
            ],

            // Indicadores IAAPS
            [
                'codigo' => 'IAAPS-02.1',
                'hoja' => 'A01',
                'celda_numerador' => 'E15',
                'celda_denominador' => null,
                'seccion' => 'Sección A',
                'descripcion' => 'Acceso a la atención de salud en horario extendido',
            ],
            [
                'codigo' => 'IAAPS-02.2',
                'hoja' => 'A01',
                'celda_numerador' => 'F18',
                'celda_denominador' => null,
                'seccion' => 'Sección A',
                'descripcion' => 'Disponibilidad de fármacos trazadores',
            ],
            [
                'codigo' => 'IAAPS-03',
                'hoja' => 'A01',
                'celda_numerador' => 'E20',
                'celda_denominador' => null,
                'seccion' => 'Sección A',
                'descripcion' => 'Tasa de consultas médicas de morbilidad en APS',
            ],
            [
                'codigo' => 'IAAPS-04',
                'hoja' => 'A01',
                'celda_numerador' => 'G15',
                'celda_denominador' => null,
                'seccion' => 'Sección A',
                'descripcion' => 'Porcentaje de derivación al nivel secundario',
            ],
            [
                'codigo' => 'IAAPS-05',
                'hoja' => 'A01',
                'celda_numerador' => 'H22',
                'celda_denominador' => null,
                'seccion' => 'Sección A',
                'descripcion' => 'Tasa de Visita Domiciliaria Integral (VDI)',
            ],
        ];

        foreach ($mappings as $item) {
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
