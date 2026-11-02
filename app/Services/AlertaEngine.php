<?php

namespace App\Services;

use App\Models\Alerta;
use App\Models\AlertaRegla;
use App\Models\Establecimiento;
use App\Models\Indicador;
use App\Models\IndicadorMedida;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Motor de Alertas (Módulo 6).
 *
 * Evalúa las mediciones de `indicador_medidas` de un período contra el catálogo de reglas activas
 * (`alerta_reglas`) y deja los incidentes en `alertas`.
 *
 * Es idempotente: se puede ejecutar varias veces sobre el mismo período sin duplicar alertas,
 * conserva el estado y la observación de gestión de las ya detectadas, y retira las Pendientes
 * cuya condición dejó de cumplirse (por ejemplo, tras corregir un dato).
 *
 * Alcance: indicadores activos de tipo Meta Sanitaria / IAAPS y establecimientos activos CESFAM / CECOSF.
 */
class AlertaEngine
{
    /** Indicadores cuyo `porcentaje_cumplimiento` ya viene expresado como % de la meta (ver calculateCompliance). */
    public const INDICADORES_TASA = ['IAAPS-03', 'IAAPS-05', 'IAAPS-09.2'];

    /** Indicadores donde menos es mejor (umbral máximo tolerable). */
    public const INDICADORES_INVERSOS = ['IAAPS-04'];

    /** % de la meta desde el cual un indicador En Riesgo se considera "cercano" (REG-07). */
    public const UMBRAL_CERCANO = 95.0;

    /** Puntos porcentuales de caída mensual que disparan REG-06. */
    public const CAIDA_ABRUPTA = 20.0;

    /** Indicadores Críticos en un establecimiento que disparan REG-10. */
    public const CRITICOS_POR_ESTABLECIMIENTO = 3;

    /** Meses mínimos de historia para proyectar (REG-05). */
    public const MIN_PUNTOS_PROYECCION = 3;

    /** @var array<string, string> Código de regla => método que la evalúa. */
    private const EVALUADORES = [
        'REG-01' => 'reg01IncumplimientoCritico',
        'REG-02' => 'reg02MetaEnRiesgo',
        'REG-03' => 'reg03TendenciaDescendente',
        'REG-04' => 'reg04SinRegistro',
        'REG-05' => 'reg05ProyeccionEnRiesgo',
        'REG-06' => 'reg06CaidaAbrupta',
        'REG-07' => 'reg07CercanoAMeta',
        'REG-08' => 'reg08DenominadorCero',
        'REG-09' => 'reg09DatoInconsistente',
        'REG-10' => 'reg10AcumulacionCriticos',
    ];

    /** @var Collection<int, Indicador> */
    private Collection $indicadores;

    /** @var Collection<int, Establecimiento> */
    private Collection $establecimientos;

    /** @var Collection<string, IndicadorMedida> Mediciones del período, clave "indicador-establecimiento". */
    private Collection $medidas;

    /** @var array<string, array<int, float>> "indicador-establecimiento" => [mes => % de la meta] del año hasta el período. */
    private array $serie = [];

    private int $ano;

    private int $mes;

    public function __construct(private readonly ProyeccionEstadisticaService $proyeccion) {}

    /**
     * Evalúa el período y sincroniza la bandeja.
     *
     * @return array{detectadas: int, retiradas: int}
     */
    public function evaluar(int $ano, int $mes): array
    {
        $this->cargarDatos($ano, $mes);

        $detectadas = 0;
        $retiradas = 0;

        DB::transaction(function () use (&$detectadas, &$retiradas, $ano, $mes): void {
            foreach (AlertaRegla::activas()->orderBy('codigo')->get() as $regla) {
                $metodo = self::EVALUADORES[$regla->codigo] ?? null;

                if ($metodo === null) {
                    continue; // Regla creada a mano en BD sin evaluador implementado
                }

                $idsVigentes = [];

                foreach ($this->{$metodo}() as $hallazgo) {
                    $alerta = Alerta::updateOrCreate(
                        [
                            'alerta_regla_id' => $regla->id,
                            'indicadores_id' => $hallazgo['indicador_id'] ?? null,
                            'establecimientos_id' => $hallazgo['establecimiento_id'],
                            'ano' => $ano,
                            'mes' => $mes,
                        ],
                        // No se toca `estado` ni `observacion_gestion`: la gestión de la DAS se conserva.
                        [
                            'mensaje' => $hallazgo['mensaje'],
                            'brecha_detectada' => $hallazgo['brecha'] ?? null,
                        ],
                    );

                    $idsVigentes[] = $alerta->id;
                    $detectadas++;
                }

                // La condición ya no se cumple y nadie la gestionó: sale de la bandeja.
                $retiradas += Alerta::where('alerta_regla_id', $regla->id)
                    ->where('ano', $ano)
                    ->where('mes', $mes)
                    ->where('estado', 'Pendiente')
                    ->whereNotIn('id', $idsVigentes)
                    ->delete();
            }
        });

        return ['detectadas' => $detectadas, 'retiradas' => $retiradas];
    }

