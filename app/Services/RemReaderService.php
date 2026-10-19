<?php

namespace App\Services;

use App\Models\Establecimiento;
use App\Models\Indicador;
use App\Models\RemMapping;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

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
            'NOMBRE',
            'A01', 'A1', 'REM-A01', 'REM-A1',
            'A02', 'A2', 'REM-A02', 'REM-A2',
            'A03', 'A3', 'REM-A03', 'REM-A3',
            'A04', 'A4', 'REM-A04', 'REM-A4',
            'A05', 'A5', 'REM-A05', 'REM-A5',
            'A06', 'A6', 'REM-A06', 'REM-A6',
            'A07', 'A7', 'REM-A07', 'REM-A7',
            'A08', 'A8', 'REM-A08', 'REM-A8',
            'A09', 'A9', 'REM-A09', 'REM-A9',
            'A19a', 'A19A', 'REM-A19a', 'REM-A19A',
            'A23', 'REM-A23',
            'A24', 'REM-A24',
            'A26', 'REM-A26',
            'A27', 'REM-A27',
            'A32', 'REM-A32',
            'A33', 'REM-A33',
            'P1', 'P01', 'REM-P1', 'REM-P01',
            'P2', 'P02', 'REM-P2', 'REM-P02',
            'P3', 'P03', 'REM-P3', 'REM-P03',
            'P4', 'P04', 'REM-P4', 'REM-P04',
            'P6', 'P06', 'REM-P6', 'REM-P06',
            'P12', 'P-12', 'REM-P12',
        ];

        try {
            $mappedSheets = RemMapping::where('is_active', true)->pluck('hoja')->all();
            foreach ($mappedSheets as $ms) {
                $targetSheets[] = $ms;
                $targetSheets[] = 'REM-'.$ms;
                $targetSheets[] = 'REM '.$ms;
            }
        } catch (\Throwable) {
        }

        $sheetsToLoad = array_values(array_intersect($sheetNames, $targetSheets));

        if (empty($sheetsToLoad)) {
            $sheetsToLoad = ! empty($sheetNames) ? [$sheetNames[0]] : null;
        }

        return $this->loadSpreadsheet($filePath, $sheetsToLoad);
    }

    /**
     * Resuelve una hoja de trabajo buscando entre nombres candidatos o alias comunes.
     */
    public function getSheet(Spreadsheet $spreadsheet, string ...$candidates): ?Worksheet
    {
        $sheetNames = $spreadsheet->getSheetNames();
        foreach ($candidates as $candidate) {
            if ($spreadsheet->sheetNameExists($candidate)) {
                return $spreadsheet->getSheetByName($candidate);
            }
            foreach ($sheetNames as $name) {
                if (strcasecmp($name, $candidate) === 0) {
                    return $spreadsheet->getSheetByName($name);
                }
            }
        }

        return null;
    }

    /**
     * Obtiene el valor numérico calculado de una celda, controlando errores de fórmula y vacíos.
     */
    public function getCalculatedCell(?Worksheet $sheet, string $coord): ?float
    {
        if ($sheet === null) {
            return null;
        }

        try {
            $cell = $sheet->getCell($coord);
            $val = $cell->getCalculatedValue();

            return is_numeric($val) ? (float) $val : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Suma los valores numéricos calculados de una lista de coordenadas de celdas.
     *
     * @param  list<string>  $coords
     */
    public function sumCalculatedCells(?Worksheet $sheet, array $coords): float
    {
        if ($sheet === null) {
            return 0.0;
        }

        $sum = 0.0;
        foreach ($coords as $coord) {
            $val = $this->getCalculatedCell($sheet, trim($coord));
            if ($val !== null) {
                $sum += $val;
            }
        }

        return $sum;
    }

    /**
     * Suma los valores numéricos calculados en un rango de celdas bidimensional.
     */
    public function sumCellRange(?Worksheet $sheet, string $startCol, int $startRow, string $endCol, int $endRow): float
    {
        if ($sheet === null) {
            return 0.0;
        }

        $startColIdx = Coordinate::columnIndexFromString($startCol);
        $endColIdx = Coordinate::columnIndexFromString($endCol);
        $sum = 0.0;

        for ($r = $startRow; $r <= $endRow; $r++) {
            for ($c = $startColIdx; $c <= $endColIdx; $c++) {
                $colLetter = Coordinate::stringFromColumnIndex($c);
                $val = $this->getCalculatedCell($sheet, "{$colLetter}{$r}");
                if ($val !== null) {
                    $sum += $val;
                }
            }
        }

        return $sum;
    }

    /**
     * Evalúa una expresión de celda(s) o rango(s) separados por comas o signos '+'
     * (ej: 'G21', 'J26, K26', 'J26:M26, J28:M28', 'F206:Y207').
     */
    public function evaluateCellExpression(?Worksheet $sheet, ?string $expression): ?float
    {
        if ($sheet === null || empty($expression)) {
            return null;
        }

        $tokens = preg_split('/[,+]/', $expression);
        $total = 0.0;
        $foundAny = false;

        foreach ($tokens as $token) {
            $token = trim((string) $token);
            if ($token === '') {
                continue;
            }

            if (str_contains($token, ':')) {
                [$start, $end] = explode(':', $token, 2);
                $start = trim($start);
                $end = trim($end);

                // Separar letra(s) de columna y número de fila
                if (preg_match('/^([A-Za-z]+)(\d+)$/', $start, $mStart) && preg_match('/^([A-Za-z]+)(\d+)$/', $end, $mEnd)) {
                    $valRange = $this->sumCellRange($sheet, $mStart[1], (int) $mStart[2], $mEnd[1], (int) $mEnd[2]);
                    $total += $valRange;
                    $foundAny = true;
                }
            } else {
                $cellVal = $this->getCalculatedCell($sheet, $token);
                if ($cellVal !== null) {
                    $total += $cellVal;
                    $foundAny = true;
                }
            }
        }

        return $foundAny ? $total : null;
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
     * Metas Sanitarias Ley 19.813 como para indicadores IAAPS 2025,
     * siguiendo estrictamente las Orientaciones Técnicas ministeriales.
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
        $results = [];

        // ==========================================
        // 1. METAS SANITARIAS LEY 19.813 (2026)
        // ==========================================

        // MS-01: Desarrollo Psicomotor (EEDP) 12-23 meses -> Hoja REM-A03 (Sección A.2)
        // Numerador: J26..M26 + J28..M28
        // Denominador: (J23..M23) - (J37..M37 + J40..M40)
        $sheetA03 = $this->getSheet($spreadsheet, 'A03', 'A3', 'REM-A03', 'REM-A3');
        if ($sheetA03 !== null) {
            $numCoords = ['J26', 'K26', 'L26', 'M26', 'J28', 'K28', 'L28', 'M28'];
            $denPart1 = ['J23', 'K23', 'L23', 'M23'];
            $denPart2 = ['J37', 'K37', 'L37', 'M37', 'J40', 'K40', 'L40', 'M40'];

            $numNormalReeval = $this->sumCalculatedCells($sheetA03, $numCoords);
            $denRiesgoRetraso = $this->sumCalculatedCells($sheetA03, $denPart1) - $this->sumCalculatedCells($sheetA03, $denPart2);

            // Fallback por análisis de texto si las celdas directas estuvieran vacías en otra variante
            if ($numNormalReeval === 0.0 && $denRiesgoRetraso <= 0.0) {
                $highestRow = min($sheetA03->getHighestRow(), 70);
                for ($r = 15; $r <= $highestRow; $r++) {
                    $act = trim((string) $sheetA03->getCell("A{$r}")->getValue());
                    $res = trim((string) $sheetA03->getCell("B{$r}")->getValue());
                    $rowVal = $this->sumCalculatedCells($sheetA03, ["J{$r}", "K{$r}", "L{$r}", "M{$r}"]);

                    if (stripos($res, 'Riesgo') !== false && stripos($res, 'desde') === false && stripos($act, 'Traslado') === false) {
                        $denRiesgoRetraso += $rowVal;
                    } elseif (stripos($res, 'Retraso') !== false && stripos($res, 'desde') === false && stripos($act, 'Traslado') === false) {
                        $denRiesgoRetraso += $rowVal;
                    }

                    if (stripos($res, 'Normal (desde riesgo)') !== false || stripos($res, 'Normal (desde retraso)') !== false) {
                        $numNormalReeval += $rowVal;
                    }
                }
            }

            if ($numNormalReeval > 0 || $denRiesgoRetraso > 0) {
                $results['MS-01'] = [
                    'numerador' => $numNormalReeval,
                    'denominador' => $denRiesgoRetraso > 0 ? $denRiesgoRetraso : null,
                    'fuente' => 'REM-A03 (Sección A.2)',
                    'detalles' => [
                        'recuperados_normal' => $numNormalReeval,
                        'primeras_eval_riesgo_netas' => $denRiesgoRetraso,
                    ],
                ];
            }
        }

        // MS-03: Salud Bucal Infantil -> Hoja REM-A03 (Sección D.7) y REM-A09 (Sección C)
        // Submeta III.A: Fila 206 (F206:Y206) + Fila 207 (F207:Y207)
        // Submeta III.B: Celdas S51 + T51 (CEOD = 0 en 6 años)
        $sheetA09 = $this->getSheet($spreadsheet, 'A09', 'A9', 'REM-A09', 'REM-A9');
        $cero09 = 0.0;
        $libresCaries6a = 0.0;

        if ($sheetA03 !== null) {
            $cero09 = $this->sumCellRange($sheetA03, 'F', 206, 'Y', 207);
        }

        if ($sheetA09 !== null) {
            $libresCaries6a = $this->sumCalculatedCells($sheetA09, ['S51', 'T51']);
            if ($cero09 === 0.0) {
                $highestRow = min($sheetA09->getHighestRow(), 80);
                for ($r = 40; $r <= $highestRow; $r++) {
                    $act = trim((string) $sheetA09->getCell("A{$r}")->getValue());
                    if (stripos($act, 'cero') !== false && stripos($act, 'ingreso') !== false) {
                        $cero09 = $this->sumCellRange($sheetA09, 'G', $r, 'AB', $r);
                        break;
                    }
                }
            }
        }

        if ($cero09 > 0 || $libresCaries6a > 0) {
            $results['MS-03'] = [
                'numerador' => $cero09 > 0 ? $cero09 : $libresCaries6a,
                'denominador' => null, // Población inscrita validada FONASA (0 a 9 años)
                'fuente' => $cero09 > 0 ? 'REM-A03 (Sección D.7)' : 'REM-A09 (Sección C)',
                'detalles' => [
                    'pauta_cero_0_9' => $cero09,
                    'libres_caries_6_anos' => $libresCaries6a,
                ],
            ];
        }

        // MS-06: Lactancia Materna Exclusiva al 6° mes -> Hoja REM-A03 (Sección A.5)
        // Numerador: Celda H61 (Código A0200002)
        // Denominador: Celda H67 (Código A0200001)
        if ($sheetA03 !== null) {
            $lmeNum = $this->getCalculatedCell($sheetA03, 'H61');
            $lmeDen = $this->getCalculatedCell($sheetA03, 'H67');

            if ($lmeNum !== null && $lmeNum > 0) {
                $results['MS-06'] = [
                    'numerador' => $lmeNum,
                    'denominador' => ($lmeDen !== null && $lmeDen > 0) ? $lmeDen : null,
                    'fuente' => 'REM-A03 (Sección A.5, H61/H67)',
                    'detalles' => [
                        'lme_6m' => $lmeNum,
                        'total_control_6m' => $lmeDen,
                    ],
                ];
            }
        }

        // Fallback MS-06 por escaneo A24/A27 si no se encontró en A03
        if (! isset($results['MS-06'])) {
            $sheetLme = $this->getSheet($spreadsheet, 'A24', 'A27', 'REM-A24', 'REM-A27');
            if ($sheetLme !== null) {
                $highestRow = min($sheetLme->getHighestRow(), 180);
                for ($r = 10; $r <= $highestRow; $r++) {
                    $txt = trim((string) $sheetLme->getCell("A{$r}")->getValue());
                    if (stripos($txt, 'lactancia materna exclusiva') !== false) {
                        $v = $this->getCalculatedCell($sheetLme, "C{$r}");
                        if ($v !== null && $v > 0) {
                            $results['MS-06'] = [
                                'numerador' => $v,
                                'denominador' => null,
                                'fuente' => 'REM-'.$sheetLme->getTitle(),
                                'detalles' => ['fila' => $r, 'texto' => $txt],
                            ];
                            break;
                        }
                    }
                }
            }
        }

        // MS-02: Cáncer Cuello Uterino (PAP / VPH 25 a 64 años) -> Hoja REM-P12 (Sección A.1)
        // Celdas B12..B19 (Códigos P1206010 al P1206080)
        $sheetP12 = $this->getSheet($spreadsheet, 'P12', 'P-12', 'REM-P12', 'REM-P-12');
        if ($sheetP12 !== null) {
            $papCells = ['B12', 'B13', 'B14', 'B15', 'B16', 'B17', 'B18', 'B19'];
            $valPap = $this->sumCalculatedCells($sheetP12, $papCells);
            if ($valPap === 0.0) {
                $valPap = (float) ($this->getCalculatedCell($sheetP12, 'D10') ?? $this->getCalculatedCell($sheetP12, 'B10') ?? 0);
            }

            if ($valPap > 0) {
                $results['MS-02'] = [
                    'numerador' => $valPap,
                    'denominador' => null, // Población inscrita validada FONASA (25 a 64 años)
                    'fuente' => 'REM-P12 (Sección A.1, B12:B19)',
                    'detalles' => ['pap_tamizaje_vigente_25_64' => $valPap],
                ];
            }
        }

        // MS-04 y MS-05: Hoja REM P4 (Sección B y C)
        $sheetP4 = $this->getSheet($spreadsheet, 'P4', 'P04', 'REM-P4', 'REM-P04');
        if ($sheetP4 !== null) {
            // META IV.A (DM2 Compensadas): HbA1c < 7% (Fila 31 C31) + HbA1c < 8% (Fila 32 C32)
            $dmComp = (float) ($this->getCalculatedCell($sheetP4, 'C31') ?? 0) + (float) ($this->getCalculatedCell($sheetP4, 'C32') ?? 0);
            if ($dmComp === 0.0) {
                $dmComp = (float) ($this->getCalculatedCell($sheetP4, 'C30') ?? 0) + (float) ($this->getCalculatedCell($sheetP4, 'C31') ?? 0);
            }

            // META IV.B (Pie Diabético): Celdas C62..C65 (y C61 si aplica), Denominador: C17
            $pieDiab = $this->sumCalculatedCells($sheetP4, ['C61', 'C62', 'C63', 'C64', 'C65']);
            $dmBajoControl = (float) ($this->getCalculatedCell($sheetP4, 'C17') ?? 0);

            if ($dmComp > 0 || $pieDiab > 0) {
                $results['MS-04'] = [
                    'numerador' => $dmComp > 0 ? $dmComp : $pieDiab,
                    'denominador' => null, // Estimada por prevalencia ENS
                    'fuente' => 'REM-P4 (Sección B y C)',
                    'detalles' => [
                        'dm2_compensada' => $dmComp,
                        'pie_diabetico_evaluado' => $pieDiab,
                        'dm2_bajo_control_c17' => $dmBajoControl,
                    ],
                ];
            }

            // META V (HTA Compensadas): PA < 140/90 (Fila 29 C29) + PA < 150/90 (Fila 30 C30)
            $htaComp = (float) ($this->getCalculatedCell($sheetP4, 'C29') ?? 0) + (float) ($this->getCalculatedCell($sheetP4, 'C30') ?? 0);
            if ($htaComp === 0.0) {
                $htaComp = (float) ($this->getCalculatedCell($sheetP4, 'C28') ?? 0) + (float) ($this->getCalculatedCell($sheetP4, 'C29') ?? 0);
            }

            $htaBajoControl = (float) ($this->getCalculatedCell($sheetP4, 'C16') ?? 0);

            if ($htaComp > 0) {
                $results['MS-05'] = [
                    'numerador' => $htaComp,
                    'denominador' => null, // Estimada por prevalencia ENS
                    'fuente' => 'REM-P4 (Sección B)',
                    'detalles' => [
                        'hta_compensada' => $htaComp,
                        'hta_bajo_control_c16' => $htaBajoControl,
                    ],
                ];
            }

            // IAAPS-14: DM2 Bajo Control (P4 Sección A, C17)
            if ($dmBajoControl > 0) {
                $results['IAAPS-14'] = [
                    'numerador' => $dmBajoControl,
                    'denominador' => null,
                    'fuente' => 'REM-P4 (Sección A, Fila 17)',
                    'detalles' => ['dm2_bajo_control' => $dmBajoControl],
                ];
            }

            // IAAPS-15: HTA Bajo Control (P4 Sección A, C16)
            if ($htaBajoControl > 0) {
                $results['IAAPS-15'] = [
                    'numerador' => $htaBajoControl,
                    'denominador' => null,
                    'fuente' => 'REM-P4 (Sección A, Fila 16)',
                    'detalles' => ['hta_bajo_control' => $htaBajoControl],
                ];
            }
        }

        // MS-07: Cobertura Asma y EPOC (5+ años) -> Hoja REM P3 (Sección D)
        // Asma: H59..AM59 + EPOC: C63
        $sheetP3 = $this->getSheet($spreadsheet, 'P3', 'P03', 'REM-P3', 'REM-P03');
        if ($sheetP3 !== null) {
            $asmaCols = [];
            for ($c = Coordinate::columnIndexFromString('H'); $c <= Coordinate::columnIndexFromString('AM'); $c++) {
                $asmaCols[] = Coordinate::stringFromColumnIndex($c).'59';
            }
            $asmaNum = $this->sumCalculatedCells($sheetP3, $asmaCols);
            $epocNum = (float) ($this->getCalculatedCell($sheetP3, 'C63') ?? 0);
            $totalResp = $asmaNum + $epocNum;

            if ($totalResp > 0) {
                $results['MS-07'] = [
                    'numerador' => $totalResp,
                    'denominador' => null, // Prevalencia nacional
                    'fuente' => 'REM-P3 (Sección D)',
                    'detalles' => [
                        'asma_controlado' => $asmaNum,
                        'epoc_adecuado' => $epocNum,
                    ],
                ];
            }
        }

        // ==========================================
        // 2. INDICADORES IAAPS 2025
        // ==========================================

        $sheetA01 = $this->getSheet($spreadsheet, 'A01', 'A1', 'REM-A01', 'REM-A1');
        $sheetA02 = $this->getSheet($spreadsheet, 'A02', 'A2', 'REM-A02', 'REM-A2');
        $sheetA04 = $this->getSheet($spreadsheet, 'A04', 'A4', 'REM-A04', 'REM-A4');
        $sheetA05 = $this->getSheet($spreadsheet, 'A05', 'A5', 'REM-A05', 'REM-A5');
        $sheetA06 = $this->getSheet($spreadsheet, 'A06', 'A6', 'REM-A06', 'REM-A6');
        $sheetA07 = $this->getSheet($spreadsheet, 'A07', 'A7', 'REM-A07', 'REM-A7');
        $sheetA08 = $this->getSheet($spreadsheet, 'A08', 'A8', 'REM-A08', 'REM-A8');
        $sheetA19 = $this->getSheet($spreadsheet, 'A19a', 'A19A', 'REM-A19a', 'REM-A19A');
        $sheetA23 = $this->getSheet($spreadsheet, 'A23', 'REM-A23');
        $sheetA26 = $this->getSheet($spreadsheet, 'A26', 'REM-A26');
        $sheetA32 = $this->getSheet($spreadsheet, 'A32', 'REM-A32');
        $sheetA33 = $this->getSheet($spreadsheet, 'A33', 'REM-A33');
        $sheetP1 = $this->getSheet($spreadsheet, 'P1', 'P01', 'REM-P1', 'REM-P01');
        $sheetP2 = $this->getSheet($spreadsheet, 'P2', 'P02', 'REM-P2', 'REM-P02');
        $sheetP6 = $this->getSheet($spreadsheet, 'P6', 'P06', 'REM-P6', 'REM-P06');

        // IAAPS-03: Tasa de consultas médicas de morbilidad
        // Numerador: A04:B12 + A07:(Z121+AA121) + A08:B41 + A23:B58 + A32:(B28+B29)
        $a04_b12 = (float) ($this->getCalculatedCell($sheetA04, 'B12') ?? 0);
        $a07_morb = $this->sumCalculatedCells($sheetA07, ['Z121', 'AA121']);
        $a08_b41 = (float) ($this->getCalculatedCell($sheetA08, 'B41') ?? 0);
        $a23_b58 = (float) ($this->getCalculatedCell($sheetA23, 'B58') ?? 0);
        $a32_morb = $this->sumCalculatedCells($sheetA32, ['B28', 'B29']);
        $totalMorb = $a04_b12 + $a07_morb + $a08_b41 + $a23_b58 + $a32_morb;

        if ($totalMorb > 0) {
            $results['IAAPS-03'] = [
                'numerador' => $totalMorb,
                'denominador' => null, // Población inscrita validada FONASA
                'fuente' => 'REM Serie A (A04, A07, A08, A23, A32)',
                'detalles' => [
                    'a04_b12' => $a04_b12,
                    'a07_z121_aa121' => $a07_morb,
                    'a08_b41' => $a08_b41,
                    'a23_b58' => $a23_b58,
                    'a32_b28_b29' => $a32_morb,
                ],
            ];
        }

        // IAAPS-04: Porcentaje de derivación al nivel secundario
        // Numerador: REM A07 (Sección A.1): AE126 + AF126
        // Denominador: A01 + A04 + A06 + A07 + A08 + A23 + A32
        $sicNum = $this->sumCalculatedCells($sheetA07, ['AE126', 'AF126']);
        $denA01 = $this->sumCalculatedCells($sheetA01, [
            'C11', 'C13', 'C15', 'C17', 'C19', 'C21', 'C23', 'C25', 'C27', 'C29', 'C31',
            'C36',
            'C44', 'C48', 'C51', 'C54', 'C57', 'C63',
        ]);
        $denA06 = (float) ($this->getCalculatedCell($sheetA06, 'C12') ?? 0);
        $denA23 = (float) ($this->getCalculatedCell($sheetA23, 'B58') ?? 0) + (float) ($this->getCalculatedCell($sheetA23, 'B63') ?? 0);
        $denA32 = $this->sumCalculatedCells($sheetA32, ['B28', 'B29', 'C130', 'C141']);
        $totalConsultasAPS = $denA01 + $a04_b12 + $denA06 + $a07_morb + $a08_b41 + $denA23 + $denA32;

        if ($sicNum > 0 || $totalConsultasAPS > 0) {
            $results['IAAPS-04'] = [
                'numerador' => $sicNum > 0 ? $sicNum : null,
                'denominador' => $totalConsultasAPS > 0 ? $totalConsultasAPS : null,
                'fuente' => 'REM Serie A (A07 / A01, A04, A06, A08, A23, A32)',
                'detalles' => [
                    'sic_derivaciones' => $sicNum,
                    'total_consultas_aps' => $totalConsultasAPS,
                ],
            ];
        }

        // IAAPS-05: Tasa de visita domiciliaria integral
        // Numerador: REM A26 (C10..C35 + C41..F41) + REM A33 (C39 + C40)
        $a26Coords = [];
        for ($r = 10; $r <= 35; $r++) {
            $a26Coords[] = "C{$r}";
        }
        $vdiA26SecA = $this->sumCalculatedCells($sheetA26, $a26Coords);
        $vdiA26SecA1 = $this->sumCalculatedCells($sheetA26, ['C41', 'D41', 'E41', 'F41']);
        $vdiA33 = $this->sumCalculatedCells($sheetA33, ['C39', 'C40']);
        $totalVdi = $vdiA26SecA + $vdiA26SecA1 + $vdiA33;

        if ($totalVdi > 0) {
            $results['IAAPS-05'] = [
                'numerador' => $totalVdi,
                'denominador' => null, // Se calcula con pobTotal / 3.3
                'fuente' => 'REM-A26 y REM-A33',
                'detalles' => [
                    'vdi_a26_sec_a' => $vdiA26SecA,
                    'vdi_a26_sec_a1' => $vdiA26SecA1,
                    'vdi_a33' => $vdiA33,
                ],
            ];
        }

        // IAAPS-06.1A, 06.1B y 06.2: Cobertura EMP
        // 6.1A Num: A02 Sección B (H21+I21+L21+N21+P21+R21+T21+V21+X21)
        // Gestantes P1: B46..B52
        // 6.1B Num: A02 Sección B (G21+I21+K21+M21+O21+Q21+S21+U21+W21)
        // 6.2 Num: A02 Sección B (Y21..AF21)
        if ($sheetA02 !== null) {
            $empMujeres2064 = $this->sumCalculatedCells($sheetA02, ['H21', 'I21', 'L21', 'N21', 'P21', 'R21', 'T21', 'V21', 'X21']);
            $empHombres2064 = $this->sumCalculatedCells($sheetA02, ['G21', 'I21', 'K21', 'M21', 'O21', 'Q21', 'S21', 'U21', 'W21']);
            $empam65Mas = $this->sumCalculatedCells($sheetA02, ['Y21', 'Z21', 'AA21', 'AB21', 'AC21', 'AD21', 'AE21', 'AF21']);

            // Fallback total sección A si la fila 21 no tuviera datos
            if ($empMujeres2064 === 0.0) {
                $empMujeres2064 = (float) ($this->getCalculatedCell($sheetA02, 'D11') ?? 0);
            }
            if ($empHombres2064 === 0.0) {
                $empHombres2064 = (float) ($this->getCalculatedCell($sheetA02, 'C11') ?? 0);
            }

            $gestantesP1 = $this->sumCalculatedCells($sheetP1, ['B46', 'B47', 'B48', 'B49', 'B50', 'B51', 'B52']);

            if ($empMujeres2064 > 0) {
                $results['IAAPS-06.1A'] = [
                    'numerador' => $empMujeres2064,
                    'denominador' => null,
                    'fuente' => 'REM-A02 (Sección B)',
                    'detalles' => [
                        'emp_mujeres_20_64' => $empMujeres2064,
                        'gestantes_p1' => $gestantesP1,
                    ],
                ];
            }

            if ($empHombres2064 > 0) {
                $results['IAAPS-06.1B'] = [
                    'numerador' => $empHombres2064,
                    'denominador' => null,
                    'fuente' => 'REM-A02 (Sección B)',
                    'detalles' => ['emp_hombres_20_64' => $empHombres2064],
                ];
            }

            if ($empam65Mas > 0) {
                $results['IAAPS-06.2'] = [
                    'numerador' => $empam65Mas,
                    'denominador' => null,
                    'fuente' => 'REM-A02 (Sección B, Y21:AF21)',
                    'detalles' => ['empam_65_mas' => $empam65Mas],
                ];
            }
        }

        // IAAPS-07: Cobertura DSM niños(as) 12 a 23 meses
        // Numerador: A03 Sección A.2 (J21..M21 + J22..M22 + J23..M23 + J24..M24)
        // Denominador: P2 Sección A (V12 + W12 + X12 + Y12)
        if ($sheetA03 !== null) {
            $dsmEvalCells = [];
            for ($r = 21; $r <= 24; $r++) {
                foreach (['J', 'K', 'L', 'M'] as $col) {
                    $dsmEvalCells[] = "{$col}{$r}";
                }
            }
            $dsmEvaluados = $this->sumCalculatedCells($sheetA03, $dsmEvalCells);
            $pob1223Control = $this->sumCalculatedCells($sheetP2, ['V12', 'W12', 'X12', 'Y12']);

            if ($dsmEvaluados > 0 || $pob1223Control > 0) {
                $results['IAAPS-07'] = [
                    'numerador' => $dsmEvaluados > 0 ? $dsmEvaluados : null,
                    'denominador' => $pob1223Control > 0 ? $pob1223Control : null,
                    'fuente' => 'REM-A03 (Sección A.2) / REM-P02 (Sección A)',
                    'detalles' => [
                        'dsm_evaluados_12_23m' => $dsmEvaluados,
                        'ninos_12_23m_control' => $pob1223Control,
                    ],
                ];
            }
        }

        // IAAPS-08: Salud Integral Adolescentes 10 a 19 años -> Hoja REM-A01 (Sección D)
        // Celdas C74 + F74
        if ($sheetA01 !== null) {
            $saludAdol = $this->sumCalculatedCells($sheetA01, ['C74', 'F74']);
            if ($saludAdol > 0) {
                $results['IAAPS-08'] = [
                    'numerador' => $saludAdol,
                    'denominador' => null, // Población 10 a 19 años FONASA
                    'fuente' => 'REM-A01 (Sección D, C74+F74)',
                    'detalles' => ['controles_salud_adolescente' => $saludAdol],
                ];
            }
        }

        // IAAPS-09.1, 09.2, 09.3: Salud Mental
        // Población bajo control = P6:C13 + A05:C193 - A05:(C245 + AN245 + AO245 + AP245)
        $smP6 = (float) ($this->getCalculatedCell($sheetP6, 'C13') ?? 0);
        $smA05Ingresos = (float) ($this->getCalculatedCell($sheetA05, 'C193') ?? 0);
        $smA05Egresos = $this->sumCalculatedCells($sheetA05, ['C245', 'AN245', 'AO245', 'AP245']);
        $smPoblacionControl = max(0.0, $smP6 + $smA05Ingresos - $smA05Egresos);

        // 9.2: Controles de salud mental
        $controlesSM = $this->sumCalculatedCells($sheetA06, ['C22', 'C23'])
            + (float) ($this->getCalculatedCell($sheetA06, 'E32') ?? 0)
            + $this->sumCalculatedCells($sheetA19, ['C110', 'C112'])
            + $this->sumCalculatedCells($sheetA26, ['C30', 'C31', 'C38', 'D38', 'E38', 'F38'])
            + $this->sumCalculatedCells($sheetA32, ['B123', 'B124', 'B125', 'C140', 'C151'])
            + (float) ($this->getCalculatedCell($sheetA04, 'B24') ?? 0);

        // 9.3: Altas clínicas
        $altasClinicas = (float) ($this->getCalculatedCell($sheetA05, 'C245') ?? 0);

        if ($smPoblacionControl > 0 || $controlesSM > 0) {
            $results['IAAPS-09.1'] = [
                'numerador' => $smPoblacionControl > 0 ? $smPoblacionControl : null,
                'denominador' => null, // 22% prevalencia referencial
                'fuente' => 'REM-P06 (A.1) / REM-A05 (N y O)',
                'detalles' => [
                    'pob_control_sm' => $smPoblacionControl,
                    'p6_c13' => $smP6,
                    'a05_ingresos' => $smA05Ingresos,
                    'a05_egresos' => $smA05Egresos,
                ],
            ];

            $results['IAAPS-09.2'] = [
                'numerador' => $controlesSM > 0 ? $controlesSM : null,
                'denominador' => $smPoblacionControl > 0 ? $smPoblacionControl : null,
                'fuente' => 'REM Serie A (A06, A19a, A26, A32, A04)',
                'detalles' => [
                    'controles_totales' => $controlesSM,
                    'pob_control_sm' => $smPoblacionControl,
                ],
            ];

            $results['IAAPS-09.3'] = [
                'numerador' => $altasClinicas,
                'denominador' => $smPoblacionControl > 0 ? $smPoblacionControl : null,
                'fuente' => 'REM-A05 (Sección O)',
                'detalles' => [
                    'altas_clinicas' => $altasClinicas,
                    'pob_control_sm' => $smPoblacionControl,
                ],
            ];
        }

        // IAAPS-12: Ingreso precoz de mujeres a control de embarazo antes de las 14 semanas -> Hoja A05
        if ($sheetA05 !== null) {
            $gestAntes14 = $this->getCalculatedCell($sheetA05, 'C13');
            $gestTotal = $this->getCalculatedCell($sheetA05, 'C11');

            if ($gestTotal !== null && $gestTotal > 0) {
                $results['IAAPS-12'] = [
                    'numerador' => $gestAntes14 ?? 0.0,
                    'denominador' => $gestTotal,
                    'fuente' => 'REM-A05 (Sección A, C11 y C13)',
                    'detalles' => [
                        'ingresadas_antes_14_sem' => $gestAntes14 ?? 0.0,
                        'total_gestantes_ingresadas' => $gestTotal,
                    ],
                ];
            }
        }

        // IAAPS-13: Regulación de fertilidad en adolescentes de 15 a 19 años -> Hoja REM-P01 (Sección A)
        if ($sheetP1 !== null) {
            $fertAdol = $this->getCalculatedCell($sheetP1, 'E25');
            if ($fertAdol !== null && $fertAdol > 0) {
                $results['IAAPS-13'] = [
                    'numerador' => $fertAdol,
                    'denominador' => null, // Población 15 a 19 años FONASA
                    'fuente' => 'REM-P01 (Sección A, E25)',
                    'detalles' => ['regulacion_fertilidad_15_19' => $fertAdol],
                ];
            }
        }

        // IAAPS-16: Libre de caries en menores de 3 años -> Hoja REM-A09 (Sección C)
        // Celdas G51 + H51 + I51 + J51 + K51 + L51
        if ($sheetA09 !== null) {
            $ceod0Menores3 = $this->sumCalculatedCells($sheetA09, ['G51', 'H51', 'I51', 'J51', 'K51', 'L51']);
            if ($ceod0Menores3 > 0) {
                $results['IAAPS-16'] = [
                    'numerador' => $ceod0Menores3,
                    'denominador' => null, // Población menores de 3 años FONASA
                    'fuente' => 'REM-A09 (Sección C, G51:L51)',
                    'detalles' => ['libres_caries_menores_3' => $ceod0Menores3],
                ];
            }
        }

        // IAAPS-17: Prevalencia normalidad en menores de 2 años bajo control -> Hoja REM-P02 (Sección A)
        // Numerador: H36..Y36 (18 columnas de normalidad nutricional)
        // Denominador: H12..Y12 (18 columnas de población bajo control)
        if ($sheetP2 !== null) {
            $nutrCols = [];
            for ($c = Coordinate::columnIndexFromString('H'); $c <= Coordinate::columnIndexFromString('Y'); $c++) {
                $colL = Coordinate::stringFromColumnIndex($c);
                $nutrCols[] = $colL;
            }
            $numCoords = array_map(fn ($col) => "{$col}36", $nutrCols);
            $denCoords = array_map(fn ($col) => "{$col}12", $nutrCols);

            $nutrNormal = $this->sumCalculatedCells($sheetP2, $numCoords);
            $nutrTotal = $this->sumCalculatedCells($sheetP2, $denCoords);

            // Fallback C36 y C12
            if ($nutrNormal === 0.0) {
                $nutrNormal = (float) ($this->getCalculatedCell($sheetP2, 'C36') ?? 0);
            }
            if ($nutrTotal === 0.0) {
                $nutrTotal = (float) ($this->getCalculatedCell($sheetP2, 'C12') ?? 0);
            }

            if ($nutrNormal > 0 && $nutrTotal > 0) {
                $results['IAAPS-17'] = [
                    'numerador' => $nutrNormal,
                    'denominador' => $nutrTotal,
                    'fuente' => 'REM-P02 (Sección A, H36:Y36 / H12:Y12)',
                    'detalles' => [
                        'normal_menor_2a' => $nutrNormal,
                        'total_menor_2a' => $nutrTotal,
                    ],
                ];
            }
        }

        // ==========================================
        // 3. APLICACIÓN DE MAPEOS PERSONALIZADOS (Panel de Administración)
        // ==========================================
        try {
            $customMappings = RemMapping::where('is_active', true)->with('indicador')->get();
            foreach ($customMappings as $mapping) {
                if (! $mapping->indicador) {
                    continue;
                }

                $sheet = $this->getSheet($spreadsheet, $mapping->hoja, 'REM-'.$mapping->hoja, 'REM '.$mapping->hoja);
                if ($sheet !== null) {
                    $customNum = $this->evaluateCellExpression($sheet, $mapping->celda_numerador);
                    if ($customNum !== null) {
                        $customDen = ! empty($mapping->celda_denominador)
                            ? $this->evaluateCellExpression($sheet, $mapping->celda_denominador)
                            : ($results[$mapping->indicador->codigo]['denominador'] ?? null);

                        $results[$mapping->indicador->codigo] = [
                            'numerador' => $customNum,
                            'denominador' => $customDen,
                            'fuente' => 'REM-'.$mapping->hoja.($mapping->seccion ? " ({$mapping->seccion})" : ''),
                            'detalles' => [
                                'mapeo_personalizado' => true,
                                'celda_numerador' => $mapping->celda_numerador,
                                'celda_denominador' => $mapping->celda_denominador,
                            ],
                        ];
                    }
                }
            }
        } catch (\Throwable) {
            // Continuar con los resultados estándar si la base de datos no está disponible
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
            if ($data === null || $data['numerador'] === null || $data['numerador'] <= 0) {
                $pending[] = [
                    'meta_codigo' => 'MS-01',
                    'input_key' => 'numerador_ms_01',
                    'title' => 'Meta I: Desarrollo Psicomotor (Numerador)',
                    'text' => "Ingrese el N° de niños(as) de 12 a 23 meses diagnosticados con riesgo recuperados ({$año}):",
                    'default_value' => null,
                ];
            }

            if ($data === null || $data['denominador'] === null || $data['denominador'] <= 0) {
                $pending[] = [
                    'meta_codigo' => 'MS-01',
                    'input_key' => 'dsm_denominador_riesgo',
                    'title' => 'Meta I: Desarrollo Psicomotor (Denominador)',
                    'text' => "Ingrese el N° de niños(as) de 12 a 23 meses diagnosticados con riesgo en 1ª evaluación ({$año}):",
                    'default_value' => null,
                ];
            }
        }

        // MS-02: PAP
        if (isset($indicadores['MS-02'])) {
            $data = $extracted['MS-02'] ?? null;
            if ($data === null || $data['numerador'] === null || $data['numerador'] <= 0) {
                $pending[] = [
                    'meta_codigo' => 'MS-02',
                    'input_key' => 'numerador_pap_vigente',
                    'title' => 'Meta II: Cáncer Cuello Uterino (Numerador)',
                    'text' => "Ingrese el N° de personas de 25 a 64 años inscritas con PAP o VPH vigente ({$año}):",
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
            $data = $extracted['MS-03'] ?? null;
            if ($data === null || $data['numerador'] === null || $data['numerador'] <= 0) {
                $pending[] = [
                    'meta_codigo' => 'MS-03',
                    'input_key' => 'numerador_ms_03',
                    'title' => 'Meta III: Salud Bucal (Pauta CERO Numerador)',
                    'text' => "Ingrese el N° de niños(as) de 0 a 9 años en control con pauta CERO aplicada ({$año}):",
                    'default_value' => null,
                ];
            }

            $defFonasa09 = $pobTotal > 0 ? (float) round($pobTotal * 0.12) : null;
            $pending[] = [
                'meta_codigo' => 'MS-03',
                'input_key' => 'poblacion_fonasa_0_9',
                'title' => 'Meta III: Salud Bucal (Pauta CERO Denominador)',
                'text' => "Ingrese la población de 0 a 9 años inscrita y validada por FONASA ({$año}):",
                'default_value' => $defFonasa09,
            ];
        }

        // MS-04: DM2 Compensada
        if (isset($indicadores['MS-04'])) {
            $data = $extracted['MS-04'] ?? null;
            if ($data === null || $data['numerador'] === null || $data['numerador'] <= 0) {
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
            if ($data === null || $data['numerador'] === null || $data['numerador'] <= 0) {
                $pending[] = [
                    'meta_codigo' => 'MS-05',
                    'input_key' => 'numerador_hta_compensada',
                    'title' => 'Meta V: Hipertensión Arterial (Numerador)',
                    'text' => "Ingrese el N° de personas de 15 años y más con HTA con PA bajo meta (<140/90 o <150/90) ({$año}):",
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
            if ($data === null || $data['numerador'] === null || $data['numerador'] <= 0) {
                $pending[] = [
                    'meta_codigo' => 'MS-06',
                    'input_key' => 'numerador_lme_6m',
                    'title' => 'Meta VI: Lactancia Materna Exclusiva (Numerador)',
                    'text' => "Ingrese el N° de lactantes con lactancia materna exclusiva al 6° mes ({$año}):",
                    'default_value' => null,
                ];
            }

            if ($data === null || $data['denominador'] === null || $data['denominador'] <= 0) {
                $pending[] = [
                    'meta_codigo' => 'MS-06',
                    'input_key' => 'denominador_control_6m',
                    'title' => 'Meta VI: Lactancia Materna Exclusiva (Denominador)',
                    'text' => "Ingrese el N° total de lactantes con control de salud al 6° mes ({$año}):",
                    'default_value' => null,
                ];
            }
        }

        // MS-07: Asma y EPOC
        if (isset($indicadores['MS-07'])) {
            $data = $extracted['MS-07'] ?? null;
            if ($data === null || $data['numerador'] === null || $data['numerador'] <= 0) {
                $pending[] = [
                    'meta_codigo' => 'MS-07',
                    'input_key' => 'numerador_asma_epoc',
                    'title' => 'Meta VII: Asma y EPOC (Numerador)',
                    'text' => "Ingrese el N° de personas con Asma (controladas) y EPOC (adecuadas) ({$año}):",
                    'default_value' => null,
                ];
            }

            $defResp = $pobTotal > 0 ? (float) round(($pobTotal * 0.40 * 0.117) + ($pobTotal * 0.90 * 0.10)) : null;
            $pending[] = [
                'meta_codigo' => 'MS-07',
                'input_key' => 'poblacion_estimada_asma_epoc',
                'title' => 'Meta VII: Asma y EPOC (Denominador Prevalencia)',
                'text' => "Ingrese la población esperada con Asma y EPOC según prevalencia ({$año}):",
                'default_value' => $defResp,
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
            $data = $extracted['IAAPS-04'] ?? null;
            if ($data === null || $data['numerador'] === null || $data['numerador'] <= 0) {
                $pending[] = [
                    'meta_codigo' => 'IAAPS-04',
                    'input_key' => 'iaaps_04_sic',
                    'title' => 'IAAPS META 4: Derivación al Nivel Secundario',
                    'text' => 'Ingrese el N° de solicitudes de interconsulta (SIC) médicas generadas en APS:',
                    'default_value' => 120.0,
                ];
            }
            if ($data === null || $data['denominador'] === null || $data['denominador'] <= 0) {
                $pending[] = [
                    'meta_codigo' => 'IAAPS-04',
                    'input_key' => 'iaaps_04_total_consultas',
                    'title' => 'IAAPS META 4: Total Consultas y Atenciones APS',
                    'text' => 'Total de consultas y atenciones médicas realizadas en APS:',
                    'default_value' => 1296.0,
                ];
            }
        }

        // IAAPS-05: Visitas Domiciliarias Integrales (VDI)
        if (isset($indicadores['IAAPS-05'])) {
            $data = $extracted['IAAPS-05'] ?? null;
            if ($data === null || $data['numerador'] === null || $data['numerador'] <= 0) {
                $pending[] = [
                    'meta_codigo' => 'IAAPS-05',
                    'input_key' => 'iaaps_05_vdi',
                    'title' => 'IAAPS META 5: Tasa de Visita Domiciliaria Integral',
                    'text' => 'Ingrese el N° de Visitas Domiciliarias Integrales (VDI) realizadas:',
                    'default_value' => 150.0,
                ];
            }
        }

        // IAAPS-06.1A: EMP Mujeres (Denominador)
        if (isset($indicadores['IAAPS-06.1A'])) {
            $data = $extracted['IAAPS-06.1A'] ?? null;
            if ($data === null || $data['numerador'] === null || $data['numerador'] <= 0) {
                $pending[] = [
                    'meta_codigo' => 'IAAPS-06.1A',
                    'input_key' => 'iaaps_06_1a_num',
                    'title' => 'IAAPS META 6.1.A: EMP Mujeres (Numerador)',
                    'text' => 'N° de EMP realizado en mujeres de 20 a 64 años:',
                    'default_value' => 100.0,
                ];
            }
            $gestantes = (float) ($data['detalles']['gestantes_p1'] ?? 0);
            $defMujeres = $pobTotal > 0 ? (float) max(1, round($pobTotal * 0.31) - $gestantes) : null;
            $pending[] = [
                'meta_codigo' => 'IAAPS-06.1A',
                'input_key' => 'iaaps_06_1a_den',
                'title' => 'IAAPS META 6.1.A: EMP Mujeres (Denominador)',
                'text' => 'Población mujeres 20-64 años inscrita validada (menos gestantes):',
                'default_value' => $defMujeres,
            ];
        }

        // IAAPS-06.1B: EMP Hombres (Denominador)
        if (isset($indicadores['IAAPS-06.1B'])) {
            $data = $extracted['IAAPS-06.1B'] ?? null;
            if ($data === null || $data['numerador'] === null || $data['numerador'] <= 0) {
                $pending[] = [
                    'meta_codigo' => 'IAAPS-06.1B',
                    'input_key' => 'iaaps_06_1b_num',
                    'title' => 'IAAPS META 6.1.B: EMP Hombres (Numerador)',
                    'text' => 'N° de EMP realizado en hombres de 20 a 64 años:',
                    'default_value' => 100.0,
                ];
            }
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
            $data = $extracted['IAAPS-06.2'] ?? null;
            if ($data === null || $data['numerador'] === null || $data['numerador'] <= 0) {
                $pending[] = [
                    'meta_codigo' => 'IAAPS-06.2',
                    'input_key' => 'iaaps_06_2_num',
                    'title' => 'IAAPS META 6.2: Examen de Medicina Preventiva Adulto Mayor (EMPAM)',
                    'text' => 'N° de adultos de 65 y más años con EMP realizado:',
                    'default_value' => 500.0,
                ];
            }
            $def65 = $pobTotal > 0 ? (float) round($pobTotal * 0.14) : null;
            $pending[] = [
                'meta_codigo' => 'IAAPS-06.2',
                'input_key' => 'iaaps_06_2_den',
                'title' => 'IAAPS META 6.2: Población 65 y más años',
                'text' => 'Población inscrita validada de 65 años y más:',
                'default_value' => $def65,
            ];
        }

        // IAAPS-07: DSM bajo control
        if (isset($indicadores['IAAPS-07'])) {
            $data = $extracted['IAAPS-07'] ?? null;
            if ($data === null || $data['numerador'] === null || $data['numerador'] <= 0) {
                $pending[] = [
                    'meta_codigo' => 'IAAPS-07',
                    'input_key' => 'iaaps_07_num',
                    'title' => 'IAAPS META 7: Niños(as) 12 a 23 meses evaluados DSM',
                    'text' => 'N° de niños(as) de 12 a 23 meses con evaluación DSM realizada:',
                    'default_value' => null,
                ];
            }
            if ($data === null || $data['denominador'] === null || $data['denominador'] <= 0) {
                $defNinos1223 = $pobTotal > 0 ? (float) round($pobTotal * 0.02) : null;
                $pending[] = [
                    'meta_codigo' => 'IAAPS-07',
                    'input_key' => 'iaaps_07_den',
                    'title' => 'IAAPS META 7: Niños(as) 12 a 23 meses bajo control',
                    'text' => 'N° total de niños y niñas de 12 a 23 meses bajo control:',
                    'default_value' => $defNinos1223,
                ];
            }
        }

        // IAAPS-08: Control integral adolescentes 10 a 19 años
        if (isset($indicadores['IAAPS-08'])) {
            $data = $extracted['IAAPS-08'] ?? null;
            if ($data === null || $data['numerador'] === null || $data['numerador'] <= 0) {
                $pending[] = [
                    'meta_codigo' => 'IAAPS-08',
                    'input_key' => 'iaaps_08_num',
                    'title' => 'IAAPS META 8: Control de Salud Integral Adolescente',
                    'text' => 'N° de controles de salud integral realizados a adolescentes de 10 a 19 años:',
                    'default_value' => 300.0,
                ];
            }
            $defAdol1019 = $pobTotal > 0 ? (float) round($pobTotal * 0.13) : null;
            $pending[] = [
                'meta_codigo' => 'IAAPS-08',
                'input_key' => 'iaaps_08_den',
                'title' => 'IAAPS META 8: Población Adolescente 10 a 19 años',
                'text' => 'Población total de adolescentes de 10 a 19 años inscrita validada:',
                'default_value' => $defAdol1019,
            ];
        }

        // IAAPS-09.1, 09.2, 09.3: Salud mental
        if (isset($indicadores['IAAPS-09.1'])) {
            $data = $extracted['IAAPS-09.1'] ?? null;
            if ($data === null || $data['numerador'] === null || $data['numerador'] <= 0) {
                $pending[] = [
                    'meta_codigo' => 'IAAPS-09.1',
                    'input_key' => 'iaaps_09_1_num',
                    'title' => 'IAAPS META 9.1: Población Bajo Control en Salud Mental',
                    'text' => 'N° de personas bajo control en salud mental:',
                    'default_value' => round($pobTotal * 0.05),
                ];
            }
            $defPrevalenciaSM = $pobTotal > 0 ? (float) round($pobTotal * 0.22) : null;
            $pending[] = [
                'meta_codigo' => 'IAAPS-09.1',
                'input_key' => 'iaaps_09_1_den',
                'title' => 'IAAPS META 9.1: Prevalencia Referencial Salud Mental (22%)',
                'text' => 'Población estimada según prevalencia (22% población total):',
                'default_value' => $defPrevalenciaSM,
            ];
        }

        if (isset($indicadores['IAAPS-09.2'])) {
            $data = $extracted['IAAPS-09.2'] ?? null;
            if ($data === null || $data['numerador'] === null || $data['numerador'] <= 0) {
                $pending[] = [
                    'meta_codigo' => 'IAAPS-09.2',
                    'input_key' => 'iaaps_09_2_num',
                    'title' => 'IAAPS META 9.2: Controles de Salud Mental',
                    'text' => 'N° total de controles integrales de salud mental realizados:',
                    'default_value' => 200.0,
                ];
            }
            if ($data === null || $data['denominador'] === null || $data['denominador'] <= 0) {
                $pending[] = [
                    'meta_codigo' => 'IAAPS-09.2',
                    'input_key' => 'iaaps_09_2_den',
                    'title' => 'IAAPS META 9.2: Población Bajo Control en Salud Mental',
                    'text' => 'Población bajo control en salud mental:',
                    'default_value' => 100.0,
                ];
            }
        }

        if (isset($indicadores['IAAPS-09.3'])) {
            $data = $extracted['IAAPS-09.3'] ?? null;
            if ($data === null || $data['numerador'] === null) {
                $pending[] = [
                    'meta_codigo' => 'IAAPS-09.3',
                    'input_key' => 'iaaps_09_3_num',
                    'title' => 'IAAPS META 9.3: Altas Clínicas de Salud Mental',
                    'text' => 'N° de personas egresadas por alta clínica en salud mental:',
                    'default_value' => 10.0,
                ];
            }
            if ($data === null || $data['denominador'] === null || $data['denominador'] <= 0) {
                $pending[] = [
                    'meta_codigo' => 'IAAPS-09.3',
                    'input_key' => 'iaaps_09_3_den',
                    'title' => 'IAAPS META 9.3: Población Bajo Control en Salud Mental',
                    'text' => 'Población bajo control en salud mental:',
                    'default_value' => 100.0,
                ];
            }
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
            $data = $extracted['IAAPS-13'] ?? null;
            if ($data === null || $data['numerador'] === null || $data['numerador'] <= 0) {
                $pending[] = [
                    'meta_codigo' => 'IAAPS-13',
                    'input_key' => 'iaaps_13_num',
                    'title' => 'IAAPS META 13: Regulación Fertilidad Adolescente',
                    'text' => 'N° de adolescentes de 15 a 19 años con método de regulación de fertilidad:',
                    'default_value' => null,
                ];
            }
            $defAdol1519 = $pobTotal > 0 ? (float) round($pobTotal * 0.07) : null;
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
            $data = $extracted['IAAPS-16'] ?? null;
            if ($data === null || $data['numerador'] === null || $data['numerador'] <= 0) {
                $pending[] = [
                    'meta_codigo' => 'IAAPS-16',
                    'input_key' => 'iaaps_16_num',
                    'title' => 'IAAPS META 16: Libre de Caries Menores de 3 años (CEOD = 0)',
                    'text' => 'N° de niños y niñas menores de 3 años con registro CEOD = 0:',
                    'default_value' => null,
                ];
            }
            $defNinos03 = $pobTotal > 0 ? (float) round($pobTotal * 0.035) : null;
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
