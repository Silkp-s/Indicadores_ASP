<?php

namespace App\Services;

use App\Models\Establecimiento;
use App\Models\Indicador;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class RemReaderService
{
    /**
     * Meses en español mapeados a número de mes.
     *
     * @var array<string, int>
     */
    protected array $mesesMap = [
        'enero' => 1,
        'febrero' => 2,
        'marzo' => 3,
        'abril' => 4,
        'mayo' => 5,
        'junio' => 6,
        'julio' => 7,
        'agosto' => 8,
        'septiembre' => 9,
        'octubre' => 10,
        'noviembre' => 11,
        'diciembre' => 12,
    ];

    /**
     * Obtiene los nombres de todas las hojas sin cargarlas en memoria.
     *
     * @return list<string>
     */
    public function getSheetNames(string $filePath): array
    {
        $reader = new Xlsx;

        return $reader->listWorksheetNames($filePath);
    }

    /**
     * Abre un archivo de planilla (.xlsm / .xlsx / .xls) sin ejecutar macros
     * y optimizado para sólo lectura de datos.
     *
     * @param  list<string>|null  $onlySheets
     */
    public function loadSpreadsheet(string $filePath, ?array $onlySheets = null): Spreadsheet
    {
        @ini_set('memory_limit', '1024M');

        $reader = new Xlsx;
        $reader->setReadDataOnly(true);
        $reader->setIncludeCharts(false);

        if (! empty($onlySheets)) {
            $reader->setLoadSheetsOnly($onlySheets);
        }

        return $reader->load($filePath);
    }

    /**
     * Carga únicamente las hojas relevantes para metadatos e indicadores clínicos (Metas Sanitarias e IAAPS 2025),
     * evitando consumir cientos de megabytes en planillas masivas de 30+ hojas.
     */
    public function loadRelevantSpreadsheet(string $filePath): Spreadsheet
    {
        $sheetNames = $this->getSheetNames($filePath);
        $targetSheets = [
            'NOMBRE', 'A01', 'A02', 'A03', 'A04', 'A05', 'A09',
            'A23', 'A24', 'A26', 'A27',
            'P2', 'P3', 'P4', 'P6', 'P12',
        ];
        $sheetsToLoad = array_values(array_intersect($sheetNames, $targetSheets));

        if (empty($sheetsToLoad)) {
            $sheetsToLoad = ! empty($sheetNames) ? [$sheetNames[0]] : null;
        }

        return $this->loadSpreadsheet($filePath, $sheetsToLoad);
    }

    /**
     * Detecta metadatos del archivo REM: código DEIS, mes, año, nombre del establecimiento y serie.
     *
     * @return array{
     *     codigo_deis: ?string,
     *     nombre_establecimiento: ?string,
     *     mes: ?int,
     *     mes_nombre: ?string,
     *     año: int,
     *     serie: ?string
     * }
     */
    public function detectMetadata(Spreadsheet $spreadsheet, string $filename): array
    {
        $metadata = [
            'codigo_deis' => null,
            'nombre_establecimiento' => null,
            'mes' => null,
            'mes_nombre' => null,
            'año' => (int) date('Y'),
            'serie' => null,
        ];

        // 1. Fallback por nomenclatura de archivo: {CODIGO_DEIS}{SERIE}{MES}.xlsm (ej: 119308A06.xlsm)
        $basename = pathinfo($filename, PATHINFO_FILENAME);
        if (preg_match('/^(\d{5,7})([A-Za-z]+)(\d{1,2})/i', $basename, $matches)) {
            $metadata['codigo_deis'] = $matches[1];
            $metadata['serie'] = strtoupper($matches[2]);
            $metadata['mes'] = (int) $matches[3];
        }

        // 2. Detección principal por contenido de celdas en la hoja 'NOMBRE'
        $sheet = $spreadsheet->getSheetByName('NOMBRE') ?? $spreadsheet->getSheet(0);

        if ($sheet !== null) {
            $maxRow = min($sheet->getHighestRow(), 35);

            for ($r = 1; $r <= $maxRow; $r++) {
                $colA = trim((string) $sheet->getCell("A{$r}")->getValue());
                $colB = trim((string) $sheet->getCell("B{$r}")->getValue());

                // Nombre y código del establecimiento (Fila 3 habitual de carátula DEIS)
                if (stripos($colA, 'NOMBRE ESTABLECIMIENTO') !== false) {
                    if ($colB !== '') {
                        $metadata['nombre_establecimiento'] = $colB;
                    }

                    $digits = '';
                    foreach (['C', 'D', 'E', 'F', 'G', 'H'] as $col) {
                        $val = $sheet->getCell("{$col}{$r}")->getValue();
                        if ($val !== null && is_numeric($val)) {
                            $digits .= (string) $val;
                        }
                    }

                    if (strlen($digits) >= 5) {
                        $metadata['codigo_deis'] = $digits;
                    }
                }

                // Mes reportado
                if (preg_match('/^MES\s*:/i', $colA) || $colA === 'MES') {
                    if ($colB !== '') {
                        $cleanMes = strtolower($colB);
                        if (isset($this->mesesMap[$cleanMes])) {
                            $metadata['mes'] = $this->mesesMap[$cleanMes];
                            $metadata['mes_nombre'] = ucfirst($cleanMes);
                        } elseif (is_numeric($colB) && (int) $colB >= 1 && (int) $colB <= 12) {
                            $metadata['mes'] = (int) $colB;
                        }
                    }
                }

                // Año de evaluación
                if (preg_match('/^AÑO\s*:/i', $colA) || preg_match('/^ANO\s*:/i', $colA) || $colA === 'AÑO') {
                    if (is_numeric($colB) && (int) $colB >= 2020 && (int) $colB <= 2040) {
                        $metadata['año'] = (int) $colB;
                    }
                }

                // Serie
                if (stripos($colB, 'SERIE') !== false) {
                    if (preg_match('/SERIE\s*([A-Za-z]+)/i', $colB, $sm)) {
                        $metadata['serie'] = strtoupper($sm[1]);
                    }
                }
            }
        }

        $nombresMeses = array_flip($this->mesesMap);
        if ($metadata['mes'] !== null && $metadata['mes_nombre'] === null && isset($nombresMeses[$metadata['mes']])) {
            $metadata['mes_nombre'] = ucfirst($nombresMeses[$metadata['mes']]);
        }

        return $metadata;
    }

    /**
     * Vincula el código o nombre detectado con la tabla `establecimientos`.
     */
    public function matchEstablecimiento(?string $codigoDeis, ?string $nombre): ?Establecimiento
    {
        if (! empty($codigoDeis)) {
            $match = Establecimiento::where('codigo_deis', $codigoDeis)->first();
            if ($match !== null) {
                return $match;
            }
        }

        if (! empty($nombre)) {
            $match = Establecimiento::where('nombre', $nombre)->first();
            if ($match !== null) {
                return $match;
            }

            $cleanNombre = trim(preg_replace('/^(CESFAM|CECOSF|SAR|SAPU|DAS)\s+/i', '', $nombre));
            $match = Establecimiento::where('nombre', 'like', "%{$cleanNombre}%")->first();
            if ($match !== null) {
                return $match;
            }
        }

        return null;
    }

    /**
     * Extrae numeradores y denominadores de la planilla REM tanto para
     * Metas Sanitarias Ley 19.813 como para indicadores IAAPS 2025.
     *
     * @return array<string, array{
     *     numerador: ?float,
     *     denominador: ?float,
     *     fuente: string,
     *     detalles: array<string, mixed>
     * }>
     */
    public function extractNumeradores(Spreadsheet $spreadsheet): array
    {
        $sheetNames = $spreadsheet->getSheetNames();
        $results = [];

        // ==========================================
        // 1. METAS SANITARIAS LEY 19.813 (2026)
        // ==========================================

        // MS-01: Desarrollo Psicomotor (EEDP) 12-23 meses -> Hoja A03
        if (in_array('A03', $sheetNames, true)) {
            $sheet = $spreadsheet->getSheetByName('A03');
            $highestRow = min($sheet->getHighestRow(), 70);

            $sumCols1223 = function (int $row) use ($sheet): float {
                $sum = 0.0;
                foreach (['J', 'K', 'L', 'M'] as $col) {
                    $v = $sheet->getCell("{$col}{$row}")->getValue();
                    if (is_numeric($v)) {
                        $sum += (float) $v;
                    }
                }

                return $sum;
            };

            $numNormalReeval = 0.0;
            $denRiesgoRetraso = 0.0;
            $detalles = [];

            for ($r = 15; $r <= $highestRow; $r++) {
                $act = trim((string) $sheet->getCell("A{$r}")->getValue());
                $res = trim((string) $sheet->getCell("B{$r}")->getValue());

                if (stripos($res, 'Riesgo') !== false && stripos($res, 'desde') === false && stripos($act, 'Traslado') === false) {
                    $val = $sumCols1223($r);
                    $denRiesgoRetraso += $val;
                    $detalles['primera_eval_riesgo_r'.$r] = $val;
                } elseif (stripos($res, 'Retraso') !== false && stripos($res, 'desde') === false && stripos($act, 'Traslado') === false) {
                    $val = $sumCols1223($r);
                    $denRiesgoRetraso += $val;
                    $detalles['primera_eval_retraso_r'.$r] = $val;
                }

                if (stripos($res, 'Normal (desde riesgo)') !== false) {
                    $val = $sumCols1223($r);
                    $numNormalReeval += $val;
                    $detalles['reeval_normal_desde_riesgo_r'.$r] = $val;
                } elseif (stripos($res, 'Normal (desde retraso)') !== false) {
                    $val = $sumCols1223($r);
                    $numNormalReeval += $val;
                    $detalles['reeval_normal_desde_retraso_r'.$r] = $val;
                }
            }

            $results['MS-01'] = [
                'numerador' => $numNormalReeval,
                'denominador' => $denRiesgoRetraso > 0 ? $denRiesgoRetraso : null,
                'fuente' => 'REM-A03 (Sección A.2)',
                'detalles' => $detalles,
            ];
        }

        // MS-03: Odontología (Pauta CERO 0-9 años y Altas 12 años) -> Hoja A09
        if (in_array('A09', $sheetNames, true)) {
            $sheet = $spreadsheet->getSheetByName('A09');
            $highestRow = min($sheet->getHighestRow(), 80);

            $cero09 = 0.0;
            $altas12 = 0.0;
            $detalles = [];

            for ($r = 40; $r <= $highestRow; $r++) {
                $act = trim((string) $sheet->getCell("A{$r}")->getValue());

                if (stripos($act, 'cero') !== false && stripos($act, 'ingreso') !== false) {
                    for ($c = 7; $c <= 28; $c++) {
                        $colLetter = Coordinate::stringFromColumnIndex($c);
                        $v = $sheet->getCell("{$colLetter}{$r}")->getValue();
                        if (is_numeric($v)) {
                            $cero09 += (float) $v;
                        }
                    }
                    $detalles['ingresos_pauta_cero_0_9'] = $cero09;
                }

                if (stripos($act, 'alta') !== false && stripos($act, 'odontol') !== false) {
                    $valS = (float) $sheet->getCell("S{$r}")->getValue();
                    $valT = (float) $sheet->getCell("T{$r}")->getValue();
                    $altas12 = $valS + $valT;
                    $detalles['altas_odontologicas'] = $altas12;
                }
            }

            $results['MS-03'] = [
                'numerador' => $cero09 > 0 ? $cero09 : ($altas12 > 0 ? $altas12 : 0.0),
                'denominador' => null,
                'fuente' => 'REM-A09 (Sección C)',
                'detalles' => $detalles,
            ];
        }

        // MS-06: Lactancia Materna Exclusiva al 6° mes -> Hoja A24 o A27
        foreach (['A24', 'A27'] as $lmeSheet) {
            if (in_array($lmeSheet, $sheetNames, true)) {
                $sheet = $spreadsheet->getSheetByName($lmeSheet);
                $highestRow = min($sheet->getHighestRow(), 180);

                for ($r = 10; $r <= $highestRow; $r++) {
                    $txt = trim((string) $sheet->getCell("A{$r}")->getValue());
                    if (stripos($txt, 'lactancia materna exclusiva') !== false) {
                        $v = $sheet->getCell("C{$r}")->getValue();
                        if (is_numeric($v) && (float) $v > 0) {
                            $results['MS-06'] = [
                                'numerador' => (float) $v,
                                'denominador' => null,
                                'fuente' => "REM-{$lmeSheet}",
                                'detalles' => ['fila' => $r, 'texto' => $txt],
                            ];
                            break 2;
                        }
                    }
                }
            }
        }

        // ==========================================
        // 2. INDICADORES IAAPS 2025
        // ==========================================

        // IAAPS-03: Tasa de consultas médicas de morbilidad -> Hoja A04 (R12 celda B12)
        if (in_array('A04', $sheetNames, true)) {
            $sheet = $spreadsheet->getSheetByName('A04');
            $valMorb = $sheet->getCell('B12')->getCalculatedValue();

            if (is_numeric($valMorb) && (float) $valMorb > 0) {
                $results['IAAPS-03'] = [
                    'numerador' => (float) $valMorb,
                    'denominador' => null, // Se asigna con la población inscrita validada
                    'fuente' => 'REM-A04 (Sección A, Fila 12)',
                    'detalles' => ['consultas_medicas_total' => (float) $valMorb],
                ];

                // IAAPS-04: Denominador total de consultas médicas realizadas en APS
                $results['IAAPS-04'] = [
                    'numerador' => null, // SIC generadas
                    'denominador' => (float) $valMorb,
                    'fuente' => 'REM-A04',
                    'detalles' => ['total_consultas_medicas' => (float) $valMorb],
                ];
            }
        }

        // IAAPS-06.1A y IAAPS-06.1B: Cobertura EMP Mujeres y Hombres (20-64 años) -> Hoja A02
        if (in_array('A02', $sheetNames, true)) {
            $sheet = $spreadsheet->getSheetByName('A02');
            $empHombres = (float) $sheet->getCell('C11')->getCalculatedValue();
            $empMujeres = (float) $sheet->getCell('D11')->getCalculatedValue();

            if ($empMujeres > 0) {
                $results['IAAPS-06.1A'] = [
                    'numerador' => $empMujeres,
                    'denominador' => null, // Población mujeres 20-64 menos embarazadas
                    'fuente' => 'REM-A02 (Sección A, Fila 11)',
                    'detalles' => ['emp_mujeres_total' => $empMujeres],
                ];
            }

            if ($empHombres > 0) {
                $results['IAAPS-06.1B'] = [
                    'numerador' => $empHombres,
                    'denominador' => null, // Población hombres 20-64
                    'fuente' => 'REM-A02 (Sección A, Fila 11)',
                    'detalles' => ['emp_hombres_total' => $empHombres],
                ];
            }
        }

        // IAAPS-07: Cobertura DSM evaluado en niños(as) de 12 a 23 meses -> Hoja A03
        if (in_array('A03', $sheetNames, true)) {
            $sheet = $spreadsheet->getSheetByName('A03');
            $dsmEvaluados = 0.0;
            for ($r = 21; $r <= 24; $r++) {
                foreach (['J', 'K', 'L', 'M'] as $col) {
                    $v = $sheet->getCell("{$col}{$r}")->getValue();
                    if (is_numeric($v)) {
                        $dsmEvaluados += (float) $v;
                    }
                }
            }

            if ($dsmEvaluados > 0) {
                $results['IAAPS-07'] = [
                    'numerador' => $dsmEvaluados,
                    'denominador' => null, // Población niños 12-23m bajo control
                    'fuente' => 'REM-A03 (Sección A.2)',
                    'detalles' => ['dsm_evaluados_12_23m' => $dsmEvaluados],
                ];
            }
        }

        // IAAPS-12: Ingreso precoz de mujeres a control de embarazo antes de las 14 semanas -> Hoja A05
        if (in_array('A05', $sheetNames, true)) {
            $sheet = $spreadsheet->getSheetByName('A05');
            $gestAntes14 = (float) $sheet->getCell('C13')->getCalculatedValue();
            $gestTotal = (float) $sheet->getCell('C11')->getCalculatedValue();

            if ($gestTotal > 0) {
                $results['IAAPS-12'] = [
                    'numerador' => $gestAntes14,
                    'denominador' => $gestTotal,
                    'fuente' => 'REM-A05 (Sección A, Filas 11 y 13)',
                    'detalles' => [
                        'ingresadas_antes_14_sem' => $gestAntes14,
                        'total_gestantes_ingresadas' => $gestTotal,
                    ],
                ];
            }
        }

        // ==========================================
        // 3. ARCHIVOS SERIE P (P12, P4, P2, P6)
        // ==========================================
        if (in_array('P12', $sheetNames, true)) {
            $sheet = $spreadsheet->getSheetByName('P12');
            $valPap = $sheet->getCell('D10')->getValue() ?? $sheet->getCell('B10')->getValue();
            if (is_numeric($valPap)) {
                $results['MS-02'] = [
                    'numerador' => (float) $valPap,
                    'denominador' => null,
                    'fuente' => 'REM-P12 (Sección A)',
                    'detalles' => ['pap_vigentes' => (float) $valPap],
                ];
            }
        }

        if (in_array('P4', $sheetNames, true)) {
            $sheet = $spreadsheet->getSheetByName('P4');
            $dmComp = (float) $sheet->getCell('C31')->getValue();
            $htaComp = (float) $sheet->getCell('C29')->getValue();
            $dmBajoControl = (float) $sheet->getCell('C17')->getValue();
            $htaBajoControl = (float) $sheet->getCell('C16')->getValue();

            if ($dmComp > 0) {
                $results['MS-04'] = [
                    'numerador' => $dmComp,
                    'denominador' => null,
                    'fuente' => 'REM-P4 (Sección B)',
                    'detalles' => ['hba1c_menor_7' => $dmComp],
                ];
            }

            if ($htaComp > 0) {
                $results['MS-05'] = [
                    'numerador' => $htaComp,
                    'denominador' => null,
                    'fuente' => 'REM-P4 (Sección B)',
                    'detalles' => ['pa_menor_140_90' => $htaComp],
                ];
            }

            if ($dmBajoControl > 0) {
                $results['IAAPS-14'] = [
                    'numerador' => $dmBajoControl,
                    'denominador' => null,
                    'fuente' => 'REM-P4 (Sección A, Fila 17)',
                    'detalles' => ['dm2_bajo_control' => $dmBajoControl],
                ];
            }

            if ($htaBajoControl > 0) {
                $results['IAAPS-15'] = [
                    'numerador' => $htaBajoControl,
                    'denominador' => null,
                    'fuente' => 'REM-P4 (Sección A, Fila 16)',
                    'detalles' => ['hta_bajo_control' => $htaBajoControl],
                ];
            }
        }

        if (in_array('P2', $sheetNames, true)) {
            $sheet = $spreadsheet->getSheetByName('P2');
            $nutrNormal = (float) $sheet->getCell('C36')->getValue();
            $nutrTotal = (float) $sheet->getCell('C12')->getValue();

            if ($nutrNormal > 0 && $nutrTotal > 0) {
                $results['IAAPS-17'] = [
                    'numerador' => $nutrNormal,
                    'denominador' => $nutrTotal,
                    'fuente' => 'REM-P2 (Sección A)',
                    'detalles' => ['normal_menor_2a' => $nutrNormal, 'total_menor_2a' => $nutrTotal],
                ];
            }
        }

        return $results;
    }

    /**
     * Determina las preguntas o insumos faltantes para completar las Metas Sanitarias e IAAPS 2025.
     * Genera preguntas secuenciales con estimaciones automáticas pre-calculadas para SweetAlert2.
     *
     * @param  array<string, array{numerador: ?float, denominador: ?float, fuente: string, detalles: array<string, mixed>}>  $extracted
     * @return list<array{
     *     meta_codigo: string,
     *     input_key: string,
     *     title: string,
     *     text: string,
     *     default_value: ?float
     * }>
     */
    public function determinePendingInputs(array $extracted, ?Establecimiento $establecimiento, int $año, string $filtroTipo = 'todas'): array
    {
        $pending = [];
        $pobTotal = $establecimiento?->poblacion_inscrita ?? 0;

        $query = Indicador::where('is_active', true);
        if ($filtroTipo === 'metas') {
            $query->where('tipo', 'Meta Sanitaria');
        } elseif ($filtroTipo === 'iaaps') {
            $query->where('tipo', 'IAAPS');
        } else {
            $query->whereIn('tipo', ['Meta Sanitaria', 'IAAPS']);
        }

        $indicadores = $query->get()->keyBy('codigo');

        // ==========================================
        // A. METAS SANITARIAS (LEY 19.813)
        // ==========================================

        // MS-01: DSM
        if (isset($indicadores['MS-01'])) {
            $data = $extracted['MS-01'] ?? null;
            if ($data === null || $data['denominador'] === null) {
                $pending[] = [
                    'meta_codigo' => 'MS-01',
                    'input_key' => 'dsm_denominador_riesgo',
                    'title' => 'Meta I: Desarrollo Psicomotor (12 a 23 meses)',
                    'text' => "Ingrese el N° de niños(as) de 12 a 23 meses diagnosticados con riesgo o retraso del DSM en 1ª evaluación ({$año}):",
                    'default_value' => null,
                ];
            }
        }

        // MS-02: PAP
        if (isset($indicadores['MS-02'])) {
            $data = $extracted['MS-02'] ?? null;
            if ($data === null || $data['numerador'] === null) {
                $pending[] = [
                    'meta_codigo' => 'MS-02',
                    'input_key' => 'numerador_pap_vigente',
                    'title' => 'Meta II: Cáncer Cuello Uterino (Numerador)',
                    'text' => "Ingrese el N° de mujeres de 25 a 64 años inscritas con PAP o VPH vigente ({$año}):",
                    'default_value' => null,
                ];
            }

            $defFonasa2564 = $pobTotal > 0 ? (float) round($pobTotal * 0.28) : null;
            $pending[] = [
                'meta_codigo' => 'MS-02',
                'input_key' => 'poblacion_fonasa_25_64',
                'title' => 'Meta II: Cáncer Cuello Uterino (Denominador)',
                'text' => "Ingrese el N° de personas de 25 a 64 años inscritas validadas por FONASA ({$año}):",
                'default_value' => $defFonasa2564,
            ];
        }

        // MS-03: Odontología
        if (isset($indicadores['MS-03'])) {
            $defFonasa09 = $pobTotal > 0 ? (float) round($pobTotal * 0.12) : null;
            $pending[] = [
                'meta_codigo' => 'MS-03',
                'input_key' => 'poblacion_fonasa_0_9',
                'title' => 'Meta III: Salud Bucal (Pauta CERO)',
                'text' => "Ingrese la población de 0 a 9 años inscrita y validada por FONASA ({$año}):",
                'default_value' => $defFonasa09,
            ];
        }

        // MS-04: DM2 Compensada
        if (isset($indicadores['MS-04'])) {
            $data = $extracted['MS-04'] ?? null;
            if ($data === null || $data['numerador'] === null) {
                $pending[] = [
                    'meta_codigo' => 'MS-04',
                    'input_key' => 'numerador_dm2_compensada',
                    'title' => 'Meta IV.A: Diabetes Mellitus (Numerador)',
                    'text' => "Ingrese el N° de personas de 15 años y más con DM2 compensada (HbA1c <7% o <8%) ({$año}):",
                    'default_value' => null,
                ];
            }

            $defDm2 = $pobTotal > 0 ? (float) round($pobTotal * 0.088) : null;
            $pending[] = [
                'meta_codigo' => 'MS-04',
                'input_key' => 'poblacion_estimada_dm2',
                'title' => 'Meta IV.A: Diabetes Mellitus (Denominador ENS)',
                'text' => "Ingrese el N° estimado de personas de 15 años y más con DM2 según prevalencia ENS ({$año}):",
                'default_value' => $defDm2,
            ];
        }

        // MS-05: HTA Compensada
        if (isset($indicadores['MS-05'])) {
            $data = $extracted['MS-05'] ?? null;
            if ($data === null || $data['numerador'] === null) {
                $pending[] = [
                    'meta_codigo' => 'MS-05',
                    'input_key' => 'numerador_hta_compensada',
                    'title' => 'Meta V: Hipertensión Arterial (Numerador)',
                    'text' => "Ingrese el N° de personas de 15 años y más con HTA con PA bajo meta (<140/90) ({$año}):",
                    'default_value' => null,
                ];
            }

            $defHta = $pobTotal > 0 ? (float) round($pobTotal * 0.215) : null;
            $pending[] = [
                'meta_codigo' => 'MS-05',
                'input_key' => 'poblacion_estimada_hta',
                'title' => 'Meta V: Hipertensión Arterial (Denominador ENS)',
                'text' => "Ingrese el N° estimado de personas de 15 años y más con HTA según prevalencia ENS ({$año}):",
                'default_value' => $defHta,
            ];
        }

        // MS-06: LME 6° mes
        if (isset($indicadores['MS-06'])) {
            $data = $extracted['MS-06'] ?? null;
            if ($data === null || $data['numerador'] === null) {
                $pending[] = [
                    'meta_codigo' => 'MS-06',
                    'input_key' => 'numerador_lme_6m',
                    'title' => 'Meta VI: Lactancia Materna Exclusiva (Numerador)',
                    'text' => "Ingrese el N° de lactantes con lactancia materna exclusiva al 6° mes ({$año}):",
                    'default_value' => null,
                ];
            }

            $pending[] = [
                'meta_codigo' => 'MS-06',
                'input_key' => 'denominador_control_6m',
                'title' => 'Meta VI: Lactancia Materna Exclusiva (Denominador)',
                'text' => "Ingrese el N° total de lactantes con control de salud al 6° mes ({$año}):",
                'default_value' => null,
            ];
        }

        // ==========================================
        // B. INDICADORES IAAPS 2025
        // ==========================================

        // IAAPS-01: MAIS Comunal
        if (isset($indicadores['IAAPS-01'])) {
            $pending[] = [
                'meta_codigo' => 'IAAPS-01',
                'input_key' => 'iaaps_01_num',
                'title' => 'IAAPS META 1: Certificación MAIS Comunal',
                'text' => 'Ingrese el N° de centros de salud de la comuna autoevaluados en MAIS:',
                'default_value' => 1.0,
            ];
            $pending[] = [
                'meta_codigo' => 'IAAPS-01',
                'input_key' => 'iaaps_01_den',
                'title' => 'IAAPS META 1: Centros Totales Comuna',
                'text' => 'Ingrese el N° total de establecimientos de salud de la comuna (año 2025):',
                'default_value' => 5.0,
            ];
        }

        // IAAPS-02.1: Horario Extendido
        if (isset($indicadores['IAAPS-02.1'])) {
            $pending[] = [
                'meta_codigo' => 'IAAPS-02.1',
                'input_key' => 'iaaps_02_1_num',
                'title' => 'IAAPS META 2.1: Horario Extendido (8-20h y Sáb 9-13h)',
                'text' => 'N° establecimientos funcionando en horario extendido:',
                'default_value' => 1.0,
            ];
            $pending[] = [
                'meta_codigo' => 'IAAPS-02.1',
                'input_key' => 'iaaps_02_1_den',
                'title' => 'IAAPS META 2.1: Establecimientos Visitados',
                'text' => 'N° total de establecimientos visitados:',
                'default_value' => 1.0,
            ];
        }

        // IAAPS-02.2: Fármacos Trazadores
        if (isset($indicadores['IAAPS-02.2'])) {
            $pending[] = [
                'meta_codigo' => 'IAAPS-02.2',
                'input_key' => 'iaaps_02_2_num',
                'title' => 'IAAPS META 2.2: Fármacos Trazadores Disponibles',
                'text' => 'N° de fármacos trazadores disponibles en farmacia:',
                'default_value' => 20.0,
            ];
            $pending[] = [
                'meta_codigo' => 'IAAPS-02.2',
                'input_key' => 'iaaps_02_2_den',
                'title' => 'IAAPS META 2.2: Total Fármacos Trazadores',
                'text' => 'N° total de fármacos trazadores definidos por norma:',
                'default_value' => 20.0,
            ];
        }

        // IAAPS-04: Derivación al Nivel Secundario (SIC)
        if (isset($indicadores['IAAPS-04'])) {
            $pending[] = [
                'meta_codigo' => 'IAAPS-04',
                'input_key' => 'iaaps_04_sic',
                'title' => 'IAAPS META 4: Derivación al Nivel Secundario',
                'text' => 'Ingrese el N° de solicitudes de interconsulta (SIC) médicas generadas en APS:',
                'default_value' => 120.0,
            ];
        }

        // IAAPS-05: Visitas Domiciliarias Integrales (VDI)
        if (isset($indicadores['IAAPS-05'])) {
            $pending[] = [
                'meta_codigo' => 'IAAPS-05',
                'input_key' => 'iaaps_05_vdi',
                'title' => 'IAAPS META 5: Tasa de Visita Domiciliaria Integral',
                'text' => 'Ingrese el N° de Visitas Domiciliarias Integrales (VDI) realizadas:',
                'default_value' => 150.0,
            ];
        }

        // IAAPS-06.1A: EMP Mujeres (Denominador)
        if (isset($indicadores['IAAPS-06.1A'])) {
            $defMujeres = $pobTotal > 0 ? (float) round($pobTotal * 0.31) : null;
            $pending[] = [
                'meta_codigo' => 'IAAPS-06.1A',
                'input_key' => 'iaaps_06_1a_den',
                'title' => 'IAAPS META 6.1.A: EMP Mujeres (Denominador)',
                'text' => 'Población mujeres 20-64 años inscrita validada (menos embarazadas 20-54 años):',
                'default_value' => $defMujeres,
            ];
        }

        // IAAPS-06.1B: EMP Hombres (Denominador)
        if (isset($indicadores['IAAPS-06.1B'])) {
            $defHombres = $pobTotal > 0 ? (float) round($pobTotal * 0.29) : null;
            $pending[] = [
                'meta_codigo' => 'IAAPS-06.1B',
                'input_key' => 'iaaps_06_1b_den',
                'title' => 'IAAPS META 6.1.B: EMP Hombres (Denominador)',
                'text' => 'Población hombres 20-64 años inscrita y validada:',
                'default_value' => $defHombres,
            ];
        }

        // IAAPS-06.2: EMPAM 65+ años
        if (isset($indicadores['IAAPS-06.2'])) {
            $def65 = $pobTotal > 0 ? (float) round($pobTotal * 0.14) : null;
            $pending[] = [
                'meta_codigo' => 'IAAPS-06.2',
                'input_key' => 'iaaps_06_2_num',
                'title' => 'IAAPS META 6.2: Examen de Medicina Preventiva Adulto Mayor (EMPAM)',
                'text' => 'N° de adultos de 65 y más años con EMP realizado:',
                'default_value' => 500.0,
            ];
            $pending[] = [
                'meta_codigo' => 'IAAPS-06.2',
                'input_key' => 'iaaps_06_2_den',
                'title' => 'IAAPS META 6.2: Población 65 y más años',
                'text' => 'Población inscrita validada de 65 años y más:',
                'default_value' => $def65,
            ];
        }

        // IAAPS-07: DSM bajo control (Denominador)
        if (isset($indicadores['IAAPS-07'])) {
            $defNinos1223 = $pobTotal > 0 ? (float) round($pobTotal * 0.02) : null;
            $pending[] = [
                'meta_codigo' => 'IAAPS-07',
                'input_key' => 'iaaps_07_den',
                'title' => 'IAAPS META 7: Niños(as) 12 a 23 meses bajo control',
                'text' => 'N° total de niños y niñas de 12 a 23 meses bajo control:',
                'default_value' => $defNinos1223,
            ];
        }

        // IAAPS-08: Control integral adolescentes 10 a 19 años
        if (isset($indicadores['IAAPS-08'])) {
            $defAdol1019 = $pobTotal > 0 ? (float) round($pobTotal * 0.13) : null;
            $pending[] = [
                'meta_codigo' => 'IAAPS-08',
                'input_key' => 'iaaps_08_num',
                'title' => 'IAAPS META 8: Control de Salud Integral Adolescente',
                'text' => 'N° de controles de salud integral realizados a adolescentes de 10 a 19 años:',
                'default_value' => 300.0,
            ];
            $pending[] = [
                'meta_codigo' => 'IAAPS-08',
                'input_key' => 'iaaps_08_den',
                'title' => 'IAAPS META 8: Población Adolescente 10 a 19 años',
                'text' => 'Población total de adolescentes de 10 a 19 años inscrita validada:',
                'default_value' => $defAdol1019,
            ];
        }

        // IAAPS-10: Garantías GES en APS
        if (isset($indicadores['IAAPS-10'])) {
            $pending[] = [
                'meta_codigo' => 'IAAPS-10',
                'input_key' => 'iaaps_10_num',
                'title' => 'IAAPS META 10: Garantías GES Cumplidas/Atendidas',
                'text' => 'N° de garantías cumplidas + exceptuadas + incumplidas atendidas:',
                'default_value' => 95.0,
            ];
            $pending[] = [
                'meta_codigo' => 'IAAPS-10',
                'input_key' => 'iaaps_10_den',
                'title' => 'IAAPS META 10: Garantías GES Exigibles Totales',
                'text' => 'Total de garantías GES del periodo (incluye retrasadas acumuladas):',
                'default_value' => 100.0,
            ];
        }

        // IAAPS-11: Vacunación Anti-Influenza
        if (isset($indicadores['IAAPS-11'])) {
            $defVacunacion = $pobTotal > 0 ? (float) round($pobTotal * 0.35) : null;
            $pending[] = [
                'meta_codigo' => 'IAAPS-11',
                'input_key' => 'iaaps_11_num',
                'title' => 'IAAPS META 11: Vacunación Anti-Influenza',
                'text' => 'Número de personas vacunadas pertenecientes a grupos objetivo:',
                'default_value' => null,
            ];
            $pending[] = [
                'meta_codigo' => 'IAAPS-11',
                'input_key' => 'iaaps_11_den',
                'title' => 'IAAPS META 11: Población Objetivo Anti-Influenza',
                'text' => 'Total de población inscrita validada de grupos objetivo:',
                'default_value' => $defVacunacion,
            ];
        }

        // IAAPS-13: Regulación fertilidad adolescentes 15-19 años
        if (isset($indicadores['IAAPS-13'])) {
            $defAdol1519 = $pobTotal > 0 ? (float) round($pobTotal * 0.07) : null;
            $pending[] = [
                'meta_codigo' => 'IAAPS-13',
                'input_key' => 'iaaps_13_num',
                'title' => 'IAAPS META 13: Regulación Fertilidad Adolescente',
                'text' => 'N° de adolescentes de 15 a 19 años con método de regulación de fertilidad:',
                'default_value' => null,
            ];
            $pending[] = [
                'meta_codigo' => 'IAAPS-13',
                'input_key' => 'iaaps_13_den',
                'title' => 'IAAPS META 13: Total Adolescentes 15 a 19 años',
                'text' => 'Total de adolescentes de 15 a 19 años inscritos:',
                'default_value' => $defAdol1519,
            ];
        }

        // IAAPS-16: Libre de caries <3 años
        if (isset($indicadores['IAAPS-16'])) {
            $defNinos03 = $pobTotal > 0 ? (float) round($pobTotal * 0.035) : null;
            $pending[] = [
                'meta_codigo' => 'IAAPS-16',
                'input_key' => 'iaaps_16_num',
                'title' => 'IAAPS META 16: Libre de Caries Menores de 3 años (CEOD = 0)',
                'text' => 'N° de niños y niñas menores de 3 años con registro CEOD = 0:',
                'default_value' => null,
            ];
            $pending[] = [
                'meta_codigo' => 'IAAPS-16',
                'input_key' => 'iaaps_16_den',
                'title' => 'IAAPS META 16: Población Menores de 3 años',
                'text' => 'Total de niños y niñas menores de 3 años inscritos:',
                'default_value' => $defNinos03,
            ];
        }

        return $pending;
    }

    /**
     * Calcula el porcentaje de cumplimiento y determina el estado según la regla de negocio:
     * - Si porcentaje >= objetivo -> 'Cumpliendo'
     * - Si porcentaje >= (objetivo * 0.85) -> 'En Riesgo'
     * - En caso contrario -> 'Crítico'
     *
     * Para tasas e indicadores con umbral inverso (ej: IAAPS-04 Derivación <= 10%),
     * aplica la lógica técnica correspondiente.
     *
     * @return array{porcentaje: float, estado: 'Cumpliendo'|'En Riesgo'|'Crítico'}
     */
    public function calculateCompliance(float $numerador, float $denominador, float $objetivo, string $tipoCalculo = 'Porcentaje'): array
    {
        if ($denominador <= 0.0) {
            return [
                'porcentaje' => 0.0,
                'estado' => 'Crítico',
            ];
        }

        // Caso especial: Tasa por habitante o por familia (ej: IAAPS-03 Tasa Morbilidad meta 1.25, IAAPS-05 Tasa VDI meta 0.15)
        if ($tipoCalculo === 'Tasa') {
            $tasa = $numerador / $denominador;
            $porcentaje = ($objetivo > 0) ? round(($tasa / $objetivo) * 100, 2) : 0.0;

            if ($porcentaje >= 100.0) {
                return ['porcentaje' => $porcentaje, 'estado' => 'Cumpliendo'];
            } elseif ($porcentaje >= 85.0) {
                return ['porcentaje' => $porcentaje, 'estado' => 'En Riesgo'];
            } else {
                return ['porcentaje' => $porcentaje, 'estado' => 'Crítico'];
            }
        } elseif ($tipoCalculo === 'Inverso') {
            // Umbral máximo tolerable (ej: % derivación nivel secundario <= 10%)
            $tasa = round(($numerador / $denominador) * 100, 2);
            $porcentaje = $tasa;
            if ($tasa <= $objetivo) {
                return ['porcentaje' => $porcentaje, 'estado' => 'Cumpliendo'];
            } elseif ($tasa <= ($objetivo * 1.15)) {
                return ['porcentaje' => $porcentaje, 'estado' => 'En Riesgo'];
            } else {
                return ['porcentaje' => $porcentaje, 'estado' => 'Crítico'];
            }
        } else {
            $porcentaje = round(($numerador / $denominador) * 100, 2);
        }

        $umbralRiesgo = $objetivo * 0.85;

        if ($porcentaje >= $objetivo) {
            $estado = 'Cumpliendo';
        } elseif ($porcentaje >= $umbralRiesgo) {
            $estado = 'En Riesgo';
        } else {
            $estado = 'Crítico';
        }

        return [
            'porcentaje' => $porcentaje,
            'estado' => $estado,
        ];
    }
}
