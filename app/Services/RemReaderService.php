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
     * Carga únicamente las hojas relevantes para metadatos e indicadores clínicos,
     * evitando consumir cientos de megabytes en planillas masivas de 30+ hojas.
     */
    public function loadRelevantSpreadsheet(string $filePath): Spreadsheet
    {
        $sheetNames = $this->getSheetNames($filePath);
        $targetSheets = ['NOMBRE', 'A03', 'A09', 'A24', 'A27', 'P4', 'P12', 'P3', 'A23'];
        $sheetsToLoad = array_values(array_intersect($sheetNames, $targetSheets));

        if (empty($sheetsToLoad)) {
            $sheetsToLoad = ! empty($sheetNames) ? [$sheetNames[0]] : null;
        }

        return $this->loadSpreadsheet($filePath, $sheetsToLoad);
    }

    /**
     * Detecta metadatos del archivo REM: código DEIS, mes, año, nombre del establecimiento y serie.
     *
     * Prioridad 1: Contenido de celdas en la hoja 'NOMBRE' (carátula oficial DEIS).
     * Prioridad 2: Nomenclatura del archivo {CODIGO_DEIS}{SERIE}{MES}.xlsm como fallback.
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

        // 2. Detección principal por contenido de celdas en la primera hoja o 'NOMBRE'
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

                    // Código DEIS desglosado en celdas C, D, E, F, G, H
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

        // Si tenemos mes numérico pero no nombre
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
            // Coincidencia exacta
            $match = Establecimiento::where('nombre', $nombre)->first();
            if ($match !== null) {
                return $match;
            }

            // Coincidencia parcial insensible a mayúsculas
            $cleanNombre = trim(preg_replace('/^(CESFAM|CECOSF|SAR|SAPU|DAS)\s+/i', '', $nombre));
            $match = Establecimiento::where('nombre', 'like', "%{$cleanNombre}%")->first();
            if ($match !== null) {
                return $match;
            }
        }

        return null;
    }

    /**
     * Extrae de la matriz de celdas los numeradores y denominadores disponibles
     * para las metas sanitarias de la Ley 19.813.
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

        // 1. Meta I (MS-01): Desarrollo Psicomotor (EEDP) 12-23 meses -> Hoja A03
        if (in_array('A03', $sheetNames, true)) {
            $sheet = $spreadsheet->getSheetByName('A03');
            $highestRow = min($sheet->getHighestRow(), 70);

            $sumCols1223 = function (int $row) use ($sheet): float {
                // Columnas para 12-23 meses: J (12-17 H), K (12-17 M), L (18-23 H), M (18-23 M)
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

                // Denominador: Primera Evaluación con Riesgo o Retraso
                if (stripos($res, 'Riesgo') !== false && stripos($res, 'desde') === false && stripos($act, 'Traslado') === false) {
                    $val = $sumCols1223($r);
                    $denRiesgoRetraso += $val;
                    $detalles['primera_eval_riesgo_r'.$r] = $val;
                } elseif (stripos($res, 'Retraso') !== false && stripos($res, 'desde') === false && stripos($act, 'Traslado') === false) {
                    $val = $sumCols1223($r);
                    $denRiesgoRetraso += $val;
                    $detalles['primera_eval_retraso_r'.$r] = $val;
                }

                // Numerador: Reevaluación Normal (desde riesgo) y Normal (desde retraso)
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

        // 2. Meta III (MS-03): Odontología (Pauta CERO 0-9 años y Altas 12 años) -> Hoja A09
        if (in_array('A09', $sheetNames, true)) {
            $sheet = $spreadsheet->getSheetByName('A09');
            $highestRow = min($sheet->getHighestRow(), 80);

            $cero09 = 0.0;
            $altas12 = 0.0;
            $detalles = [];

            for ($r = 40; $r <= $highestRow; $r++) {
                $act = trim((string) $sheet->getCell("A{$r}")->getValue());

                // Ingreso control con enfoque riesgo odontológico (cero)
                if (stripos($act, 'cero') !== false && stripos($act, 'ingreso') !== false) {
                    // Columnas 0 a 9 años (G a AB: col 7 a 28)
                    for ($c = 7; $c <= 28; $c++) {
                        $colLetter = Coordinate::stringFromColumnIndex($c);
                        $v = $sheet->getCell("{$colLetter}{$r}")->getValue();
                        if (is_numeric($v)) {
                            $cero09 += (float) $v;
                        }
                    }
                    $detalles['ingresos_pauta_cero_0_9'] = $cero09;
                }

                // Altas odontológicas integrales
                if (stripos($act, 'alta') !== false && stripos($act, 'odontol') !== false) {
                    // Columnas S y T corresponden a 6 y 12 años en diversas versiones
                    $valS = (float) $sheet->getCell("S{$r}")->getValue();
                    $valT = (float) $sheet->getCell("T{$r}")->getValue();
                    $altas12 = $valS + $valT;
                    $detalles['altas_odontologicas'] = $altas12;
                }
            }

            $results['MS-03'] = [
                'numerador' => $cero09 > 0 ? $cero09 : ($altas12 > 0 ? $altas12 : 0.0),
                'denominador' => null, // Depende de población FONASA validada 0-9 años
                'fuente' => 'REM-A09 (Sección C)',
                'detalles' => $detalles,
            ];
        }

        // 3. Meta VI (MS-06): Lactancia Materna Exclusiva al 6° mes -> Hoja A24 o A27
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

        // 4. Serie P: Si se cargó un archivo con hojas P (P12 para PAP, P4 para DM2/HTA)
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
            $dmComp = (float) $sheet->getCell('C31')->getValue(); // HbA1c < 7%
            $htaComp = (float) $sheet->getCell('C29')->getValue(); // PA < 140/90

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
        }

        return $results;
    }

    /**
     * Determina las preguntas o insumos faltantes para completar las Metas Sanitarias.
     * Retorna el arreglo de 'pending_inputs' que el frontend iterará con SweetAlert2.
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
    public function determinePendingInputs(array $extracted, ?Establecimiento $establecimiento, int $año): array
    {
        $pending = [];
        $pobTotal = $establecimiento?->poblacion_inscrita ?? 0;

        // Metas de catálogo
        $indicadores = Indicador::where('tipo', 'Meta Sanitaria')
            ->where('is_active', true)
            ->get()
            ->keyBy('codigo');

        // Meta I: MS-01 (Desarrollo Psicomotor)
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

        // Meta II: MS-02 (Cáncer Cuello Uterino - PAP)
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

            // Denominador FONASA 25-64 años
            $defFonasa2564 = $pobTotal > 0 ? (float) round($pobTotal * 0.28) : null;
            $pending[] = [
                'meta_codigo' => 'MS-02',
                'input_key' => 'poblacion_fonasa_25_64',
                'title' => 'Meta II: Cáncer Cuello Uterino (Denominador)',
                'text' => "Ingrese el N° de personas de 25 a 64 años inscritas validadas por FONASA ({$año}):",
                'default_value' => $defFonasa2564,
            ];
        }

        // Meta III: MS-03 (Salud Bucal)
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

        // Meta IV: MS-04 (DM2 Cobertura Efectiva)
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

        // Meta V: MS-05 (HTA Cobertura Efectiva)
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

        // Meta VI: MS-06 (LME 6° mes)
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

        return $pending;
    }

    /**
     * Calcula el porcentaje de cumplimiento y determina el estado según la regla de negocio:
     * - Si porcentaje >= objetivo -> 'Cumpliendo'
     * - Si porcentaje >= (objetivo * 0.85) -> 'En Riesgo'
     * - En caso contrario -> 'Crítico'
     *
     * Controla estrictamente la división por cero: si denominador es 0 o negativo, porcentaje es 0.
     *
     * @return array{porcentaje: float, estado: 'Cumpliendo'|'En Riesgo'|'Crítico'}
     */
    public function calculateCompliance(float $numerador, float $denominador, float $objetivo): array
    {
        if ($denominador <= 0.0) {
            $porcentaje = 0.0;
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
