<?php

namespace App\Http\Controllers;

use App\Models\Establecimiento;
use App\Models\Indicador;
use App\Models\IndicadorMedida;
use App\Services\RemReaderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ImportacionController extends Controller
{
    public function __construct(
        protected RemReaderService $remReader
    ) {}

    /**
     * Muestra la vista principal del módulo de importación masiva REM (Metas Sanitarias e IAAPS 2025).
     */
    public function index(): View
    {
        $establecimientos = Establecimiento::where('is_active', true)
            ->orderBy('nombre')
            ->get();

        $medidasRecientes = IndicadorMedida::with(['indicador', 'establecimiento'])
            ->latest()
            ->take(20)
            ->get();

        $stats = [
            'total_mediciones' => IndicadorMedida::count(),
            'total_metas' => IndicadorMedida::whereHas('indicador', fn ($q) => $q->where('tipo', 'Meta Sanitaria'))->count(),
            'total_iaaps' => IndicadorMedida::whereHas('indicador', fn ($q) => $q->where('tipo', 'IAAPS'))->count(),
            'cumpliendo' => IndicadorMedida::where('status', 'Cumpliendo')->count(),
            'en_riesgo' => IndicadorMedida::where('status', 'En Riesgo')->count(),
            'critico' => IndicadorMedida::where('status', 'Crítico')->count(),
        ];

        return view('importacion.index', [
            'establecimientos' => $establecimientos,
            'medidasRecientes' => $medidasRecientes,
            'stats' => $stats,
        ]);
    }

    /**
     * Procesa la planilla REM (.xlsm / .xlsx), detecta metadatos y extrae numeradores
     * tanto de Metas Sanitarias (Ley 19.813) como de Indicadores IAAPS 2025.
     */
    public function previsualizar(Request $request): JsonResponse
    {
        $request->validate([
            'archivo' => ['required', 'file', 'max:35840'], // hasta 35MB
            'establecimiento_id' => ['nullable', 'exists:establecimientos,id'],
            'tipo_indicadores' => ['nullable', 'string', 'in:todas,metas,iaaps'],
        ]);

        $file = $request->file('archivo');
        $originalName = $file->getClientOriginalName();
        $filtroTipo = $request->input('tipo_indicadores', 'todas');
        $tempToken = (string) Str::uuid();

        // Guardar archivo temporalmente para su posterior confirmación
        $tempPath = $file->storeAs('temp_rem', "{$tempToken}.".$file->getClientOriginalExtension());
        $fullPath = storage_path("app/private/{$tempPath}");

        if (! file_exists($fullPath)) {
            $fullPath = storage_path("app/{$tempPath}");
        }

        try {
            $spreadsheet = $this->remReader->loadRelevantSpreadsheet($fullPath);
            $metadata = $this->remReader->detectMetadata($spreadsheet, $originalName);

            // 1. Vinculación con Establecimiento
            $establecimiento = null;
            if ($request->filled('establecimiento_id')) {
                $establecimiento = Establecimiento::find($request->input('establecimiento_id'));
            } else {
                $establecimiento = $this->remReader->matchEstablecimiento(
                    $metadata['codigo_deis'],
                    $metadata['nombre_establecimiento']
                );
            }

            // Excepción SweetAlert: Establecimiento no reconocido en BD
            if ($establecimiento === null) {
                Cache::put("rem_pending_{$tempToken}", [
                    'file_path' => $fullPath,
                    'filename' => $originalName,
                    'metadata' => $metadata,
                    'filtro_tipo' => $filtroTipo,
                ], now()->addHours(2));

                $todosEstablecimientos = Establecimiento::select('id', 'nombre', 'codigo_deis', 'poblacion_inscrita')
                    ->where('is_active', true)
                    ->orderBy('nombre')
                    ->get();

                return response()->json([
                    'status' => 'establishment_not_found',
                    'temp_token' => $tempToken,
                    'detected' => [
                        'codigo_deis' => $metadata['codigo_deis'] ?? 'No detectado',
                        'nombre' => $metadata['nombre_establecimiento'] ?? 'No especificado',
                        'mes' => $metadata['mes'],
                        'mes_nombre' => $metadata['mes_nombre'] ?? 'No especificado',
                        'año' => $metadata['año'],
                        'serie' => $metadata['serie'] ?? 'A',
                    ],
                    'establecimientos_disponibles' => $todosEstablecimientos,
                    'message' => 'El establecimiento detectado en la planilla no coincide exactamente con ninguno registrado en la base de datos.',
                ]);
            }

            // 2. Extracción de numeradores y denominadores de la matriz REM
            $extracted = $this->remReader->extractNumeradores($spreadsheet);

            // Asignar denominador automático de población inscrita si aplica (IAAPS-03 Tasa Morbilidad)
            if (isset($extracted['IAAPS-03']) && $establecimiento->poblacion_inscrita > 0) {
                $extracted['IAAPS-03']['denominador'] = (float) $establecimiento->poblacion_inscrita;
            }

            // 3. Determinar denominadores o insumos pendientes (FONASA / ENS / IAAPS)
            $pendingInputs = $this->remReader->determinePendingInputs(
                $extracted,
                $establecimiento,
                $metadata['año'],
                $filtroTipo
            );

            // Almacenar extracción en caché
            Cache::put("rem_pending_{$tempToken}", [
                'file_path' => $fullPath,
                'filename' => $originalName,
                'metadata' => $metadata,
                'establecimiento_id' => $establecimiento->id,
                'extracted' => $extracted,
                'filtro_tipo' => $filtroTipo,
            ], now()->addHours(2));

            $periodo = [
                'año' => $metadata['año'],
                'mes' => $metadata['mes'] ?? (int) date('n'),
                'mes_nombre' => $metadata['mes_nombre'] ?? 'Periodo actual',
                'serie' => $metadata['serie'] ?? 'A',
            ];

            if (! empty($pendingInputs)) {
                return response()->json([
                    'status' => 'requires_input',
                    'temp_token' => $tempToken,
                    'establecimiento' => [
                        'id' => $establecimiento->id,
                        'nombre' => $establecimiento->nombre,
                        'codigo_deis' => $establecimiento->codigo_deis,
                        'poblacion_inscrita' => $establecimiento->poblacion_inscrita,
                    ],
                    'periodo' => $periodo,
                    'extracted_metas' => $extracted,
                    'pending_inputs' => $pendingInputs,
                ]);
            }

            return response()->json([
                'status' => 'ready',
                'temp_token' => $tempToken,
                'establecimiento' => [
                    'id' => $establecimiento->id,
                    'nombre' => $establecimiento->nombre,
                    'codigo_deis' => $establecimiento->codigo_deis,
                    'poblacion_inscrita' => $establecimiento->poblacion_inscrita,
                ],
                'periodo' => $periodo,
                'extracted_metas' => $extracted,
                'pending_inputs' => [],
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al previsualizar archivo REM: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json([
                'status' => 'error',
                'message' => 'Ocurrió un error al procesar la planilla: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Recibe los valores complementarios ingresados vía SweetAlert2, efectúa los cálculos
     * finales de cumplimiento y persiste las mediciones tanto de Metas Sanitarias como de IAAPS 2025.
     */
    public function guardar(Request $request): JsonResponse
    {
        $request->validate([
            'temp_token' => ['required', 'string'],
            'establecimiento_id' => ['required', 'exists:establecimientos,id'],
            'año' => ['required', 'integer'],
            'mes' => ['required', 'integer', 'between:1,12'],
            'inputs' => ['nullable', 'array'],
        ]);

        $tempToken = $request->input('temp_token');
        $sessionData = Cache::get("rem_pending_{$tempToken}");

        $establecimientoId = (int) $request->input('establecimiento_id');
        $establecimiento = Establecimiento::findOrFail($establecimientoId);
        $año = (int) $request->input('año');
        $mes = (int) $request->input('mes');
        $userInputs = $request->input('inputs', []);

        $extracted = $sessionData['extracted'] ?? [];
        $pobTotal = $establecimiento->poblacion_inscrita ?? 0;

        // Catálogo de Indicadores activos (Metas Sanitarias e IAAPS)
        $indicadores = Indicador::whereIn('tipo', ['Meta Sanitaria', 'IAAPS'])
            ->where('is_active', true)
            ->get();

        $savedMedidas = [];

        foreach ($indicadores as $indicador) {
            $codigo = $indicador->codigo;
            $metaExtracted = $extracted[$codigo] ?? null;

            $numerador = 0.0;
            $denominador = 0.0;
            $tipoCalculo = 'Porcentaje';

            // ==========================================
            // A. METAS SANITARIAS LEY 19.813
            // ==========================================
            switch ($codigo) {
                case 'MS-01':
                    $numerador = (float) ($metaExtracted['numerador'] ?? ($userInputs['numerador_ms_01'] ?? 0));
                    $denominador = (float) ($metaExtracted['denominador'] ?? ($userInputs['dsm_denominador_riesgo'] ?? 0));
                    break;

                case 'MS-02':
                    $numerador = (float) ($metaExtracted['numerador'] ?? ($userInputs['numerador_pap_vigente'] ?? 0));
                    $denominador = (float) ($userInputs['poblacion_fonasa_25_64'] ?? 0);
                    break;

                case 'MS-03':
                    $numerador = (float) ($metaExtracted['numerador'] ?? ($userInputs['numerador_ms_03'] ?? 0));
                    $denominador = (float) ($userInputs['poblacion_fonasa_0_9'] ?? 0);
                    break;

                case 'MS-04':
                    $numerador = (float) ($metaExtracted['numerador'] ?? ($userInputs['numerador_dm2_compensada'] ?? 0));
                    $denominador = (float) ($userInputs['poblacion_estimada_dm2'] ?? 0);
                    break;

                case 'MS-05':
                    $numerador = (float) ($metaExtracted['numerador'] ?? ($userInputs['numerador_hta_compensada'] ?? 0));
                    $denominador = (float) ($userInputs['poblacion_estimada_hta'] ?? 0);
                    break;

                case 'MS-06':
                    $numerador = (float) ($metaExtracted['numerador'] ?? ($userInputs['numerador_lme_6m'] ?? 0));
                    $denominador = (float) ($userInputs['denominador_control_6m'] ?? 0);
                    break;

                case 'MS-07':
                    $numerador = (float) ($metaExtracted['numerador'] ?? ($userInputs['numerador_asma_epoc'] ?? 0));
                    $denominador = (float) ($userInputs['poblacion_estimada_asma_epoc'] ?? 0);
                    break;

                case 'MS-08':
                    $numerador = (float) ($userInputs['actividades_comunitarias_ejec'] ?? 1);
                    $denominador = (float) ($userInputs['actividades_comunitarias_prog'] ?? 1);
                    break;

                    // ==========================================
                    // B. INDICADORES IAAPS 2025
                    // ==========================================
                case 'IAAPS-01':
                    $numerador = (float) ($userInputs['iaaps_01_num'] ?? 1);
                    $denominador = (float) ($userInputs['iaaps_01_den'] ?? 1);
                    break;

                case 'IAAPS-02.1':
                    $numerador = (float) ($userInputs['iaaps_02_1_num'] ?? 1);
                    $denominador = (float) ($userInputs['iaaps_02_1_den'] ?? 1);
                    break;

                case 'IAAPS-02.2':
                    $numerador = (float) ($userInputs['iaaps_02_2_num'] ?? 20);
                    $denominador = (float) ($userInputs['iaaps_02_2_den'] ?? 20);
                    break;

                case 'IAAPS-03':
                    $numerador = (float) ($metaExtracted['numerador'] ?? ($userInputs['iaaps_03_num'] ?? 0));
                    $denominador = (float) ($pobTotal > 0 ? $pobTotal : ($userInputs['iaaps_03_den'] ?? 1));
                    $tipoCalculo = 'Tasa';
                    break;

                case 'IAAPS-04':
                    $numerador = (float) ($userInputs['iaaps_04_sic'] ?? 0);
                    $denominador = (float) ($metaExtracted['denominador'] ?? ($userInputs['iaaps_04_total_consultas'] ?? 1296));
                    $tipoCalculo = 'Inverso';
                    break;

                case 'IAAPS-05':
                    $numerador = (float) ($userInputs['iaaps_05_vdi'] ?? 0);
                    $denominador = (float) ($pobTotal > 0 ? round($pobTotal / 3.3) : ($userInputs['iaaps_05_familias'] ?? 1000));
                    $tipoCalculo = 'Tasa';
                    break;

                case 'IAAPS-06.1A':
                    $numerador = (float) ($metaExtracted['numerador'] ?? ($userInputs['iaaps_06_1a_num'] ?? 0));
                    $denominador = (float) ($userInputs['iaaps_06_1a_den'] ?? round($pobTotal * 0.31));
                    break;

                case 'IAAPS-06.1B':
                    $numerador = (float) ($metaExtracted['numerador'] ?? ($userInputs['iaaps_06_1b_num'] ?? 0));
                    $denominador = (float) ($userInputs['iaaps_06_1b_den'] ?? round($pobTotal * 0.29));
                    break;

                case 'IAAPS-06.2':
                    $numerador = (float) ($userInputs['iaaps_06_2_num'] ?? 0);
                    $denominador = (float) ($userInputs['iaaps_06_2_den'] ?? round($pobTotal * 0.14));
                    break;

                case 'IAAPS-07':
                    $numerador = (float) ($metaExtracted['numerador'] ?? ($userInputs['iaaps_07_num'] ?? 0));
                    $denominador = (float) ($userInputs['iaaps_07_den'] ?? round($pobTotal * 0.02));
                    break;

                case 'IAAPS-08':
                    $numerador = (float) ($userInputs['iaaps_08_num'] ?? 0);
                    $denominador = (float) ($userInputs['iaaps_08_den'] ?? round($pobTotal * 0.13));
                    break;

                case 'IAAPS-09.1':
                    $numerador = (float) ($userInputs['iaaps_09_1_num'] ?? 0);
                    $denominador = (float) ($userInputs['iaaps_09_1_den'] ?? round($pobTotal * 0.05));
                    break;

                case 'IAAPS-09.2':
                    $numerador = (float) ($userInputs['iaaps_09_2_num'] ?? 0);
                    $denominador = (float) ($userInputs['iaaps_09_2_den'] ?? 100);
                    $tipoCalculo = 'Tasa';
                    break;

                case 'IAAPS-09.3':
                    $numerador = (float) ($userInputs['iaaps_09_3_num'] ?? 0);
                    $denominador = (float) ($userInputs['iaaps_09_3_den'] ?? 100);
                    break;

                case 'IAAPS-10':
                    $numerador = (float) ($userInputs['iaaps_10_num'] ?? 95);
                    $denominador = (float) ($userInputs['iaaps_10_den'] ?? 100);
                    break;

                case 'IAAPS-11':
                    $numerador = (float) ($userInputs['iaaps_11_num'] ?? 0);
                    $denominador = (float) ($userInputs['iaaps_11_den'] ?? round($pobTotal * 0.35));
                    break;

                case 'IAAPS-12':
                    $numerador = (float) ($metaExtracted['numerador'] ?? ($userInputs['iaaps_12_num'] ?? 0));
                    $denominador = (float) ($metaExtracted['denominador'] ?? ($userInputs['iaaps_12_den'] ?? 0));
                    break;

                case 'IAAPS-13':
                    $numerador = (float) ($userInputs['iaaps_13_num'] ?? 0);
                    $denominador = (float) ($userInputs['iaaps_13_den'] ?? round($pobTotal * 0.07));
                    break;

                case 'IAAPS-14':
                    $numerador = (float) ($metaExtracted['numerador'] ?? ($userInputs['iaaps_14_num'] ?? 0));
                    $denominador = (float) ($userInputs['iaaps_14_den'] ?? round($pobTotal * 0.088));
                    break;

                case 'IAAPS-15':
                    $numerador = (float) ($metaExtracted['numerador'] ?? ($userInputs['iaaps_15_num'] ?? 0));
                    $denominador = (float) ($userInputs['iaaps_15_den'] ?? round($pobTotal * 0.215));
                    break;

                case 'IAAPS-16':
                    $numerador = (float) ($userInputs['iaaps_16_num'] ?? 0);
                    $denominador = (float) ($userInputs['iaaps_16_den'] ?? round($pobTotal * 0.035));
                    break;

                case 'IAAPS-17':
                    $numerador = (float) ($metaExtracted['numerador'] ?? ($userInputs['iaaps_17_num'] ?? 0));
                    $denominador = (float) ($metaExtracted['denominador'] ?? ($userInputs['iaaps_17_den'] ?? 0));
                    break;

                default:
                    if ($metaExtracted !== null) {
                        $numerador = (float) ($metaExtracted['numerador'] ?? 0);
                        $denominador = (float) ($metaExtracted['denominador'] ?? 0);
                    }
                    break;
            }

            // Si ambos son 0 y no hubo extracción ni input del usuario para este indicador, omitir
            if ($numerador === 0.0 && $denominador === 0.0 && $metaExtracted === null && ! isset($userInputs["numerador_{$codigo}"])) {
                continue;
            }

            $compliance = $this->remReader->calculateCompliance(
                $numerador,
                $denominador,
                (float) $indicador->target_value,
                $tipoCalculo
            );

            $medida = IndicadorMedida::updateOrCreate(
                [
                    'indicadores_id' => $indicador->id,
                    'establecimientos_id' => $establecimiento->id,
                    'ano' => $año,
                    'mes' => $mes,
                ],
                [
                    'numerador_value' => $numerador,
                    'denominador_value' => $denominador,
                    'porcentaje_cumplimiento' => $compliance['porcentaje'],
                    'status' => $compliance['estado'],
                ]
            );

            $savedMedidas[] = [
                'id' => $medida->id,
                'codigo' => $indicador->codigo,
                'nombre' => $indicador->nombre,
                'tipo' => $indicador->tipo,
                'numerador' => $numerador,
                'denominador' => $denominador,
                'porcentaje' => $compliance['porcentaje'],
                'objetivo' => ($tipoCalculo === 'Tasa') ? 100.0 : (float) $indicador->target_value,
                'target_original' => (float) $indicador->target_value,
                'tipo_calculo' => $tipoCalculo,
                'estado' => $compliance['estado'],
            ];
        }

        // Limpiar caché
        Cache::forget("rem_pending_{$tempToken}");

        $totales = [
            'cumpliendo' => collect($savedMedidas)->where('estado', 'Cumpliendo')->count(),
            'en_riesgo' => collect($savedMedidas)->where('estado', 'En Riesgo')->count(),
            'critico' => collect($savedMedidas)->where('estado', 'Crítico')->count(),
            'total' => count($savedMedidas),
            'metas_count' => collect($savedMedidas)->where('tipo', 'Meta Sanitaria')->count(),
            'iaaps_count' => collect($savedMedidas)->where('tipo', 'IAAPS')->count(),
        ];

        // Preparar series para ApexCharts
        $chartData = [
            'categories' => collect($savedMedidas)->pluck('codigo')->toArray(),
            'cumplimiento' => collect($savedMedidas)->pluck('porcentaje')->toArray(),
            'objetivos' => collect($savedMedidas)->pluck('objetivo')->toArray(),
            'tipos' => collect($savedMedidas)->pluck('tipo')->toArray(),
        ];

        return response()->json([
            'status' => 'success',
            'message' => 'Importación REM y cálculo de indicadores (Metas e IAAPS) completados con éxito.',
            'establecimiento' => [
                'id' => $establecimiento->id,
                'nombre' => $establecimiento->nombre,
                'poblacion_inscrita' => $establecimiento->poblacion_inscrita,
            ],
            'periodo' => [
                'año' => $año,
                'mes' => $mes,
            ],
            'totales' => $totales,
            'medidas' => $savedMedidas,
            'chart' => $chartData,
        ]);
    }
}