    /**
     * Avance expresado como % de la meta (100 = cumple), común a los tres tipos de cálculo del sistema.
     */
    public static function logro(string $codigoIndicador, float $porcentaje, float $meta): float
    {
        if (in_array($codigoIndicador, self::INDICADORES_TASA, true)) {
            return round($porcentaje, 2);
        }

        if (in_array($codigoIndicador, self::INDICADORES_INVERSOS, true)) {
            if ($porcentaje <= $meta || $porcentaje <= 0.0) {
                return 100.0;
            }

            return round(($meta / $porcentaje) * 100, 2);
        }

        return $meta > 0 ? round(($porcentaje / $meta) * 100, 2) : 0.0;
    }

    // ---------------------------------------------------------------------------------------------
    // Carga de datos
    // ---------------------------------------------------------------------------------------------

    private function cargarDatos(int $ano, int $mes): void
    {
        $this->ano = $ano;
        $this->mes = $mes;

        $this->indicadores = Indicador::where('is_active', true)
            ->whereIn('tipo', ['Meta Sanitaria', 'IAAPS'])
            ->get()
            ->keyBy('id');

        $this->establecimientos = Establecimiento::where('is_active', true)
            ->whereIn('tipo', ['CESFAM', 'CECOSF'])
            ->orderBy('nombre')
            ->get()
            ->keyBy('id');

        $delAno = IndicadorMedida::where('ano', $ano)
            ->where('mes', '<=', $mes)
            ->whereIn('indicadores_id', $this->indicadores->keys())
            ->whereIn('establecimientos_id', $this->establecimientos->keys())
            ->get();

        $this->medidas = $delAno->where('mes', $mes)
            ->keyBy(fn (IndicadorMedida $m): string => $this->clave($m->indicadores_id, $m->establecimientos_id));

        $this->serie = [];
        foreach ($delAno as $m) {
            $indicador = $this->indicadores[$m->indicadores_id];
            $this->serie[$this->clave($m->indicadores_id, $m->establecimientos_id)][(int) $m->mes] = self::logro(
                $indicador->codigo,
                (float) $m->porcentaje_cumplimiento,
                (float) $indicador->target_value,
            );
        }
    }

    private function clave(int $indicadorId, int $establecimientoId): string
    {
        return "{$indicadorId}-{$establecimientoId}";
    }

    /**
     * @return array{indicador_id: int, establecimiento_id: int, mensaje: string, brecha: float}
     */
    private function hallazgoDeMedida(IndicadorMedida $medida, string $detalle, ?float $brecha = null): array
    {
        $indicador = $this->indicadores[$medida->indicadores_id];
        $logro = self::logro($indicador->codigo, (float) $medida->porcentaje_cumplimiento, (float) $indicador->target_value);

        return [
            'indicador_id' => $medida->indicadores_id,
            'establecimiento_id' => $medida->establecimientos_id,
            'mensaje' => "{$this->nombreEstablecimiento($medida->establecimientos_id)} · {$indicador->codigo} {$indicador->nombre}: {$detalle}",
            'brecha' => round($brecha ?? max(0.0, 100.0 - $logro), 2),
        ];
    }

    private function nombreEstablecimiento(int $id): string
    {
        return $this->establecimientos[$id]->nombre ?? "Establecimiento #{$id}";
    }

    private function pct(float $valor): string
    {
        return number_format($valor, 1, ',', '.').' %';
    }

    /** @return iterable<IndicadorMedida> */
    private function medidasConEstado(string $estado): iterable
    {
        foreach ($this->medidas as $medida) {
            if ($medida->status === $estado) {
                yield $medida;
            }
        }
    }

