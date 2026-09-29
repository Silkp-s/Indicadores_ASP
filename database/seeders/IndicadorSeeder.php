<?php

namespace Database\Seeders;

use App\Models\Indicador;
use Illuminate\Database\Seeder;

/**
 * Catálogo de ejemplo de indicadores APS (Metas Sanitarias, IAAPS y ministeriales).
 * Se puede correr varias veces: actualiza por código en vez de duplicar.
 */
class IndicadorSeeder extends Seeder
{
    public function run(): void
    {
        $indicadores = [
            ['MS-01', 'Recuperación del desarrollo psicomotor en niños y niñas de 12 a 23 meses', 'Meta Sanitaria',
                'N° de niños y niñas de 12 a 23 meses con riesgo o retraso del DSM recuperados',
                'N° de niños y niñas de 12 a 23 meses con riesgo o retraso del DSM detectado', 90, 'Mensual', 'REM-A', true],
            ['MS-02', 'Cobertura de PAP vigente en mujeres de 25 a 64 años', 'Meta Sanitaria',
                'N° de mujeres de 25 a 64 años inscritas con PAP vigente',
                'N° de mujeres de 25 a 64 años inscritas y validadas', 80, 'Semestral', 'REM-P', true],
            ['MS-03', 'Cobertura de alta odontológica total en adolescentes de 12 años', 'Meta Sanitaria',
                'N° de adolescentes de 12 años con alta odontológica total',
                'Población inscrita de adolescentes de 12 años', 74, 'Mensual', 'REM-A', true],
            ['MS-04', 'Cobertura efectiva de DM2 en personas de 15 años y más', 'Meta Sanitaria',
                'N° de personas de 15 años y más con DM2 compensada (HbA1c bajo meta) en el último control',
                'N° estimado de personas de 15 años y más con DM2 según prevalencia', 29, 'Semestral', 'REM-P', true],
            ['MS-05', 'Cobertura efectiva de HTA en personas de 15 años y más', 'Meta Sanitaria',
                'N° de personas de 15 años y más con HTA con presión arterial bajo meta en el último control',
                'N° estimado de personas de 15 años y más con HTA según prevalencia', 54, 'Semestral', 'REM-P', true],
            ['MS-06', 'Lactancia materna exclusiva en menores de 6 meses', 'Meta Sanitaria',
                'N° de lactantes con lactancia materna exclusiva al control del 6° mes',
                'N° de lactantes con control de salud al 6° mes', 60, 'Mensual', 'REM-A', true],
            ['IAAPS-04', 'Cobertura de examen de medicina preventiva en hombres de 20 a 64 años', 'IAAPS',
                'N° de EMP realizados en hombres de 20 a 64 años',
                'Población inscrita validada de hombres de 20 a 64 años', 25, 'Mensual', 'REM-A', true],
            ['IAAPS-05', 'Cobertura de examen de medicina preventiva en mujeres de 45 a 64 años', 'IAAPS',
                'N° de EMP realizados en mujeres de 45 a 64 años',
                'Población inscrita validada de mujeres de 45 a 64 años', 30, 'Mensual', 'REM-A', true],
            ['IAAPS-06', 'Cobertura de evaluación del desarrollo psicomotor en niños y niñas de 12 a 23 meses', 'IAAPS',
                'N° de niños y niñas de 12 a 23 meses con evaluación del DSM',
                'Población inscrita de niños y niñas de 12 a 23 meses', 94, 'Mensual', 'REM-A', true],
            ['IAAPS-10', 'Cobertura de control de salud integral en adolescentes de 10 a 14 años', 'IAAPS',
                'N° de controles de salud integral realizados a adolescentes de 10 a 14 años',
                'Población inscrita de adolescentes de 10 a 14 años', 22, 'Trimestral', 'REM-A', true],
            ['IAAPS-12', 'Cobertura de atención integral de salud mental en personas de 5 años y más', 'IAAPS',
                'N° de personas de 5 años y más bajo control en el programa de salud mental',
                'N° estimado de personas de 5 años y más con trastornos mentales según prevalencia', 23, 'Semestral', 'REM-P', true],
            ['MIN-03', 'Cobertura de vacunación anti-influenza en población objetivo', 'Ministerial',
                'N° de personas de la población objetivo vacunadas contra influenza',
                'Población objetivo de la campaña de invierno', 85, 'Anual', 'DEIS', true],
            ['MIN-04', 'Cumplimiento de garantías de oportunidad GES en APS', 'Ministerial',
                'N° de garantías de oportunidad GES cumplidas en APS',
                'N° de garantías de oportunidad GES exigibles en APS', 95, 'Mensual', 'SIGGES', true],
            ['MIN-05', 'Cobertura de visita domiciliaria integral a familias en riesgo', 'Ministerial',
                'N° de familias en riesgo con visita domiciliaria integral realizada',
                'N° de familias evaluadas en riesgo', 20, 'Trimestral', 'REM-A', false],
        ];

        foreach ($indicadores as [$codigo, $nombre, $tipo, $numerador, $denominador, $meta, $periodicidad, $fuente, $activo]) {
            Indicador::updateOrCreate(['codigo' => $codigo], [
                'nombre' => $nombre,
                'tipo' => $tipo,
                'numerador_description' => $numerador,
                'denominador_description' => $denominador,
                'target_value' => $meta,
                'periodicidad' => $periodicidad,
                'fu' => $fuente,
                'is_active' => $activo,
            ]);
        }
    }
}
