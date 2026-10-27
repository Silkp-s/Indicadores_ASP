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
use Illuminate\Support\Facades\Storage;
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
     * Procesa una o múltiples planillas REM (.xlsm / .xlsx), detecta metadatos y extrae numeradores
     * tanto de Metas Sanitarias (Ley 19.813) como de Indicadores IAAPS 2025.
     */
    public function previsualizar(Request $request): JsonResponse
    {
        $request->validate([
            'archivo' => ['nullable', 'file', 'max:35840', 'extensions:xlsx,xlsm'], // hasta 35MB
            'archivos' => ['nullable', 'array', 'max:20'],
            'archivos.*' => ['file', 'max:35840', 'extensions:xlsx,xlsm'],
            'establecimiento_id' => ['nullable', 'exists:establecimientos,id'],
            'tipo_indicadores' => ['nullable', 'string', 'in:todas,metas,iaaps'],
        ], [
            'archivo.extensions' => 'Solo se permiten planillas REM en formato .xlsx o .xlsm.',
            'archivo.max' => 'Cada planilla no puede superar los 35 MB.',
            'archivos.max' => 'No es posible procesar más de 20 planillas en una misma importación.',
            'archivos.*.extensions' => 'Solo se permiten planillas REM en formato .xlsx o .xlsm.',
            'archivos.*.max' => 'Cada planilla no puede superar los 35 MB.',
        ]);

        // Las planillas se guardan solo mientras dura el procesamiento y se eliminan en el bloque finally.
        $storedPaths = [];

        $uploadedFiles = [];
        if ($request->hasFile('archivos')) {
            $uploadedFiles = $request->file('archivos');
        } elseif ($request->hasFile('archivo')) {
            $uploadedFiles = [$request->file('archivo')];
        }

        if (empty($uploadedFiles)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Debe adjuntar al menos una planilla REM (.xlsm / .xlsx) para procesar.',
            ], 422);
        }

        $filtroTipo = $request->input('tipo_indicadores', 'todas');
        $overrideEstablecimientoId = $request->input('establecimiento_id');

        $this->limpiarTemporalesAntiguos();

        try {
            $groups = [];
            $unmatchedFiles = [];

            foreach ($uploadedFiles as $file) {
                $originalName = $file->getClientOriginalName();
                $fileToken = (string) Str::uuid();

                // La extensión se normaliza contra una lista blanca: nunca se usa tal cual la envía el cliente.
                $extension = strtolower((string) $file->getClientOriginalExtension());
                if (! in_array($extension, ['xlsx', 'xlsm'], true)) {
                    $extension = 'xlsx';
                }

                $tempPath = $file->storeAs('temp_rem', "{$fileToken}.{$extension}");

                if ($tempPath === false) {
                    throw new \RuntimeException('No fue posible almacenar temporalmente la planilla.');
                }

                $storedPaths[] = $tempPath;

                $fullPath = storage_path("app/private/{$tempPath}");

                if (! file_exists($fullPath)) {
                    $fullPath = storage_path("app/{$tempPath}");
                }

                $spreadsheet = $this->remReader->loadRelevantSpreadsheet($fullPath);
                $metadata = $this->remReader->detectMetadata($spreadsheet, $originalName);

                // 1. Vinculación con Establecimiento
                $establecimiento = null;
                if ($overrideEstablecimientoId) {
                    $establecimiento = Establecimiento::find($overrideEstablecimientoId);
                } else {
                    $establecimiento = $this->remReader->matchEstablecimiento(
                        $metadata['codigo_deis'],
                        $metadata['nombre_establecimiento']
                    );
                }

                // 2. Extracción de numeradores y denominadores de la planilla
                $extracted = $establecimiento !== null
                    ? $this->remReader->extractNumeradores($spreadsheet)
                    : [];

                // Se libera la planilla para no acumular memoria al procesar varias en un mismo lote.
                $this->remReader->releaseSpreadsheet($spreadsheet);

                if ($establecimiento === null) {
                    $unmatchedFiles[] = [
                        'file_path' => $fullPath,
                        'filename' => $originalName,
                        'metadata' => $metadata,
                        'file_token' => $fileToken,
                    ];

                    continue;
                }

                $año = (int) ($metadata['año'] ?? date('Y'));
                $mes = (int) ($metadata['mes'] ?? date('n'));
                $groupKey = "{$establecimiento->id}_{$año}_{$mes}";

                if (! isset($groups[$groupKey])) {
                    $groups[$groupKey] = [
                        'establecimiento' => $establecimiento,
                        'año' => $año,
                        'mes' => $mes,
                        'mes_nombre' => $metadata['mes_nombre'] ?? 'Periodo '.$mes,
                        'series' => [],
                        'archivos_procesados' => [],
                        'extracted' => [],
                    ];
                }

                if (! empty($metadata['serie'])) {
                    $groups[$groupKey]['series'][] = $metadata['serie'];
                }

                $groups[$groupKey]['archivos_procesados'][] = [
                    'nombre' => $originalName,
                    'tamano' => $file->getSize(),
                    'serie' => $metadata['serie'] ?? 'N/A',
                    'indicadores_extraidos' => count($extracted),
                ];

                // Consolidar indicadores extraídos (sin perder datos de planillas complementarias como Serie A + Serie P)
                foreach ($extracted as $cod => $info) {
                    if (! isset($groups[$groupKey]['extracted'][$cod])) {
                        $groups[$groupKey]['extracted'][$cod] = $info;
                    } else {
                        $currNum = (float) ($groups[$groupKey]['extracted'][$cod]['numerador'] ?? 0);
                        $newNum = (float) ($info['numerador'] ?? 0);
                        if ($newNum > 0 && $currNum == 0) {
                            $groups[$groupKey]['extracted'][$cod] = $info;
                        } elseif ($newNum > 0 && $currNum > 0) {
                            $groups[$groupKey]['extracted'][$cod] = array_merge($groups[$groupKey]['extracted'][$cod], $info);
                        }
                    }
                }
            }

            // Si hay archivos no reconocidos y no hay grupos válidos:
            if (empty($groups) && ! empty($unmatchedFiles)) {
                $firstUnmatched = $unmatchedFiles[0];
                $tempToken = $firstUnmatched['file_token'];

                Cache::put("rem_pending_{$tempToken}", [
                    'user_id' => $request->user()->id,
                    'file_path' => $firstUnmatched['file_path'],
                    'filename' => $firstUnmatched['filename'],
                    'metadata' => $firstUnmatched['metadata'],
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
                        'codigo_deis' => $firstUnmatched['metadata']['codigo_deis'] ?? 'No detectado',
                        'nombre' => $firstUnmatched['metadata']['nombre_establecimiento'] ?? 'No especificado',
                        'mes' => $firstUnmatched['metadata']['mes'],
                        'mes_nombre' => $firstUnmatched['metadata']['mes_nombre'] ?? 'No especificado',
                        'año' => $firstUnmatched['metadata']['año'],
                        'serie' => $firstUnmatched['metadata']['serie'] ?? 'A',
                    ],
                    'establecimientos_disponibles' => $todosEstablecimientos,
                    'message' => 'El establecimiento detectado en la planilla no coincide exactamente con ninguno registrado en la base de datos.',
                ]);
            }

            if (empty($groups)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No fue posible identificar ningún establecimiento en las planillas subidas.',
                ], 422);
            }

            // Procesar cada grupo: asignar denominador de población y determinar inputs pendientes
            foreach ($groups as $groupKey => &$group) {
                $est = $group['establecimiento'];

                // Asignar denominador automático de población inscrita si aplica (IAAPS-03 Tasa Morbilidad)
                if (isset($group['extracted']['IAAPS-03']) && $est->poblacion_inscrita > 0) {
                    $group['extracted']['IAAPS-03']['denominador'] = (float) $est->poblacion_inscrita;
                }

                $pendingInputs = $this->remReader->determinePendingInputs(
                    $group['extracted'],
                    $est,
                    $group['año'],
                    $filtroTipo
                );

                $tempToken = (string) Str::uuid();

                Cache::put("rem_pending_{$tempToken}", [
                    'user_id' => $request->user()->id,
                    'archivos' => $group['archivos_procesados'],
                    'metadata' => [
                        'año' => $group['año'],
                        'mes' => $group['mes'],
                        'mes_nombre' => $group['mes_nombre'],
                        'serie' => implode(' / ', array_unique($group['series'] ?: ['A'])),
                    ],
                    'establecimiento_id' => $est->id,
                    'extracted' => $group['extracted'],
                    'filtro_tipo' => $filtroTipo,
                ], now()->addHours(2));

                $group['temp_token'] = $tempToken;
                $group['pending_inputs'] = $pendingInputs;
            }
            unset($group);

            // Caso 1: Un solo establecimiento / periodo (caso habitual o consolidación de varias planillas para el mismo CESFAM)
            if (count($groups) === 1) {
                $single = reset($groups);
                $est = $single['establecimiento'];
                $periodo = [
                    'año' => $single['año'],
                    'mes' => $single['mes'] ?? (int) date('n'),
                    'mes_nombre' => $single['mes_nombre'] ?? 'Periodo actual',
                    'serie' => implode(' / ', array_unique($single['series'] ?: ['A'])),
                ];

                $status = ! empty($single['pending_inputs']) ? 'requires_input' : 'ready';

                return response()->json([
                    'status' => $status,
                    'temp_token' => $single['temp_token'],
                    'establecimiento' => [
                        'id' => $est->id,
                        'nombre' => $est->nombre,
                        'codigo_deis' => $est->codigo_deis,
                        'poblacion_inscrita' => $est->poblacion_inscrita,
                    ],
                    'periodo' => $periodo,
                    'extracted_metas' => $single['extracted'],
                    'pending_inputs' => $single['pending_inputs'],
                    'archivos_procesados' => $single['archivos_procesados'],
                    'is_multiple' => count($uploadedFiles) > 1,
                    'total_archivos' => count($uploadedFiles),
                ]);
            }

            // Caso 2: Múltiples establecimientos en el mismo lote
            $gruposData = array_values(array_map(function ($g) {
                return [
                    'temp_token' => $g['temp_token'],
                    'establecimiento' => [
                        'id' => $g['establecimiento']->id,
                        'nombre' => $g['establecimiento']->nombre,
                        'codigo_deis' => $g['establecimiento']->codigo_deis,
                        'poblacion_inscrita' => $g['establecimiento']->poblacion_inscrita,
                    ],
                    'periodo' => [
                        'año' => $g['año'],
                        'mes' => $g['mes'],
                        'mes_nombre' => $g['mes_nombre'],
                        'serie' => implode(' / ', array_unique($g['series'] ?: ['A'])),
                    ],
                    'archivos_procesados' => $g['archivos_procesados'],
                    'total_indicadores' => count($g['extracted']),
                    'pending_inputs' => $g['pending_inputs'],
                    'requires_input' => ! empty($g['pending_inputs']),
                ];
            }, $groups));

            return response()->json([
                'status' => 'batch_ready',
                'message' => 'Se procesaron '.count($uploadedFiles).' planillas correspondientes a '.count($groups).' establecimientos distintos.',
                'total_archivos' => count($uploadedFiles),
                'total_establecimientos' => count($groups),
                'grupos' => $gruposData,
            ]);

        } catch (\Throwable $e) {
            Log::error('Error al previsualizar archivo(s) REM: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json([
                'status' => 'error',
                'message' => 'No fue posible procesar las planillas. Verifique que los archivos correspondan a un REM válido e intente nuevamente.',
            ], 422);
        } finally {
            foreach ($storedPaths as $storedPath) {
                Storage::disk('local')->delete($storedPath);
            }
        }
    }

    /**
     * Elimina planillas temporales de importaciones ya expiradas (la caché de la importación dura 2 horas).
     */
    private function limpiarTemporalesAntiguos(): void
    {
        $disk = Storage::disk('local');
        $limite = now()->subHours(2)->getTimestamp();

        foreach ($disk->files('temp_rem') as $archivo) {
            if ($disk->lastModified($archivo) < $limite) {
                $disk->delete($archivo);
            }
        }
    }

    /**
     * Recibe los valores complementarios ingresados vía SweetAlert2, efectúa los cálculos
     * finales de cumplimiento y persiste las mediciones tanto de Metas Sanitarias como de IAAPS 2025.
     */
    public function guardar(Request $request): JsonResponse
    {
        if ($request->has('batch') && is_array($request->input('batch'))) {
            return $this->guardarBatch($request);
        }

        $request->validate([
            'temp_token' => ['required', 'string'],
            'establecimiento_id' => ['required', 'exists:establecimientos,id'],
            'año' => ['required', 'integer'],
            'mes' => ['required', 'integer', 'between:1,12'],
            'inputs' => ['nullable', 'array'],
        ]);

        $tempToken = $request->input('temp_token');
        $sessionData = Cache::get("rem_pending_{$tempToken}");

        if (! is_array($sessionData)) {
            return response()->json([
                'status' => 'error',
                'message' => 'La importación expiró o no es válida. Vuelva a procesar las planillas.',
            ], 422);
        }

        // La importación solo puede confirmarla el usuario que la generó.
        if (isset($sessionData['user_id']) && (int) $sessionData['user_id'] !== (int) $request->user()->id) {
            abort(403, 'No autorizado para confirmar esta importación.');
        }

        $establecimientoId = (int) $request->input('establecimiento_id');

        // Los datos extraídos solo pueden persistirse para el establecimiento detectado en la planilla.
        if (isset($sessionData['establecimiento_id']) && (int) $sessionData['establecimiento_id'] !== $establecimientoId) {
            return response()->json([
                'status' => 'error',
                'message' => 'El establecimiento seleccionado no coincide con el de la planilla procesada.',
            ], 422);
        }

        $establecimiento = Establecimiento::findOrFail($establecimientoId);
        $año = (int) $request->input('año');
        $mes = (int) $request->input('mes');
        $userInputs = $request->input('inputs', []);

        $extracted = $sessionData['extracted'] ?? [];
        $savedMedidas = $this->calculateAndSaveIndicadores($establecimiento, $año, $mes, $extracted, $userInputs);

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

    /**
     * Guarda en lote mediciones para múltiples establecimientos.
     */
    protected function guardarBatch(Request $request): JsonResponse
    {
        $batch = $request->input('batch', []);
        $results = [];
        $totalMedidasCount = 0;

        foreach ($batch as $item) {
            $tempToken = $item['temp_token'] ?? null;
            $establecimientoId = (int) ($item['establecimiento_id'] ?? 0);
            $año = (int) ($item['año'] ?? date('Y'));
            $mes = (int) ($item['mes'] ?? date('n'));
            $userInputs = $item['inputs'] ?? [];

            $establecimiento = Establecimiento::find($establecimientoId);
            if (! $establecimiento) {
                continue;
            }

            $sessionData = $tempToken ? Cache::get("rem_pending_{$tempToken}") : null;

            if (! is_array($sessionData)) {
                continue;
            }

            // La importación solo puede confirmarla el usuario que la generó, para el establecimiento detectado.
            if (isset($sessionData['user_id']) && (int) $sessionData['user_id'] !== (int) $request->user()->id) {
                continue;
            }

            if (isset($sessionData['establecimiento_id']) && (int) $sessionData['establecimiento_id'] !== $establecimientoId) {
                continue;
            }

            $extracted = $sessionData['extracted'] ?? [];

            $savedMedidas = $this->calculateAndSaveIndicadores($establecimiento, $año, $mes, $extracted, $userInputs);
            if ($tempToken) {
                Cache::forget("rem_pending_{$tempToken}");
            }

            $totalMedidasCount += count($savedMedidas);
            $results[] = [
                'establecimiento' => [
                    'id' => $establecimiento->id,
                    'nombre' => $establecimiento->nombre,
                ],
                'medidas_guardadas' => count($savedMedidas),
            ];
        }

        return response()->json([
            'status' => 'success',
            'message' => "Se procesaron y guardaron {$totalMedidasCount} mediciones para ".count($results).' establecimientos.',
            'total_establecimientos' => count($results),
            'total_medidas' => $totalMedidasCount,
            'detalles' => $results,
        ]);
    }

    /**
     * Calcula y persiste las mediciones para un establecimiento y periodo dado.
     *
     * @return list<array<string, mixed>>
     */
    protected function calculateAndSaveIndicadores(Establecimiento $establecimiento, int $año, int $mes, array $extracted, array $userInputs): array
    {
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
                    $numerador = (float) ($metaExtracted['numerador'] ?? ($userInputs['numerador_ms_03'] ?? ($userInputs['numerador_ms_03_a'] ?? 0)));
                    $denominador = (float) ($userInputs['poblacion_fonasa_0_9'] ?? 0);
                    break;

                case 'MS-03.A':
                case 'MS-03A':
                    $numerador = (float) ($metaExtracted['numerador'] ?? ($userInputs['numerador_ms_03_a'] ?? ($userInputs['numerador_ms_03'] ?? 0)));
                    $denominador = (float) ($userInputs['poblacion_fonasa_0_9'] ?? 0);
                    break;

                case 'MS-03.B':
                case 'MS-03B':
                    $numerador = (float) ($metaExtracted['numerador'] ?? ($userInputs['numerador_ms_03_b'] ?? 0));
                    $denominador = (float) ($userInputs['poblacion_fonasa_6_anos'] ?? 0);
                    break;

                case 'MS-04':
                case 'MS-04.A':
                case 'MS-04A':
                    $numerador = (float) ($metaExtracted['numerador'] ?? ($userInputs['numerador_dm2_compensada'] ?? 0));
                    $denominador = (float) ($userInputs['poblacion_estimada_dm2'] ?? 0);
                    break;

                case 'MS-04.B':
                case 'MS-04B':
                    $numerador = (float) ($metaExtracted['numerador'] ?? ($userInputs['numerador_pie_diabetico'] ?? 0));
                    $denominador = (float) ($metaExtracted['denominador'] ?? ($userInputs['dm2_bajo_control'] ?? 0));
                    break;

                case 'MS-05':
                    $numerador = (float) ($metaExtracted['numerador'] ?? ($userInputs['numerador_hta_compensada'] ?? 0));
                    $denominador = (float) ($userInputs['poblacion_estimada_hta'] ?? 0);
                    break;

                case 'MS-06':
                    $numerador = (float) ($metaExtracted['numerador'] ?? ($userInputs['numerador_lme_6m'] ?? 0));
                    $denominador = (float) ($metaExtracted['denominador'] ?? ($userInputs['denominador_control_6m'] ?? 0));
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
                    $numerador = (float) ($metaExtracted['numerador'] ?? ($userInputs['iaaps_04_sic'] ?? 0));
                    $denominador = (float) ($metaExtracted['denominador'] ?? ($userInputs['iaaps_04_total_consultas'] ?? 1296));
                    $tipoCalculo = 'Inverso';
                    break;

                case 'IAAPS-05':
                    $numerador = (float) ($metaExtracted['numerador'] ?? ($userInputs['iaaps_05_vdi'] ?? 0));
                    $denominador = (float) ($pobTotal > 0 ? round($pobTotal / 3.3) : ($userInputs['iaaps_05_familias'] ?? 1000));
                    $tipoCalculo = 'Tasa';
                    break;

                case 'IAAPS-06.1A':
                    $numerador = (float) ($metaExtracted['numerador'] ?? ($userInputs['iaaps_06_1a_num'] ?? 0));
                    $gestantes = (float) ($metaExtracted['detalles']['gestantes_p1'] ?? 0);
                    $defDen = $pobTotal > 0 ? max(1, round($pobTotal * 0.31) - $gestantes) : 0;
                    $denominador = (float) ($userInputs['iaaps_06_1a_den'] ?? $defDen);
                    break;

                case 'IAAPS-06.1B':
                    $numerador = (float) ($metaExtracted['numerador'] ?? ($userInputs['iaaps_06_1b_num'] ?? 0));
                    $denominador = (float) ($userInputs['iaaps_06_1b_den'] ?? round($pobTotal * 0.29));
                    break;

                case 'IAAPS-06.2':
                    $numerador = (float) ($metaExtracted['numerador'] ?? ($userInputs['iaaps_06_2_num'] ?? 0));
                    $denominador = (float) ($userInputs['iaaps_06_2_den'] ?? round($pobTotal * 0.14));
                    break;

                case 'IAAPS-07':
                    $numerador = (float) ($metaExtracted['numerador'] ?? ($userInputs['iaaps_07_num'] ?? 0));
                    $denominador = (float) ($metaExtracted['denominador'] ?? ($userInputs['iaaps_07_den'] ?? round($pobTotal * 0.02)));
                    break;

                case 'IAAPS-08':
                    $numerador = (float) ($metaExtracted['numerador'] ?? ($userInputs['iaaps_08_num'] ?? 0));
                    $denominador = (float) ($userInputs['iaaps_08_den'] ?? round($pobTotal * 0.13));
                    break;

                case 'IAAPS-09.1':
                    $numerador = (float) ($metaExtracted['numerador'] ?? ($userInputs['iaaps_09_1_num'] ?? 0));
                    $denominador = (float) ($userInputs['iaaps_09_1_den'] ?? round($pobTotal * 0.22));
                    break;

                case 'IAAPS-09.2':
                    $numerador = (float) ($metaExtracted['numerador'] ?? ($userInputs['iaaps_09_2_num'] ?? 0));
                    $denominador = (float) ($metaExtracted['denominador'] ?? ($userInputs['iaaps_09_2_den'] ?? 100));
                    $tipoCalculo = 'Tasa';
                    break;

                case 'IAAPS-09.3':
                    $numerador = (float) ($metaExtracted['numerador'] ?? ($userInputs['iaaps_09_3_num'] ?? 0));
                    $denominador = (float) ($metaExtracted['denominador'] ?? ($userInputs['iaaps_09_3_den'] ?? 100));
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
                    $numerador = (float) ($metaExtracted['numerador'] ?? ($userInputs['iaaps_13_num'] ?? 0));
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
                    $numerador = (float) ($metaExtracted['numerador'] ?? ($userInputs['iaaps_16_num'] ?? 0));
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

        return $savedMedidas;
    }
}
