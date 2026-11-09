<?php

namespace App\Http\Controllers;

use App\Models\Establecimiento;
use App\Models\IndicadorMedida;
use App\Services\ProyeccionEstadisticaService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
        return view('centro-mando.index', $this->datosDashboard($request));
    }

    /**
     * Exporta el resumen, los indicadores y el detalle de predicciones a un archivo Excel (.xlsx).
     */
    public function exportarExcel(Request $request): BinaryFileResponse
    {
        $datos = $this->datosDashboard($request);
        $proyecciones = $datos['proyecciones'];
        $resumenProyeccion = $proyecciones['resumen'] ?? [];

        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setTitle('Centro de Mando Ejecutivo')
            ->setDescription('Reporte de cumplimiento y predicciones')
            ->setCreator(config('app.name'));

        $resumen = $spreadsheet->getActiveSheet();
        $resumen->setTitle('Resumen');
        $resumen->fromArray([
            ['Centro de Mando Ejecutivo'],
            ['Periodo', $datos['periodoSeleccionado']],
            ['Establecimiento', $datos['establecimientoNombre']],
            [],
            ['Indicador', 'Valor'],
            ['Total de mediciones', $datos['totalMediciones']],
            ['Cumpliendo', $datos['cumpliendoCount']],
            ['En Riesgo', $datos['enRiesgoCount']],
            ['Crítico', $datos['criticoCount']],
            ['Cumplimiento promedio (%)', $datos['cumplimientoPromedio']],
            ['Población beneficiaria', $datos['poblacionBeneficiaria']],
            [],
            ['Predicción a Diciembre (mes 12)'],
            ['Total evaluados', $resumenProyeccion['total_evaluados'] ?? 0],
            ['Cumplirán meta', $resumenProyeccion['cumpliran_count'] ?? 0],
            ['En riesgo al cierre', $resumenProyeccion['en_riesgo_count'] ?? 0],
            ['Críticos al cierre', $resumenProyeccion['criticos_count'] ?? 0],
            ['Tasa de éxito proyectada (%)', $resumenProyeccion['tasa_exito_proyectada'] ?? 0],
            ['Cumplimiento promedio proyectado (%)', $resumenProyeccion['cumplimiento_promedio_proyectado'] ?? 0],
        ]);

        $indicadores = $spreadsheet->createSheet();
        $indicadores->setTitle('Indicadores');
        $indicadores->fromArray([
            ['Código', 'Nombre', 'Tipo', 'Establecimiento', 'Numerador', 'Denominador', 'Cumplimiento (%)', 'Meta (%)', 'Estado', 'Brecha'],
        ]);

        foreach ($datos['medidas'] as $medida) {
            $indicadores->fromArray([
                $medida->indicador?->codigo ?? 'N/A',
                $medida->indicador?->nombre ?? 'Sin nombre',
                $medida->indicador?->tipo ?? 'Meta',
                $medida->establecimiento?->nombre ?? 'N/A',
                (float) $medida->numerador_value,
                (float) $medida->denominador_value,
                (float) $medida->porcentaje_cumplimiento,
                (float) ($medida->indicador?->target_value ?? 100),
                $medida->status,
                round((float) $medida->porcentaje_cumplimiento - (float) ($medida->indicador?->target_value ?? 100), 1),
            ]);
        }

        $predicciones = $spreadsheet->createSheet();
        $predicciones->setTitle('Predicciones');
        $predicciones->fromArray([
            ['Código', 'Nombre', 'Tipo', 'Cumplimiento actual (%)', 'Regresión lineal (%)', 'Media móvil (%)', 'Proyección anual (%)', 'Brecha proyectada', 'Estado anticipado', 'Mes logro estimado', 'Confianza modelo (%)', 'Alerta ejecutiva'],
        ]);

        foreach ($proyecciones['indicadores_proyectados'] ?? [] as $proyeccion) {
            $predicciones->fromArray([
                $proyeccion['codigo'] ?? 'N/A',
                $proyeccion['nombre'] ?? 'Sin nombre',
                $proyeccion['tipo'] ?? 'Meta',
                $proyeccion['cumplimiento_actual'] ?? null,
                $proyeccion['regresion']['proyeccion_mes_12'] ?? null,
                $proyeccion['media_movil']['proyeccion_mes_12'] ?? null,
                $proyeccion['proyeccion_anual'] ?? null,
                $proyeccion['brecha_proyectada'] ?? null,
                $proyeccion['estado_anticipado'] ?? null,
                $proyeccion['mes_logro_estimado'] ?? null,
                $proyeccion['confianza_modelo'] ?? null,
                $proyeccion['alerta_ejecutiva'] ?? null,
            ]);
        }

        foreach ($spreadsheet->getAllSheets() as $hoja) {
            foreach (range('A', $hoja->getHighestColumn()) as $columna) {
                $hoja->getColumnDimension($columna)->setAutoSize(true);
            }
        }

        $temporal = tempnam(sys_get_temp_dir(), 'centro-mando-');
        (new Xlsx($spreadsheet))->save($temporal);
        $spreadsheet->disconnectWorksheets();

        return response()->download(
            $temporal,
            'centro-mando-'.$datos['periodoSeleccionado'].'.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        )->deleteFileAfterSend(true);
    }

    /**
     * Vista imprimible (Guardar como PDF desde el navegador) con el detalle de predicciones.
     */
    public function exportarPdf(Request $request): View
    {
        return view('centro-mando.pdf', $this->datosDashboard($request));
    }

    /**
     * Reúne todos los datos del Centro de Mando para la vista y las exportaciones.
     *
     * @return array<string, mixed>
     */
    private function datosDashboard(Request $request): array
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

        return [
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
        ];
    }
}
