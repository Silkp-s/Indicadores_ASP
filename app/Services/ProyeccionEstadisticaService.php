<?php

namespace App\Services;

use App\Models\Indicador;
use App\Models\IndicadorMedida;

class ProyeccionEstadisticaService
{
    /**
     * Nombres en español de los 12 meses del año.
     *
     * @var array<int, string>
     */
    public const MESES_NOMBRES = [
        1 => 'Enero',
        2 => 'Febrero',
        3 => 'Marzo',
        4 => 'Abril',
        5 => 'Mayo',
        6 => 'Junio',
        7 => 'Julio',
        8 => 'Agosto',
        9 => 'Septiembre',
        10 => 'Octubre',
        11 => 'Noviembre',
        12 => 'Diciembre',
    ];

    /**
     * Abreviaciones de los 12 meses para gráficos.
     *
     * @var list<string>
     */
    public const MESES_ABREV = [
        'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun',
        'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic',
    ];

    /**
     * Calcula la regresión lineal simple por mínimos cuadrados ordinarios (MCO)
     * a partir de una serie temporal de puntos (mes => valor).
     *
     * @param  array<int, float|int>  $puntos  Mes (1..12) => Valor porcentual observado
     * @return array{
     *     pendiente: float,
     *     intercepto: float,
     *     r2: float,
     *     correlacion: float,
     *     tendencia: string,
     *     proyeccion_mes_12: float,
     *     curva_proyectada: array<int, float>
     * }
     */
    public function calcularRegresionLineal(array $puntos): array
    {
        $n = count($puntos);

        if ($n === 0) {
            return [
                'pendiente' => 0.0,
                'intercepto' => 0.0,
                'r2' => 0.0,
                'correlacion' => 0.0,
                'tendencia' => 'Estable',
                'proyeccion_mes_12' => 0.0,
                'curva_proyectada' => array_fill(1, 12, 0.0),
            ];
        }

        // Caso con un solo punto observado: proyección constante con ritmo neutro
        if ($n === 1) {
            $mesUnico = (int) array_key_first($puntos);
            $valorUnico = (float) $puntos[$mesUnico];

            return [
                'pendiente' => 0.0,
                'intercepto' => round($valorUnico, 2),
                'r2' => 1.0,
                'correlacion' => 0.0,
                'tendencia' => 'Estable',
                'proyeccion_mes_12' => round($valorUnico, 2),
                'curva_proyectada' => array_fill(1, 12, round($valorUnico, 2)),
            ];
        }

        // Cálculo de sumatorias para MCO: y = m*x + b
        $sumX = 0.0;
        $sumY = 0.0;

        foreach ($puntos as $x => $y) {
            $sumX += (float) $x;
            $sumY += (float) $y;
        }

        $meanX = $sumX / $n;
        $meanY = $sumY / $n;

        $sxx = 0.0;
        $syy = 0.0;
        $sxy = 0.0;

        foreach ($puntos as $x => $y) {
            $dx = (float) $x - $meanX;
            $dy = (float) $y - $meanY;
            $sxx += $dx * $dx;
            $syy += $dy * $dy;
            $sxy += $dx * $dy;
        }

        if ($sxx > 0.0) {
            $pendiente = $sxy / $sxx;
            $intercepto = $meanY - ($pendiente * $meanX);

            if ($syy > 0.0) {
                $denominadorR = sqrt($sxx * $syy);
                $correlacion = $denominadorR > 0.0 ? ($sxy / $denominadorR) : 0.0;
                $r2 = max(0.0, min(1.0, $correlacion * $correlacion));
            } else {
                $correlacion = 0.0;
                $r2 = 1.0;
            }
        } else {
            $pendiente = 0.0;
            $intercepto = $meanY;
            $correlacion = 0.0;
            $r2 = 0.0;
        }

        $proyeccionMes12 = max(0.0, round(($pendiente * 12) + $intercepto, 2));

        if ($pendiente > 0.25) {
            $tendencia = 'Positiva';
        } elseif ($pendiente < -0.25) {
            $tendencia = 'Negativa';
        } else {
            $tendencia = 'Estable';
        }

        $curvaProyectada = [];
        for ($m = 1; $m <= 12; $m++) {
            $curvaProyectada[$m] = max(0.0, round(($pendiente * $m) + $intercepto, 2));
        }

        return [
            'pendiente' => round($pendiente, 4),
            'intercepto' => round($intercepto, 4),
            'r2' => round($r2, 4),
            'correlacion' => round($correlacion, 4),
            'tendencia' => $tendencia,
            'proyeccion_mes_12' => $proyeccionMes12,
            'curva_proyectada' => $curvaProyectada,
        ];
    }

