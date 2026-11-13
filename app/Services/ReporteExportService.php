<?php

namespace App\Services;

use App\Models\Alerta;
use App\Models\IndicadorMedida;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Construye los libros de cálculo (Excel/PDF) de los reportes exportables del Módulo 6.
 *
 * Genera tres reportes: mensual de cumplimiento, trimestral con proyección y bandeja de alertas.
 * Los libros se generan con PhpSpreadsheet y pueden descargarse en .xlsx o .pdf (renderer Dompdf).
 */
class ReporteExportService
{
    /** Color de marca (brand-950) para títulos y cabeceras. */
    private const COLOR_MARCA = '0F1424';

    /** Fondo de las filas alternadas. */
    private const COLOR_FILA_ALTERNADA = 'F1F5F9';

    /** Nombres en español de los 12 meses. */
    private const MESES_NOMBRES = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
        7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    public function __construct(private readonly ProyeccionEstadisticaService $proyeccion) {}

    /**
     * Reporte mensual: mediciones del período (numerador, denominador, % cumplimiento, meta y estado).
     *
     * @param  string|null  $tipo  Filtrar por tipo de indicador ('Meta Sanitaria' o 'IAAPS').
     */
    public function generarMensual(int $ano, int $mes, ?int $establecimientoId = null, ?string $tipo = null): Spreadsheet
    {
        $medidas = IndicadorMedida::with(['indicador', 'establecimiento'])
            ->where('ano', $ano)
            ->where('mes', $mes)
            ->when($establecimientoId, fn ($q) => $q->where('establecimientos_id', $establecimientoId))
            ->when($tipo !== null && $tipo !== 'todas', fn ($q) => $q->whereHas('indicador', fn ($i) => $i->where('tipo', $tipo)))
            ->orderBy('establecimientos_id')
            ->orderBy('indicadores_id')
            ->get();

        $spreadsheet = $this->nuevoLibro('Reporte Mensual de Cumplimiento');
        $hoja = $spreadsheet->getActiveSheet();

        $this->titulo($hoja, 'Reporte Mensual de Cumplimiento', "Período: {$this->mesNombre($mes)} {$ano}", 9);
        $this->encabezados($hoja, [
            'Código', 'Indicador', 'Tipo', 'Establecimiento', 'Numerador', 'Denominador', '% Cumplimiento', 'Meta', 'Estado',
        ]);

        $fila = 4;
        foreach ($medidas as $medida) {
            $this->fila($hoja, $fila++, [
                $medida->indicador?->codigo,
                $medida->indicador?->nombre,
                $medida->indicador?->tipo,
                $medida->establecimiento?->nombre,
                (float) $medida->numerador_value,
                (float) $medida->denominador_value,
                (float) $medida->porcentaje_cumplimiento,
                (float) ($medida->indicador?->target_value ?? 0),
                $medida->status,
            ], [
                5 => '#,##0.##',
                6 => '#,##0.##',
                7 => '0.0',
                8 => '0.0',
            ]);
        }

        $this->anchos($hoja, [12, 50, 16, 38, 14, 14, 16, 10, 14]);

        return $spreadsheet;
    }

    /**
     * Reporte trimestral: proyección a diciembre de cada indicador con su estado anticipado.
     */
    public function generarTrimestral(int $ano, int $trimestre, ?int $establecimientoId = null, ?string $tipo = null): Spreadsheet
    {
        $mesCorte = min(12, $trimestre * 3);
        $resultado = $this->proyeccion->obtenerProyeccionesDashboard(
            $ano,
            $mesCorte,
            $establecimientoId,
            $tipo,
            null,
            null,
        );

        $resumen = $resultado['resumen'];
        $proyecciones = $resultado['indicadores_proyectados'];

        $spreadsheet = $this->nuevoLibro('Reporte Trimestral de Proyección');
        $hoja = $spreadsheet->getActiveSheet();

        $subtitulo = sprintf(
            'Trimestre %d de %d · Evaluados: %d · Cumplirán: %d · En riesgo: %d · Críticos: %d',
            $trimestre,
            $ano,
            $resumen['total_evaluados'],
            $resumen['cumpliran_count'],
            $resumen['en_riesgo_count'],
            $resumen['criticos_count'],
        );

        $this->titulo($hoja, 'Reporte Trimestral de Proyección', $subtitulo, 9);
        $this->encabezados($hoja, [
            'Código', 'Indicador', 'Tipo', 'Último mes', 'Cumplimiento actual', 'Proyección a dic.', 'Meta', 'Estado anticipado', 'Confianza',
        ]);

        $fila = 4;
        foreach ($proyecciones as $p) {
            $this->fila($hoja, $fila++, [
                $p['codigo'],
                $p['nombre'],
                $p['tipo'],
                $this->mesNombre((int) $p['ultimo_mes_registrado']),
                (float) $p['cumplimiento_actual'],
                (float) $p['proyeccion_anual'],
                (float) $p['target'],
                $p['estado_anticipado'],
                (float) $p['confianza_modelo'],
            ], [
                5 => '0.0',
                6 => '0.0',
                7 => '0.0',
                9 => '0.0',
            ]);
        }

        $this->anchos($hoja, [12, 50, 16, 14, 18, 18, 10, 18, 12]);

        return $spreadsheet;
    }