    private function logroDe(IndicadorMedida $medida): float
    {
        $indicador = $this->indicadores[$medida->indicadores_id];

        return self::logro($indicador->codigo, (float) $medida->porcentaje_cumplimiento, (float) $indicador->target_value);
    }

    // ---------------------------------------------------------------------------------------------
    // Reglas
    // ---------------------------------------------------------------------------------------------

    /** REG-01 · Estado Crítico con datos válidos (denominador > 0; si es 0 aplica REG-08). */
    private function reg01IncumplimientoCritico(): iterable
    {
        foreach ($this->medidasConEstado('Crítico') as $m) {
            if ((float) $m->denominador_value <= 0.0) {
                continue;
            }

            $logro = $this->logroDe($m);
            yield $this->hallazgoDeMedida($m, "avance de {$this->pct($logro)} de la meta, por debajo del 85 % tolerable.");
        }
    }

    /** REG-02 · En Riesgo con avance entre 85 % y 95 % de la meta. */
    private function reg02MetaEnRiesgo(): iterable
    {
        foreach ($this->medidasConEstado('En Riesgo') as $m) {
            $logro = $this->logroDe($m);

            if ($logro < self::UMBRAL_CERCANO) {
                yield $this->hallazgoDeMedida($m, "en riesgo, avance de {$this->pct($logro)} de la meta.");
            }
        }
    }

    /** REG-03 · Avance estrictamente decreciente 3 meses seguidos y aún sin cumplir. */
    private function reg03TendenciaDescendente(): iterable
    {
        if ($this->mes < 3) {
            return;
        }

        foreach ($this->medidas as $clave => $m) {
            $s = $this->serie[$clave] ?? [];

            if (! isset($s[$this->mes - 2], $s[$this->mes - 1], $s[$this->mes])) {
                continue;
            }

            if ($m->status !== 'Cumpliendo' && $s[$this->mes - 2] > $s[$this->mes - 1] && $s[$this->mes - 1] > $s[$this->mes]) {
                yield $this->hallazgoDeMedida(
                    $m,
                    "tres meses a la baja ({$this->pct($s[$this->mes - 2])} → {$this->pct($s[$this->mes - 1])} → {$this->pct($s[$this->mes])} de la meta).",
                );
            }
        }
    }

    /** REG-04 · Establecimiento sin carga del mes, o indicador que se reportaba el mes anterior y dejó de reportarse. */
    private function reg04SinRegistro(): iterable
    {
        $conDatos = $this->medidas->pluck('establecimientos_id')->unique()->flip();

        foreach ($this->establecimientos as $est) {
            if (! $conDatos->has($est->id)) {
                yield [
                    'indicador_id' => null,
                    'establecimiento_id' => $est->id,
                    'mensaje' => "{$est->nombre}: no registra ninguna medición para el período.",
                    'brecha' => null,
                ];

                continue;
            }

            if ($this->mes < 2) {
                continue;
            }

            foreach ($this->indicadores as $indicador) {
                $clave = $this->clave($indicador->id, $est->id);

                if (isset($this->serie[$clave][$this->mes - 1]) && ! isset($this->serie[$clave][$this->mes])) {
                    yield [
                        'indicador_id' => $indicador->id,
                        'establecimiento_id' => $est->id,
                        'mensaje' => "{$est->nombre} · {$indicador->codigo} {$indicador->nombre}: se reportó el mes anterior y no tiene registro en el período.",
                        'brecha' => null,
                    ];
                }
            }
        }
    }

    /** REG-05 · Proyección a diciembre en estado Crítico (usa ProyeccionEstadisticaService, con la serie normalizada a % de la meta). */
    private function reg05ProyeccionEnRiesgo(): iterable
    {
        foreach ($this->medidas as $clave => $m) {
            $s = $this->serie[$clave] ?? [];

            if (count($s) < self::MIN_PUNTOS_PROYECCION) {
                continue;
            }

            ksort($s);

            // Serie ya expresada como % de la meta => se proyecta contra meta 100.
            $normalizado = $this->indicadores[$m->indicadores_id]->replicate();
            $normalizado->id = $m->indicadores_id;
            $normalizado->target_value = 100;

            $p = $this->proyeccion->proyectarIndicador($normalizado, $s, $this->mes);

            if ($p['estado_anticipado'] === 'Crítico') {
                yield $this->hallazgoDeMedida(
                    $m,
                    "la proyección a diciembre llega a {$this->pct($p['proyeccion_anual'])} de la meta (confianza del modelo {$this->pct($p['confianza_modelo'])}).",
                    max(0.0, -$p['brecha_proyectada']),
                );
            }
        }
    }