    /**
     * Calcula la media móvil simple (SMA) y proyecta el cumplimiento a diciembre
     * considerando el momentum de cambio de los meses recientes.
     *
     * @param  array<int, float|int>  $puntos  Mes => Cumplimiento observado
     * @param  int  $ventana  Ventana móvil (por defecto 3 meses)
     * @return array{
     *     sma_reciente: float,
     *     velocidad_mensual: float,
     *     proyeccion_mes_12: float,
     *     ventana_usada: int,
     *     curva_proyectada: array<int, float>
     * }
     */
    public function calcularMediaMovil(array $puntos, int $ventana = 3): array
    {
        $n = count($puntos);

        if ($n === 0) {
            return [
                'sma_reciente' => 0.0,
                'velocidad_mensual' => 0.0,
                'proyeccion_mes_12' => 0.0,
                'ventana_usada' => $ventana,
                'curva_proyectada' => array_fill(1, 12, 0.0),
            ];
        }

        ksort($puntos);

        if ($n === 1) {
            $mesUnico = (int) array_key_first($puntos);
            $valorUnico = (float) $puntos[$mesUnico];

            return [
                'sma_reciente' => round($valorUnico, 2),
                'velocidad_mensual' => 0.0,
                'proyeccion_mes_12' => round($valorUnico, 2),
                'ventana_usada' => 1,
                'curva_proyectada' => array_fill(1, 12, round($valorUnico, 2)),
            ];
        }

        $ventanaUsada = min($ventana, $n);
        $subset = array_slice($puntos, -$ventanaUsada, $ventanaUsada, true);
        $smaReciente = array_sum($subset) / $ventanaUsada;

        $primerMesVentana = (int) array_key_first($subset);
        $ultimoMesVentana = (int) array_key_last($subset);
        $primerValor = (float) $subset[$primerMesVentana];
        $ultimoValor = (float) $subset[$ultimoMesVentana];

        $lapsoMeses = $ultimoMesVentana - $primerMesVentana;
        $velocidadMensual = $lapsoMeses > 0 ? (($ultimoValor - $primerValor) / $lapsoMeses) : 0.0;

        $mesesRestantes = max(0, 12 - $ultimoMesVentana);
        $proyeccionMes12 = max(0.0, round($ultimoValor + ($velocidadMensual * $mesesRestantes), 2));

        $curvaProyectada = [];
        for ($m = 1; $m <= 12; $m++) {
            if (isset($puntos[$m])) {
                $curvaProyectada[$m] = round((float) $puntos[$m], 2);
            } elseif ($m > $ultimoMesVentana) {
                $curvaProyectada[$m] = max(0.0, round($ultimoValor + ($velocidadMensual * ($m - $ultimoMesVentana)), 2));
            } else {
                $curvaProyectada[$m] = round($primerValor, 2);
            }
        }

        return [
            'sma_reciente' => round($smaReciente, 2),
            'velocidad_mensual' => round($velocidadMensual, 4),
            'proyeccion_mes_12' => $proyeccionMes12,
            'ventana_usada' => $ventanaUsada,
            'curva_proyectada' => $curvaProyectada,
        ];
    }