    /**
     * Reporte de alertas: bandeja del período con regla, severidad, mensaje, brecha y estado.
     */
    public function generarAlertas(int $ano, int $mes, ?string $severidad = null): Spreadsheet
    {
        $alertas = Alerta::query()
            ->porSeveridad()
            ->with(['regla', 'indicador', 'establecimiento'])
            ->where('alertas.ano', $ano)
            ->where('alertas.mes', $mes)
            ->when($severidad !== null && $severidad !== 'todas', fn ($q) => $q->where('alerta_reglas.severidad', $severidad))
            ->get();

        $spreadsheet = $this->nuevoLibro('Reporte de Alertas');
        $hoja = $spreadsheet->getActiveSheet();

        $this->titulo($hoja, 'Reporte de Alertas', "Período: {$this->mesNombre($mes)} {$ano}", 7);
        $this->encabezados($hoja, [
            'Establecimiento', 'Indicador', 'Regla', 'Severidad', 'Mensaje', 'Brecha', 'Estado',
        ]);

        $fila = 4;
        foreach ($alertas as $alerta) {
            $this->fila($hoja, $fila++, [
                $alerta->establecimiento?->nombre,
                $alerta->indicador?->codigo,
                $alerta->regla?->codigo,
                $alerta->regla?->severidad,
                $alerta->mensaje,
                $alerta->brecha_detectada !== null ? (float) $alerta->brecha_detectada : null,
                $alerta->estado,
            ], [
                6 => '0.0',
            ]);
        }

        $this->anchos($hoja, [34, 14, 12, 14, 70, 12, 14]);

        return $spreadsheet;
    }

    // ---------------------------------------------------------------------------------------------
    // Construcción de libros y estilos
    // ---------------------------------------------------------------------------------------------

    /**
     * Crea un libro con los metadatos del documento.
     */
    private function nuevoLibro(string $titulo): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setCreator('DAS Talcahuano')
            ->setTitle($titulo)
            ->setSubject($titulo);

        $spreadsheet->getActiveSheet()->setTitle('Reporte');

        return $spreadsheet;
    }

    /**
     * Escribe el título (fila 1) y el subtítulo (fila 2), fusionados a lo ancho de la tabla.
     */
    private function titulo(Worksheet $hoja, string $titulo, string $subtitulo, int $columnas): void
    {
        $ultima = Coordinate::stringFromColumnIndex($columnas);

        $hoja->setCellValue('A1', $titulo);
        $hoja->mergeCells("A1:{$ultima}1");
        $hoja->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB(self::COLOR_MARCA);

        $hoja->setCellValue('A2', $subtitulo);
        $hoja->mergeCells("A2:{$ultima}2");
        $hoja->getStyle('A2')->getFont()->setSize(10)->getColor()->setRGB('64748B');

        $hoja->getRowDimension(1)->setRowHeight(24);
        $hoja->getRowDimension(2)->setRowHeight(18);
    }

    /**
     * Escribe la fila de encabezados (fila 3) con el estilo de la marca.
     */
    private function encabezados(Worksheet $hoja, array $columnas): void
    {
        $columna = 1;
        foreach ($columnas as $titulo) {
            $letra = Coordinate::stringFromColumnIndex($columna);
            $hoja->setCellValue("{$letra}3", $titulo);
            $columna++;
        }

        $ultima = Coordinate::stringFromColumnIndex(count($columnas));
        $estilo = $hoja->getStyle("A3:{$ultima}3");
        $estilo->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $estilo->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::COLOR_MARCA);
        $estilo->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $estilo->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $hoja->getRowDimension(3)->setRowHeight(22);
        $hoja->freezePane('A4');
    }

    /**
     * Escribe una fila de datos con formatos numéricos opcionales por columna y fondo alternado.
     */
    private function fila(Worksheet $hoja, int $numFila, array $valores, array $formatos = []): void
    {
        $columna = 1;
        foreach ($valores as $valor) {
            $letra = Coordinate::stringFromColumnIndex($columna);
            $celda = "{$letra}{$numFila}";
            $hoja->setCellValue($celda, $valor);

            if (isset($formatos[$columna])) {
                $hoja->getStyle($celda)->getNumberFormat()->setFormatCode($formatos[$columna]);
            }

            $columna++;
        }

        if ($numFila % 2 === 0) {
            $ultima = Coordinate::stringFromColumnIndex(count($valores));
            $hoja->getStyle("A{$numFila}:{$ultima}{$numFila}")
                ->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB(self::COLOR_FILA_ALTERNADA);
        }
    }

    /**
     * Ajusta el ancho de las columnas (índice 1 = columna A).
     */
    private function anchos(Worksheet $hoja, array $anchos): void
    {
        foreach ($anchos as $indice => $ancho) {
            $hoja->getColumnDimension(Coordinate::stringFromColumnIndex($indice + 1))->setWidth($ancho);
        }
    }

    private function mesNombre(int $mes): string
    {
        return self::MESES_NOMBRES[$mes] ?? "Mes {$mes}";
    }
}
