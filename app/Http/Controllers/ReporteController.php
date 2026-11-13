<?php

namespace App\Http\Controllers;

use App\Models\AlertaRegla;
use App\Models\Establecimiento;
use App\Models\IndicadorMedida;
use App\Services\ReporteExportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Dompdf;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Módulo 6 · Reportes exportables (PDF/Excel) para la Dirección DAS y el SS Talcahuano.
 */
class ReporteController extends Controller
{
    private const MESES_NOMBRES = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
        7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    public function __construct(private readonly ReporteExportService $reportes) {}

    /**
     * Selector de reportes: tipo, período, filtros y formato de descarga.
     */
    public function index(Request $request): View
    {
        $periodos = IndicadorMedida::select('ano', 'mes')
            ->distinct()
            ->orderByDesc('ano')
            ->orderByDesc('mes')
            ->get()
            ->map(fn ($p) => [
                'key' => sprintf('%04d-%02d', $p->ano, $p->mes),
                'label' => (self::MESES_NOMBRES[(int) $p->mes] ?? "Mes {$p->mes}")." {$p->ano}",
            ]);

        $ano = (int) ($request->input('ano') ?: now()->year);
        $mes = (int) ($request->input('mes') ?: now()->month);

        return view('reportes.index', [
            'periodos' => $periodos,
            'establecimientos' => Establecimiento::where('is_active', true)->orderBy('nombre')->get(['id', 'nombre', 'tipo']),
            'tipos' => ['Meta Sanitaria', 'IAAPS'],
            'severidades' => AlertaRegla::SEVERIDADES,
            'ano' => $ano,
            'mes' => $mes,
        ]);
    }

    /**
     * Genera y descarga el reporte pedido en el formato solicitado (xlsx o pdf).
     */
    public function exportar(Request $request): BinaryFileResponse
    {
        $datos = $request->validate([
            'tipo' => ['required', 'in:mensual,trimestral,alertas'],
            'formato' => ['required', 'in:xlsx,pdf'],
            'ano' => ['required', 'integer', 'min:2000', 'max:2100'],
            'mes' => ['nullable', 'integer', 'between:1,12'],
            'trimestre' => ['nullable', 'integer', 'between:1,4'],
            'establecimiento_id' => ['nullable', 'integer', 'exists:establecimientos,id'],
            'tipo_indicador' => ['nullable', 'in:Meta Sanitaria,IAAPS'],
            'severidad' => ['nullable', 'in:Crítica,Advertencia,Informativa'],
        ], [
            'tipo.required' => 'Seleccione el tipo de reporte.',
            'tipo.in' => 'El tipo de reporte seleccionado no es válido.',
            'formato.required' => 'Seleccione el formato de descarga.',
            'formato.in' => 'El formato seleccionado no es válido.',
            'ano.required' => 'Indique el año del reporte.',
            'mes.between' => 'El mes debe estar entre 1 y 12.',
            'trimestre.between' => 'El trimestre debe estar entre 1 y 4.',
        ]);

        $ano = (int) $datos['ano'];
        $mes = (int) ($datos['mes'] ?? now()->month);
        $trimestre = (int) ($datos['trimestre'] ?? (int) ceil($mes / 3));
        $establecimientoId = isset($datos['establecimiento_id']) ? (int) $datos['establecimiento_id'] : null;
        $tipoIndicador = $datos['tipo_indicador'] ?? null;
        $severidad = $datos['severidad'] ?? null;

        $spreadsheet = match ($datos['tipo']) {
            'mensual' => $this->reportes->generarMensual($ano, $mes, $establecimientoId, $tipoIndicador),
            'trimestral' => $this->reportes->generarTrimestral($ano, $trimestre, $establecimientoId, $tipoIndicador),
            'alertas' => $this->reportes->generarAlertas($ano, $mes, $severidad),
        };

        $nombre = $this->nombreArchivo($datos['tipo'], $datos['formato'], $ano, $mes, $trimestre);

        return $this->descargar($spreadsheet, $datos['formato'], $nombre);
    }

    /**
     * Escribe el libro en un archivo temporal y lo entrega como descarga.
     */
    private function descargar(Spreadsheet $spreadsheet, string $formato, string $nombre): BinaryFileResponse
    {
        $extension = $formato === 'pdf' ? 'pdf' : 'xlsx';
        $mime = $formato === 'pdf'
            ? 'application/pdf'
            : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        $ruta = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('reporte_', true).'.'.$extension;

        $writer = $formato === 'pdf' ? new Dompdf($spreadsheet) : new Xlsx($spreadsheet);
        $writer->save($ruta);

        return response()->download($ruta, $nombre, ['Content-Type' => $mime])->deleteFileAfterSend(true);
    }

    /**
     * Nombre de archivo legible del reporte descargado.
     */
    private function nombreArchivo(string $tipo, string $formato, int $ano, int $mes, int $trimestre): string
    {
        $extension = $formato === 'pdf' ? 'pdf' : 'xlsx';

        $prefijo = match ($tipo) {
            'mensual' => "Reporte_Mensual_{$ano}_".sprintf('%02d', $mes),
            'trimestral' => "Reporte_Trimestral_{$ano}_T{$trimestre}",
            'alertas' => "Reporte_Alertas_{$ano}_".sprintf('%02d', $mes),
        };

        return "{$prefijo}.{$extension}";
    }
}