    /**
     * Realiza la proyección combinada anticipando el cumplimiento al mes 12.
     *
     * @param  array<int, float>  $puntosHistoricos  Mes => Cumplimiento observado
     * @param  int  $mesCorte  Mes actual de evaluación
     * @return array{
     *     indicador_id: int,
     *     codigo: string,
     *     nombre: string,
     *     tipo: string,
     *     target: float,
     *     ultimo_mes_registrado: int,
     *     cumplimiento_actual: float,
     *     regresion: array{
     *         pendiente: float,
     *         intercepto: float,
     *         r2: float,
     *         correlacion: float,
     *         tendencia: string,
     *         proyeccion_mes_12: float,
     *         curva_proyectada: array<int, float>
     *     },
     *     media_movil: array{
     *         sma_reciente: float,
     *         velocidad_mensual: float,
     *         proyeccion_mes_12: float,
     *         ventana_usada: int,
     *         curva_proyectada: array<int, float>
     *     },
     *     proyeccion_anual: float,
     *     brecha_proyectada: float,
     *     estado_anticipado: string,
     *     mes_logro_estimado: ?int,
     *     confianza_modelo: float,
     *     alerta_ejecutiva: string
     * }
     */
    public function proyectarIndicador(Indicador $indicador, array $puntosHistoricos, int $mesCorte): array
    {
        $regresion = $this->calcularRegresionLineal($puntosHistoricos);
        $mediaMovil = $this->calcularMediaMovil($puntosHistoricos, 3);

        $ultimoMes = ! empty($puntosHistoricos) ? (int) max(array_keys($puntosHistoricos)) : $mesCorte;
        $cumplimientoActual = ! empty($puntosHistoricos) ? (float) $puntosHistoricos[$ultimoMes] : 0.0;

        $target = (float) ($indicador->target_value ?? 100.0);
        if ($target <= 0.0) {
            $target = 100.0;
        }

        // Ponderación de modelos:
        // Si la regresión lineal tiene R² alto (>= 0.50), se le da mayor peso (60%).
        // Si el ajuste lineal es bajo, la media móvil reciente tiene mayor peso (60%).
        $n = count($puntosHistoricos);
        if ($n <= 1) {
            $proyeccionAnual = $cumplimientoActual;
            $confianzaModelo = 50.0;
        } elseif ($regresion['r2'] >= 0.50) {
            $proyeccionAnual = (0.60 * $regresion['proyeccion_mes_12']) + (0.40 * $mediaMovil['proyeccion_mes_12']);
            $confianzaModelo = round($regresion['r2'] * 100, 1);
        } else {
            $proyeccionAnual = (0.40 * $regresion['proyeccion_mes_12']) + (0.60 * $mediaMovil['proyeccion_mes_12']);
            $confianzaModelo = round(max(30.0, $regresion['r2'] * 100), 1);
        }

        $proyeccionAnual = max(0.0, round($proyeccionAnual, 1));
        $brechaProyectada = round($proyeccionAnual - $target, 1);

        // Determinación del estado anticipado a fin de año
        if ($proyeccionAnual >= $target) {
            $estadoAnticipado = 'Cumplirá';
        } elseif ($proyeccionAnual >= ($target * 0.85)) {
            $estadoAnticipado = 'En Riesgo';
        } else {
            $estadoAnticipado = 'Crítico';
        }

        // Estimación del mes de logro de la meta dentro del año (1..12)
        $mesLogroEstimado = null;
        if ($cumplimientoActual >= $target) {
            $mesLogroEstimado = $ultimoMes;
        } elseif ($regresion['pendiente'] > 0.0) {
            $mesCalculado = (int) ceil(($target - $regresion['intercepto']) / $regresion['pendiente']);
            if ($mesCalculado >= 1 && $mesCalculado <= 12) {
                $mesLogroEstimado = $mesCalculado;
            }
        }

        // Redacción de la alerta ejecutiva anticipatoria
        $alertaEjecutiva = $this->generarAlertaEjecutiva(
            $estadoAnticipado,
            $proyeccionAnual,
            $target,
            $brechaProyectada,
            $mesLogroEstimado
        );

        return [
            'indicador_id' => (int) $indicador->id,
            'codigo' => $indicador->codigo,
            'nombre' => $indicador->nombre,
            'tipo' => $indicador->tipo,
            'target' => $target,
            'ultimo_mes_registrado' => $ultimoMes,
            'cumplimiento_actual' => round($cumplimientoActual, 1),
            'regresion' => $regresion,
            'media_movil' => $mediaMovil,
            'proyeccion_anual' => $proyeccionAnual,
            'brecha_proyectada' => $brechaProyectada,
            'estado_anticipado' => $estadoAnticipado,
            'mes_logro_estimado' => $mesLogroEstimado,
            'confianza_modelo' => $confianzaModelo,
            'alerta_ejecutiva' => $alertaEjecutiva,
        ];
    }

