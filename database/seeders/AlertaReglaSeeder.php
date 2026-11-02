<?php

namespace Database\Seeders;

use App\Models\AlertaRegla;
use Illuminate\Database\Seeder;

/**
 * Catálogo de las 10 reglas activas del Motor de Alertas.
 * Los umbrales reproducen los de RemReaderService::calculateCompliance (Crítico < 85 % de la meta).
 */
class AlertaReglaSeeder extends Seeder
{
    public function run(): void
    {
        $reglas = [
            ['REG-01', 'Incumplimiento crítico de meta', 'La medición del mes queda en estado Crítico: el avance es inferior al 85 % de la meta del indicador.', 'Crítica'],
            ['REG-02', 'Meta en riesgo', 'La medición del mes está En Riesgo con un avance entre 85 % y 94 % de la meta.', 'Advertencia'],
            ['REG-03', 'Tendencia descendente sostenida', 'El avance respecto de la meta baja durante 3 meses consecutivos y el indicador aún no cumple.', 'Advertencia'],
            ['REG-04', 'Sin registro de mediciones', 'El establecimiento no cargó ninguna medición del mes, o un indicador que se reportaba el mes anterior dejó de reportarse.', 'Crítica'],
            ['REG-05', 'Proyección anual en riesgo crítico', 'La proyección estadística a diciembre queda bajo el 85 % de la meta (requiere al menos 3 meses de datos).', 'Crítica'],
            ['REG-06', 'Caída abrupta mensual', 'El avance respecto de la meta cae 20 puntos o más frente al mes anterior.', 'Advertencia'],
            ['REG-07', 'Cercano a la meta', 'El indicador está En Riesgo con un avance de 95 % o más de la meta: un refuerzo puntual permite cerrar la brecha.', 'Informativa'],
            ['REG-08', 'Denominador en cero', 'La medición tiene denominador 0 o sin dato: no es posible calcular cumplimiento, por lo que el resultado no es confiable.', 'Advertencia'],
            ['REG-09', 'Dato inconsistente', 'El numerador es mayor que el denominador en un indicador porcentual, o existen valores negativos.', 'Advertencia'],
            ['REG-10', 'Acumulación de indicadores críticos', 'El establecimiento tiene 3 o más indicadores en estado Crítico en el mismo período.', 'Crítica'],
        ];

        foreach ($reglas as [$codigo, $nombre, $descripcion, $severidad]) {
            AlertaRegla::updateOrCreate(
                ['codigo' => $codigo],
                ['nombre' => $nombre, 'descripcion' => $descripcion, 'severidad' => $severidad, 'is_active' => true],
            );
        }
    }
}
