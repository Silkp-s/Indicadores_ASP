<?php

namespace Database\Seeders;

use App\Models\Indicador;
use Illuminate\Database\Seeder;

/**
 * Catálogo de indicadores APS: Metas Sanitarias Ley 19.813 e IAAPS 2025.
 * Se puede correr varias veces: actualiza por código en vez de duplicar.
 */
class IndicadorSeeder extends Seeder
{
    public function run(): void
    {
        $indicadores = [
            // Metas Sanitarias Ley 19.813 (2026)
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
            ['MS-07', 'Compensación de personas de 15 años y más con Asma y EPOC', 'Meta Sanitaria',
                'N° de personas con Asma y EPOC en control con patología compensada',
                'Población estimada de personas con Asma y EPOC según prevalencia', 65, 'Mensual', 'REM-A', true],
            ['MS-08', 'Estrategia de participación y desarrollo de salud comunitaria', 'Meta Sanitaria',
                'Actividades comunitarias ejecutadas según programación anual',
                'Total de actividades comunitarias programadas', 100, 'Mensual', 'REM-A', true],

            // Indicadores IAAPS 2025
            ['IAAPS-01', 'Porcentaje de Centros de Salud autoevaluados mediante instrumento MAIS', 'IAAPS',
                'N° de centros de salud de la comuna autoevaluados en MAIS',
                'N° de establecimientos de salud de la comuna año 2025', 100, 'Anual', 'DEIS', true],
            ['IAAPS-02.1', 'Acceso a la atención de salud en horario extendido', 'IAAPS',
                'N° establecimientos funcionando de 8:00 a 20:00 lun-vie y sáb 9:00 a 13:00',
                'N° total de establecimientos visitados', 100, 'Semestral', 'DEIS', true],
            ['IAAPS-02.2', 'Disponibilidad de fármacos trazadores', 'IAAPS',
                'N° de fármacos trazadores disponibles',
                'N° total de fármacos trazadores', 100, 'Mensual', 'DEIS', true],
            ['IAAPS-03', 'Tasa de consultas médicas de morbilidad', 'IAAPS',
                'N° de consultas de morbilidad realizadas por profesional médico',
                'Población inscrita validada', 1.25, 'Mensual', 'REM-A', true],
            ['IAAPS-04', 'Porcentaje de derivación al nivel secundario', 'IAAPS',
                'N° SIC de controles y consultas médicas generadas en APS',
                'N° total de controles y consultas médicas realizadas en APS', 10, 'Mensual', 'REM-A', true],
            ['IAAPS-05', 'Tasa de Visita Domiciliaria Integral', 'IAAPS',
                'N° visitas domiciliarias integrales realizadas',
                'N° de familias (población inscrita validada / 3,3)', 0.15, 'Mensual', 'REM-A', true],
            ['IAAPS-06.1A', 'Cobertura examen de medicina preventiva en mujeres de 20 a 64 años', 'IAAPS',
                'N° de EMP realizado en población de mujeres de 20 a 64 años',
                'Población de mujeres de 20 a 64 años inscrita validada - Población embarazada de 20 a 54 años', 30, 'Mensual', 'REM-A', true],
            ['IAAPS-06.1B', 'Cobertura examen de medicina preventiva en hombres de 20 a 64 años', 'IAAPS',
                'N° de EMP realizado en población de hombres de 20 a 64 años',
                'Población de hombres de 20 a 64 años inscrita validada', 25, 'Mensual', 'REM-A', true],
            ['IAAPS-06.2', 'Cobertura de examen de medicina preventiva del adulto de 65 años y más', 'IAAPS',
                'N° de adultos de 65 y más años con examen de medicina preventiva',
                'Población inscrita validada de 65 años y más', 50, 'Mensual', 'REM-A', true],
            ['IAAPS-07', 'Cobertura de Evaluación del Desarrollo Psicomotor en niños(as) de 12 a 23 meses bajo control', 'IAAPS',
                'N° de niños y niñas de 12 a 23 meses con evaluación de desarrollo psicomotor',
                'N° de niños y niñas 12 a 23 meses bajo control', 94, 'Mensual', 'REM-A', true],
            ['IAAPS-08', 'Cobertura de control de salud integral a adolescentes de 10 a 19 años', 'IAAPS',
                'N° de controles de salud integral realizados a adolescentes de 10 a 19 años',
                'Población total de adolescente de 10 a 19 años inscrita validada', 22, 'Trimestral', 'REM-A', true],
            ['IAAPS-09.1', 'Cobertura atención integral a personas con factores de riesgo y trastornos mentales', 'IAAPS',
                'N° de personas con factores de riesgo/trastornos mentales bajo control de 0 y más años',
                'N° de personas esperadas según prevalencia', 23, 'Semestral', 'REM-P', true],
            ['IAAPS-09.2', 'Tasa de controles de atención Integral en salud mental', 'IAAPS',
                'N° de controles de salud mental totales realizados a personas de 0 y más años',
                'N° de personas bajo control en el programa de salud mental de 0 y más años', 3.5, 'Semestral', 'REM-P', true],
            ['IAAPS-09.3', 'Personas egresadas por alta clínica en salud mental de 0 y más años', 'IAAPS',
                'N° de personas egresadas por alta clínica de 0 y más año',
                'N° de personas bajo control en el programa de salud mental de 0 y más años', 15, 'Semestral', 'REM-P', true],
            ['IAAPS-10', 'Cumplimiento de garantías explícitas en salud (GES) en Atención Primaria', 'IAAPS',
                'N° garantías cumplidas + exceptuadas + incumplidas atendidas',
                'Total garantías exigibles y retrasadas acumuladas', 95, 'Mensual', 'SIGGES', true],
            ['IAAPS-11', 'Cobertura de vacunación anti-influenza en población objetivo', 'IAAPS',
                'Número de personas pertenecientes a grupos objetivos vacunados con anti-influenza',
                'Total de población inscrita validada de los grupos objetivos', 85, 'Anual', 'DEIS', true],
            ['IAAPS-12', 'Ingreso precoz de mujeres a control de embarazo antes de las 14 semanas', 'IAAPS',
                'N° de mujeres embarazadas ingresadas antes de las 14 semanas a control',
                'Total de mujeres embarazadas ingresadas a control', 85, 'Mensual', 'REM-A', true],
            ['IAAPS-13', 'Cobertura adolescentes 15 a 19 años con método de regulación de la fertilidad', 'IAAPS',
                'N° de adolescentes de 15 a 19 años inscritos que usan método de regulación de la fertilidad',
                'Total adolescentes de 15 a 19 años inscritos', 35, 'Mensual', 'REM-A', true],
            ['IAAPS-14', 'Cobertura de diabetes mellitus 2 en personas de 15 y más años', 'IAAPS',
                'N° de personas de 15 años y más con diabetes mellitus 2 bajo control',
                'Total de personas con DM2 de 15 y más años esperados según prevalencia etaria', 55, 'Semestral', 'REM-P', true],
            ['IAAPS-15', 'Cobertura de hipertensión arterial en personas de 15 y más años', 'IAAPS',
                'N° de personas de 15 años y más con hipertensión arterial bajo control',
                'Total de personas con HTA de 15 y más años esperados según prevalencia etaria', 65, 'Semestral', 'REM-P', true],
            ['IAAPS-16', 'Proporción de niñas y niños menores de 3 años libre de caries (CEOD = 0)', 'IAAPS',
                'N° de niños y niñas menores de 3 años con registro CEOD = 0',
                'N° de niñas y niños menores de 3 años inscritos', 75, 'Mensual', 'REM-A', true],
            ['IAAPS-17', 'Prevalencia de normalidad nutricional en menores de 2 años bajo control', 'IAAPS',
                'N° de niños y niñas menores de 2 años con estado nutricional normal',
                'N° de niñas y niños menores de 2 bajo control', 65, 'Mensual', 'REM-P', true],
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