    /**
     * Procesa todas las proyecciones para el Centro de Mando Ejecutivo.
     *
     * @return array{
     *     resumen: array{
     *         total_evaluados: int,
     *         cumpliran_count: int,
     *         en_riesgo_count: int,
     *         criticos_count: int,
     *         tasa_exito_proyectada: float,
     *         cumplimiento_promedio_proyectado: float
     *     },
     *     indicadores_proyectados: list<array<string, mixed>>,
     *     alertas_criticas: list<array<string, mixed>>,
     *     chart_anual: array{
     *         categories: list<string>,
     *         series_real: list<?float>,
     *         series_regresion: list<?float>,
     *         series_media_movil: list<?float>,
     *         series_target: list<float>
     *     }
     * }
     */
    public function obtenerProyeccionesDashboard(
        int $año,
        int $mesCorte,
        ?int $establecimientoId = null,
        ?string $tipo = null,
        ?string $busqueda = null,
        ?string $estado = null
    ): array {
        // Consulta histórica del año hasta el mes de corte seleccionado
        $query = IndicadorMedida::with(['indicador', 'establecimiento'])
            ->where('ano', $año)
            ->where('mes', '<=', $mesCorte);

        if ($establecimientoId !== null) {
            $query->where('establecimientos_id', $establecimientoId);
        }

        if ($tipo !== null && $tipo !== 'todos') {
            $query->whereHas('indicador', fn ($q) => $q->where('tipo', $tipo));
        }

        if ($busqueda !== null && trim($busqueda) !== '') {
            $qText = trim($busqueda);
            $query->whereHas('indicador', function ($q) use ($qText) {
                $q->where('codigo', 'like', "%{$qText}%")
                    ->orWhere('nombre', 'like', "%{$qText}%");
            });
        }

        if ($estado === 'criticos') {
            $indicadoresConEstado = IndicadorMedida::where('ano', $año)
                ->where('mes', $mesCorte)
                ->whereIn('status', ['Crítico', 'En Riesgo']);

            if ($establecimientoId !== null) {
                $indicadoresConEstado->where('establecimientos_id', $establecimientoId);
            }

            $indicadorIds = $indicadoresConEstado->pluck('indicadores_id')->unique();
            $query->whereIn('indicadores_id', $indicadorIds);
        } elseif ($estado !== null && $estado !== 'todos') {
            $indicadoresConEstado = IndicadorMedida::where('ano', $año)
                ->where('mes', $mesCorte)
                ->where('status', $estado);

            if ($establecimientoId !== null) {
                $indicadoresConEstado->where('establecimientos_id', $establecimientoId);
            }

            $indicadorIds = $indicadoresConEstado->pluck('indicadores_id')->unique();
            $query->whereIn('indicadores_id', $indicadorIds);
        }

        $todasMedidas = $query->get();

        // Agrupar mediciones por indicador
        $medidasPorIndicador = $todasMedidas->groupBy('indicadores_id');

        $proyecciones = [];

        foreach ($medidasPorIndicador as $indicadorId => $medidas) {
            $indicador = $medidas->first()?->indicador;
            if (! $indicador) {
                continue;
            }

            // Agrupar puntos por mes
            $puntosHistoricos = [];
            $medidasPorMes = $medidas->groupBy('mes');

            for ($m = 1; $m <= $mesCorte; $m++) {
                if ($medidasPorMes->has($m)) {
                    $grupoMes = $medidasPorMes->get($m);
                    $sumNum = (float) $grupoMes->sum('numerador_value');
                    $sumDen = (float) $grupoMes->sum('denominador_value');

                    if ($sumDen > 0.0) {
                        $pct = round(($sumNum / $sumDen) * 100, 2);
                    } else {
                        $pct = round((float) $grupoMes->avg('porcentaje_cumplimiento'), 2);
                    }

                    $puntosHistoricos[$m] = $pct;
                }
            }

            if (empty($puntosHistoricos)) {
                continue;
            }

            $proyecciones[] = $this->proyectarIndicador($indicador, $puntosHistoricos, $mesCorte);
        }

        $totalEvaluados = count($proyecciones);
        $cumpliranCount = count(array_filter($proyecciones, fn ($p) => $p['estado_anticipado'] === 'Cumplirá'));
        $enRiesgoCount = count(array_filter($proyecciones, fn ($p) => $p['estado_anticipado'] === 'En Riesgo'));
        $criticosCount = count(array_filter($proyecciones, fn ($p) => $p['estado_anticipado'] === 'Crítico'));

        $tasaExito = $totalEvaluados > 0 ? round(($cumpliranCount / $totalEvaluados) * 100, 1) : 0.0;
        $promedioProyectado = $totalEvaluados > 0
            ? round(array_sum(array_column($proyecciones, 'proyeccion_anual')) / $totalEvaluados, 1)
            : 0.0;

        // Ordenar con indicadores más críticos primero (mayor déficit a fin de año)
        usort($proyecciones, function ($a, $b) {
            $prioridad = ['Crítico' => 1, 'En Riesgo' => 2, 'Cumplirá' => 3];
            $cmpPrioridad = ($prioridad[$a['estado_anticipado']] ?? 4) <=> ($prioridad[$b['estado_anticipado']] ?? 4);
            if ($cmpPrioridad !== 0) {
                return $cmpPrioridad;
            }

            return $a['proyeccion_anual'] <=> $b['proyeccion_anual'];
        });

        // Alertas críticas inmediatas (los que no alcanzarán meta)
        $alertasCriticas = array_slice(
            array_filter($proyecciones, fn ($p) => in_array($p['estado_anticipado'], ['Crítico', 'En Riesgo'], true)),
            0,
            6
        );

        // Preparación de datos para el gráfico predictivo comunal (Ene..Dic)
        $chartAnual = $this->construirSerieChartAnual($proyecciones, $mesCorte);

        return [
            'resumen' => [
                'total_evaluados' => $totalEvaluados,
                'cumpliran_count' => $cumpliranCount,
                'en_riesgo_count' => $enRiesgoCount,
                'criticos_count' => $criticosCount,
                'tasa_exito_proyectada' => $tasaExito,
                'cumplimiento_promedio_proyectado' => $promedioProyectado,
            ],
            'indicadores_proyectados' => $proyecciones,
            'alertas_criticas' => array_values($alertasCriticas),
            'chart_anual' => $chartAnual,
        ];
    }

