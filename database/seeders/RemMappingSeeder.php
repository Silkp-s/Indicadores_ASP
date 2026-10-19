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
            // Metas Sanitarias Ley 19.813 (Año 2026)
            [
                'codigo' => 'MS-01',
                'hoja' => 'A03',
                'celda_numerador' => 'J26:M26, J28:M28',
                'celda_denominador' => '(J23:M23) - (J37:M37, J40:M40)',
                'seccion' => 'Sección A.2',
                'descripcion' => 'Recuperación del Desarrollo Psicomotor: Niños de 12 a 23 meses con riesgo recuperados',
            ],
            [
                'codigo' => 'MS-02',
                'hoja' => 'P12',
                'celda_numerador' => 'B12:B19',
                'celda_denominador' => null,
                'seccion' => 'Sección A.1',
                'descripcion' => 'Detección precoz cáncer cuello uterino: PAP o Test VPH vigente 25 a 64 años',
            ],
            [
                'codigo' => 'MS-03.A',
                'hoja' => 'A03',
                'celda_numerador' => 'F206:Y207',
                'celda_denominador' => null,
                'seccion' => 'Sección D.7',
                'descripcion' => 'Control con enfoque de riesgo odontológico en población de 0 a 9 años (Pauta CERO)',
            ],
            [
                'codigo' => 'MS-03.B',
                'hoja' => 'A09',
                'celda_numerador' => 'S51, T51',
                'celda_denominador' => null,
                'seccion' => 'Sección C',
                'descripcion' => 'Niños y niñas de 6 años libres de caries (CEOD = 0)',
            ],
            [
                'codigo' => 'MS-03',
                'hoja' => 'A03',
                'celda_numerador' => 'F206:Y207',
                'celda_denominador' => null,
                'seccion' => 'Sección D.7',
                'descripcion' => 'Salud bucal infantil / Pauta CERO 0 a 9 años',
            ],
            [
                'codigo' => 'MS-04',
                'hoja' => 'P4',
                'celda_numerador' => 'C31, C32',
                'celda_denominador' => null,
                'seccion' => 'Sección B',
                'descripcion' => 'Cobertura efectiva DM2 en personas de 15 años y más compensadas (HbA1c < 7% o < 8%)',
            ],
            [
                'codigo' => 'MS-04.B',
                'hoja' => 'P4',
                'celda_numerador' => 'C62:C65',
                'celda_denominador' => 'C17',
                'seccion' => 'Sección C',
                'descripcion' => 'Evaluación anual de pie diabético en personas con DM2 bajo control (15 años y más)',
            ],
            [
                'codigo' => 'MS-05',
                'hoja' => 'P4',
                'celda_numerador' => 'C29, C30',
                'celda_denominador' => null,
                'seccion' => 'Sección B',
                'descripcion' => 'Cobertura efectiva HTA en personas de 15 años y más compensadas (<140/90 o <150/90)',
            ],
            [
                'codigo' => 'MS-06',
                'hoja' => 'A03',
                'celda_numerador' => 'H61',
                'celda_denominador' => 'H67',
                'seccion' => 'Sección A.5',
                'descripcion' => 'Lactancia materna exclusiva (LME) al sexto mes de vida',
            ],
            [
                'codigo' => 'MS-07',
                'hoja' => 'P3',
                'celda_numerador' => 'H59:AM59, C63',
                'celda_denominador' => null,
                'seccion' => 'Sección D',
                'descripcion' => 'Cobertura efectiva Asma (controlado) y EPOC (adecuado) en personas de 5 años y más',
            ],
            [
                'codigo' => 'MS-08',
                'hoja' => 'A26',
                'celda_numerador' => 'I25',
                'celda_denominador' => 'H25',
                'seccion' => 'Sección C',
                'descripcion' => 'Plan de trabajo en salud comunitaria (Participación Social)',
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
