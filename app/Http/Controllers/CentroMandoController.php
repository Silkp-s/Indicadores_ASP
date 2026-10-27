<?php

namespace App\Http\Controllers;

use App\Models\Establecimiento;
use App\Models\IndicadorMedida;
use App\Services\ProyeccionEstadisticaService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CentroMandoController extends Controller
{
    public function __construct(
        protected ProyeccionEstadisticaService $proyeccionService
    ) {}

    /**
     * Muestra el Dashboard Ejecutivo comunal con gráficos, KPIs y los indicadores principales/críticos del momento.
     */
    public function index(Request $request): View
    {
        // 1. Obtener periodos disponibles en base de datos
        $mesesNombres = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        $periodosDisponibles = IndicadorMedida::select('ano', 'mes')
            ->distinct()
            ->orderByDesc('ano')
            ->orderByDesc('mes')
            ->get()
            ->map(function ($p) use ($mesesNombres) {
                $mesNombre = $mesesNombres[$p->mes] ?? 'Mes '.$p->mes;

                return [
                    'key' => sprintf('%04d-%02d', $p->ano, $p->mes),
                    'ano' => (int) $p->ano,
                    'mes' => (int) $p->mes,
                    'label' => "{$mesNombre} {$p->ano}",
                ];
            });

        // Determinar periodo activo (filtro o el más reciente)
        if ($request->filled('periodo') && str_contains($request->input('periodo'), '-')) {
            $partes = explode('-', $request->input('periodo'));
            $año = (int) $partes[0];
            $mes = (int) $partes[1];
            $periodoSeleccionado = $request->input('periodo');
        } elseif ($periodosDisponibles->isNotEmpty()) {
            $año = $periodosDisponibles->first()['ano'];
            $mes = $periodosDisponibles->first()['mes'];
            $periodoSeleccionado = $periodosDisponibles->first()['key'];
        } else {
            $año = (int) date('Y');
            $mes = (int) date('n');
            $periodoSeleccionado = sprintf('%04d-%02d', $año, $mes);
        }

        // 2. Establecimientos de la red
        $establecimientos = Establecimiento::where('is_active', true)
            ->orderBy('nombre')
            ->get();

        // 3. Consulta de mediciones del periodo
        $query = IndicadorMedida::with(['indicador', 'establecimiento'])
            ->where('ano', $año)
            ->where('mes', $mes);

        // Filtro por Establecimiento
        if ($request->filled('establecimiento_id') && $request->input('establecimiento_id') !== 'todos') {
            $query->where('establecimientos_id', $request->input('establecimiento_id'));
        }

        // Filtro por Tipo de Indicador
        if ($request->filled('tipo') && $request->input('tipo') !== 'todos') {
            $query->whereHas('indicador', fn ($q) => $q->where('tipo', $request->input('tipo')));
        }

        // Filtro por Estado
        if ($request->input('estado') === 'criticos') {
            $query->whereIn('status', ['Crítico', 'En Riesgo']);
        } elseif ($request->filled('estado') && $request->input('estado') !== 'todos') {
            $query->where('status', $request->input('estado'));
        }

        // Filtro de Búsqueda rápida por texto
        if ($request->filled('q')) {
            $qText = trim($request->input('q'));
            $query->whereHas('indicador', function ($q) use ($qText) {
                $q->where('codigo', 'like', "%{$qText}%")
                    ->orWhere('nombre', 'like', "%{$qText}%");
            });
        }

        $medidas = $query->get();

        // 4. Métricas Ejecutivas / KPIs
        $totalMediciones = $medidas->count();
        $cumpliendoCount = $medidas->where('status', 'Cumpliendo')->count();
        $enRiesgoCount = $medidas->where('status', 'En Riesgo')->count();
        $criticoCount = $medidas->where('status', 'Crítico')->count();
        $cumplimientoPromedio = $totalMediciones > 0
            ? round((float) $medidas->avg('porcentaje_cumplimiento'), 1)
            : 0.0;

        $metasCount = $medidas->filter(fn ($m) => $m->indicador?->tipo === 'Meta Sanitaria')->count();
        $iaapsCount = $medidas->filter(fn ($m) => $m->indicador?->tipo === 'IAAPS')->count();

        // Población beneficiaria calculada
        if ($request->filled('establecimiento_id') && $request->input('establecimiento_id') !== 'todos') {
            $estActivo = $establecimientos->firstWhere('id', (int) $request->input('establecimiento_id'));
            $poblacionBeneficiaria = $estActivo?->poblacion_inscrita ?? 0;
            $establecimientoNombre = $estActivo?->nombre ?? 'Establecimiento seleccionado';
        } else {
            $poblacionBeneficiaria = $establecimientos->sum('poblacion_inscrita');
            $establecimientoNombre = 'Red Comunal Consolidada';
        }

        // 5. Indicadores Críticos del Momento (Atención Prioritaria)
        // Se extraen todos los que están en Crítico o En Riesgo ordenados por mayor déficit
        $indicadoresCriticos = $medidas
            ->whereIn('status', ['Crítico', 'En Riesgo'])
            ->sortBy(fn ($m) => (float) $m->porcentaje_cumplimiento)
            ->values()
            ->map(function ($m) {
                $target = (float) ($m->indicador?->target_value ?? 100);
                $actual = (float) $m->porcentaje_cumplimiento;
                $brecha = round($actual - $target, 1);

                return [
                    'id' => $m->id,
                    'codigo' => $m->indicador?->codigo ?? 'N/A',
                    'nombre' => $m->indicador?->nombre ?? 'Sin nombre',
                    'tipo' => $m->indicador?->tipo ?? 'Meta',
                    'establecimiento' => $m->establecimiento?->nombre ?? 'N/A',
                    'numerador' => (float) $m->numerador_value,
                    'denominador' => (float) $m->denominador_value,
                    'porcentaje' => $actual,
                    'target' => $target,
                    'brecha' => $brecha,
                    'status' => $m->status,
                ];
            });

        // 6. Preparar datos para Gráficos ApexCharts
        // Gráfico 1: Barras comparativas de avance vs meta
        // Priorizar hasta 15 indicadores destacados (los más críticos seguidos de representativos)
        $graficoMedidas = $medidas
            ->sortBy(fn ($m) => (float) $m->porcentaje_cumplimiento)
            ->take(14);

        $chartBarras = [
            'categories' => $graficoMedidas->map(fn ($m) => $m->indicador?->codigo ?? 'IND')->values()->toArray(),
            'nombres' => $graficoMedidas->map(fn ($m) => $m->indicador?->nombre ?? '')->values()->toArray(),
            'cumplimiento' => $graficoMedidas->map(fn ($m) => (float) $m->porcentaje_cumplimiento)->values()->toArray(),
            'objetivos' => $graficoMedidas->map(function ($m) {
                // Si la meta está en tasa, para el gráfico de porcentaje se muestra 100% de cumplimiento objetivo
                $codigo = $m->indicador?->codigo ?? '';
                if (in_array($codigo, ['IAAPS-03', 'IAAPS-05', 'IAAPS-09.2'], true)) {
                    return 100.0;
                }

                return (float) ($m->indicador?->target_value ?? 100.0);
            })->values()->toArray(),
        ];

        // Gráfico 2: Donut distribución semáforo comunal
        $chartDonut = [
            'series' => [$cumpliendoCount, $enRiesgoCount, $criticoCount],
            'labels' => ['Cumpliendo', 'En Riesgo', 'Crítico'],
        ];

        // Gráfico 3: Comparativa de cumplimiento por Tipo de Indicador
        $promedioMetas = $metasCount > 0
            ? round((float) $medidas->filter(fn ($m) => $m->indicador?->tipo === 'Meta Sanitaria')->avg('porcentaje_cumplimiento'), 1)
            : 0.0;
        $promedioIaaps = $iaapsCount > 0
            ? round((float) $medidas->filter(fn ($m) => $m->indicador?->tipo === 'IAAPS')->avg('porcentaje_cumplimiento'), 1)
            : 0.0;

        $chartComparativa = [
            'series' => [$promedioMetas, $promedioIaaps],
            'labels' => ['Metas Sanitarias (Ley 19.813)', 'IAAPS 2025'],
        ];

        // 7. Motor de Proyección Estadística (Regresión Lineal Simple y Media Móvil)
        $estFiltro = ($request->filled('establecimiento_id') && $request->input('establecimiento_id') !== 'todos')
            ? (int) $request->input('establecimiento_id')
            : null;
        $tipoFiltro = ($request->filled('tipo') && $request->input('tipo') !== 'todos')
            ? $request->input('tipo')
            : null;
        $qFiltro = $request->filled('q') ? $request->input('q') : null;
        $estadoFiltro = $request->input('estado', 'todos');

        $proyecciones = $this->proyeccionService->obtenerProyeccionesDashboard(
            $año,
            $mes,
            $estFiltro,
            $tipoFiltro,
            $qFiltro,
            $estadoFiltro
        );

        $nombreMesActual = $mesesNombres[$mes] ?? "Mes {$mes}";

        return view('centro-mando.index', [
            'periodosDisponibles' => $periodosDisponibles,
            'periodoSeleccionado' => $periodoSeleccionado,
            'año' => $año,
            'mes' => $mes,
            'mesNombre' => $nombreMesActual,
            'establecimientos' => $establecimientos,
            'establecimientoNombre' => $establecimientoNombre,
            'medidas' => $medidas,
            'totalMediciones' => $totalMediciones,
            'cumpliendoCount' => $cumpliendoCount,
            'enRiesgoCount' => $enRiesgoCount,
            'criticoCount' => $criticoCount,
            'cumplimientoPromedio' => $cumplimientoPromedio,
            'metasCount' => $metasCount,
            'iaapsCount' => $iaapsCount,
            'poblacionBeneficiaria' => $poblacionBeneficiaria,
            'indicadoresCriticos' => $indicadoresCriticos,
            'chartBarras' => $chartBarras,
            'chartDonut' => $chartDonut,
            'chartComparativa' => $chartComparativa,
            'proyecciones' => $proyecciones,
            'filtros' => [
                'periodo' => $periodoSeleccionado,
                'establecimiento_id' => $request->input('establecimiento_id', 'todos'),
                'tipo' => $request->input('tipo', 'todos'),
                'estado' => $request->input('estado', 'todos'),
                'q' => $request->input('q', ''),
            ],
        ]);
    }
}