    /**
     * Construye las series de datos para el gráfico de proyección anual en ApexCharts.
     *
     * @param  list<array<string, mixed>>  $proyecciones
     * @return array{
     *     categories: list<string>,
     *     series_real: list<?float>,
     *     series_regresion: list<?float>,
     *     series_media_movil: list<?float>,
     *     series_target: list<float>
     * }
     */
    protected function construirSerieChartAnual(array $proyecciones, int $mesCorte): array
    {
        $categories = self::MESES_ABREV;
        $total = count($proyecciones);

        if ($total === 0) {
            return [
                'categories' => $categories,
                'series_real' => array_fill(0, 12, null),
                'series_regresion' => array_fill(0, 12, null),
                'series_media_movil' => array_fill(0, 12, null),
                'series_target' => array_fill(0, 12, 100.0),
            ];
        }

        $seriesReal = [];
        $seriesRegresion = [];
        $seriesMediaMovil = [];
        $seriesTarget = [];

        // Meta promedio de la cartera evaluada
        $targetPromedio = round(array_sum(array_column($proyecciones, 'target')) / $total, 1);

        for ($m = 1; $m <= 12; $m++) {
            $seriesTarget[] = $targetPromedio;

            if ($m <= $mesCorte) {
                // Promedio de valores reales observados en el mes m
                $valoresMes = [];
                foreach ($proyecciones as $p) {
                    if (isset($p['regresion']['curva_proyectada'][$m]) && $m <= $p['ultimo_mes_registrado']) {
                        $valoresMes[] = $p['regresion']['curva_proyectada'][$m];
                    }
                }
                $promReal = ! empty($valoresMes) ? round(array_sum($valoresMes) / count($valoresMes), 1) : null;
                $seriesReal[] = $promReal;

                // En los meses ya transcurridos, las series proyectadas se muestran en null excepto el punto de unión
                if ($m === $mesCorte) {
                    $seriesRegresion[] = $promReal;
                    $seriesMediaMovil[] = $promReal;
                } else {
                    $seriesRegresion[] = null;
                    $seriesMediaMovil[] = null;
                }
            } else {
                // Meses futuros: proyectados
                $seriesReal[] = null;

                $regValores = [];
                $maValores = [];
                foreach ($proyecciones as $p) {
                    if (isset($p['regresion']['curva_proyectada'][$m])) {
                        $regValores[] = $p['regresion']['curva_proyectada'][$m];
                    }
                    if (isset($p['media_movil']['curva_proyectada'][$m])) {
                        $maValores[] = $p['media_movil']['curva_proyectada'][$m];
                    }
                }

                $seriesRegresion[] = ! empty($regValores) ? round(array_sum($regValores) / count($regValores), 1) : null;
                $seriesMediaMovil[] = ! empty($maValores) ? round(array_sum($maValores) / count($maValores), 1) : null;
            }
        }

        return [
            'categories' => $categories,
            'series_real' => $seriesReal,
            'series_regresion' => $seriesRegresion,
            'series_media_movil' => $seriesMediaMovil,
            'series_target' => $seriesTarget,
        ];
    }

    /**
     * Genera un mensaje de alerta ejecutiva comprensible para equipos directivos.
     */
    protected function generarAlertaEjecutiva(
        string $estado,
        float $proyeccion,
        float $target,
        float $brecha,
        ?int $mesLogro
    ): string {
        $mesNombre = $mesLogro !== null ? (self::MESES_NOMBRES[$mesLogro] ?? "Mes {$mesLogro}") : null;

        if ($estado === 'Cumplirá') {
            if ($mesNombre) {
                return "Cumplimiento anticipado proyectado para {$mesNombre}. Estimación a cierre de {$proyeccion}% (meta {$target}%).";
            }

            return "Trayectoria favorable: se proyecta superar la meta del {$target}% alcanzando un {$proyeccion}% a diciembre.";
        }

        $deficit = abs($brecha);

        if ($estado === 'En Riesgo') {
            return "Alerta preventiva: se proyecta un {$proyeccion}% al cierre. Requiere acelerar un +{$deficit}% para alcanzar la meta anual.";
        }

        return "Alerta crítica: la tendencia actual anticipa cierre en {$proyeccion}%, con un déficit de -{$deficit}% respecto a la meta.";
    }
}
