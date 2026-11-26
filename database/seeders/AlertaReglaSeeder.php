<?php

namespace Database\Seeders;

use App\Models\AlertaRegla;
use Illuminate\Database\Seeder;

/**
 * Catálogo de las 10 reglas activas del Motor de Alertas, con sus umbrales configurables.
 * Los valores por defecto reproducen los de RemReaderService::calculateCompliance (Crítico < 85 % de la meta).
 */
class AlertaReglaSeeder extends Seeder
{
    public function run(): void
    {
        $reglas = [
            ['REG-01', 'Incumplimiento crítico de meta', 'La medición del mes queda en estado Crítico: el avance es inferior al 85 % de la meta del indicador.', 'Crítica', []],
            ['REG-02', 'Meta en riesgo', 'La medición del mes está En Riesgo con un avance bajo el umbral de cercanía a la meta.', 'Advertencia', ['umbral_cercano' => 95]],
            ['REG-03', 'Tendencia descendente sostenida', 'El avance respecto de la meta baja durante varios meses consecutivos y el indicador aún no cumple.', 'Advertencia', ['meses' => 3]],
            ['REG-04', 'Sin registro de mediciones', 'El establecimiento no cargó ninguna medición del mes, o un indicador que se reportaba el mes anterior dejó de reportarse.', 'Crítica', []],
            ['REG-05', 'Proyección anual en riesgo crítico', 'La proyección estadística a diciembre queda bajo el 85 % de la meta (requiere un mínimo de meses de historia).', 'Crítica', ['min_puntos' => 3]],
            ['REG-06', 'Caída abrupta mensual', 'El avance respecto de la meta cae una cantidad mínima de puntos frente al mes anterior.', 'Advertencia', ['caida' => 20]],
            ['REG-07', 'Cercano a la meta', 'El indicador está En Riesgo con un avance igual o superior al umbral de cercanía: un refuerzo puntual permite cerrar la brecha.', 'Informativa', ['umbral_cercano' => 95]],
            ['REG-08', 'Denominador en cero', 'La medición tiene denominador 0 o sin dato: no es posible calcular cumplimiento, por lo que el resultado no es confiable.', 'Advertencia', []],
            ['REG-09', 'Dato inconsistente', 'El numerador es mayor que el denominador en un indicador porcentual, o existen valores negativos.', 'Advertencia', []],
            ['REG-10', 'Acumulación de indicadores críticos', 'El establecimiento tiene una cantidad mínima de indicadores en estado Crítico en el mismo período.', 'Crítica', ['criticos' => 3]],
        ];

        foreach ($reglas as [$codigo, $nombre, $descripcion, $severidad, $parametros]) {
            AlertaRegla::updateOrCreate(
                ['codigo' => $codigo],
                ['nombre' => $nombre, 'descripcion' => $descripcion, 'severidad' => $severidad, 'is_active' => true, 'parametros' => $parametros],
            );
        }
    }
}