    /** REG-06 · Caída de 20 puntos o más frente al mes anterior. */
    private function reg06CaidaAbrupta(): iterable
    {
        if ($this->mes < 2) {
            return;
        }

        foreach ($this->medidas as $clave => $m) {
            $s = $this->serie[$clave] ?? [];

            if (! isset($s[$this->mes - 1], $s[$this->mes])) {
                continue;
            }

            $caida = $s[$this->mes - 1] - $s[$this->mes];

            if ($caida >= self::CAIDA_ABRUPTA) {
                yield $this->hallazgoDeMedida(
                    $m,
                    "cayó {$this->pct($caida)} frente al mes anterior ({$this->pct($s[$this->mes - 1])} → {$this->pct($s[$this->mes])} de la meta).",
                    $caida,
                );
            }
        }
    }

    /** REG-07 · En Riesgo pero a menos de 5 % de la meta. */
    private function reg07CercanoAMeta(): iterable
    {
        foreach ($this->medidasConEstado('En Riesgo') as $m) {
            $logro = $this->logroDe($m);

            if ($logro >= self::UMBRAL_CERCANO) {
                yield $this->hallazgoDeMedida($m, "avance de {$this->pct($logro)} de la meta: cerca de cumplir, requiere un refuerzo puntual.");
            }
        }
    }

    /** REG-08 · Denominador 0: el cumplimiento no es calculable. */
    private function reg08DenominadorCero(): iterable
    {
        foreach ($this->medidas as $m) {
            if ((float) $m->denominador_value <= 0.0) {
                yield $this->hallazgoDeMedida($m, 'denominador en cero o sin dato; no se puede calcular el cumplimiento. Revisar la carga REM.', 0.0);
            }
        }
    }

    /** REG-09 · Numerador > denominador en indicadores porcentuales, o valores negativos. */
    private function reg09DatoInconsistente(): iterable
    {
        foreach ($this->medidas as $m) {
            $codigo = $this->indicadores[$m->indicadores_id]->codigo;
            $num = (float) $m->numerador_value;
            $den = (float) $m->denominador_value;

            if ($num < 0 || $den < 0) {
                yield $this->hallazgoDeMedida($m, 'contiene valores negativos en numerador o denominador.', 0.0);

                continue;
            }

            $esPorcentual = ! in_array($codigo, [...self::INDICADORES_TASA, ...self::INDICADORES_INVERSOS], true)
                && (float) $this->indicadores[$m->indicadores_id]->target_value <= 100.0;

            if ($esPorcentual && $den > 0 && $num > $den) {
                yield $this->hallazgoDeMedida(
                    $m,
                    'numerador ('.number_format($num, 0, ',', '.').') mayor que el denominador ('.number_format($den, 0, ',', '.').').',
                    $num - $den,
                );
            }
        }
    }

    /** REG-10 · Establecimiento con 3 o más indicadores Críticos (con datos válidos) en el período. */
    private function reg10AcumulacionCriticos(): iterable
    {
        $criticos = $this->medidasConEstado('Crítico');
        $porEstablecimiento = [];

        foreach ($criticos as $m) {
            if ((float) $m->denominador_value > 0.0) {
                $porEstablecimiento[$m->establecimientos_id][] = $this->indicadores[$m->indicadores_id]->codigo;
            }
        }

        foreach ($porEstablecimiento as $establecimientoId => $codigos) {
            if (count($codigos) >= self::CRITICOS_POR_ESTABLECIMIENTO) {
                sort($codigos);

                yield [
                    'indicador_id' => null,
                    'establecimiento_id' => $establecimientoId,
                    'mensaje' => "{$this->nombreEstablecimiento($establecimientoId)}: ".count($codigos).' indicadores en estado Crítico ('.implode(', ', $codigos).').',
                    'brecha' => (float) count($codigos),
                ];
            }
        }
    }
}
