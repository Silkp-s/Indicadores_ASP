<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndicadorRequest;
use App\Models\Indicador;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Módulo 1 · Catálogo de Indicadores APS.
 */
class IndicadorController extends Controller
{
    private const POR_PAGINA = 10;

    public function index(Request $request): View|RedirectResponse
    {
        $filtros = $this->filtros($request);

        // Página acotada: un número desmesurado en la URL desbordaría el OFFSET.
        $pagina = min(max($request->integer('page', 1), 1), 100_000);

        $indicadores = Indicador::query()
            ->filtrar($filtros)
            ->orderBy('codigo')
            ->paginate(self::POR_PAGINA, ['*'], 'page', $pagina)
            ->withQueryString();

        // Si la página pedida quedó vacía (por ejemplo, tras desactivar el último de la lista), ir a la última
        // conservando el mensaje de la acción anterior.
        if ($indicadores->isEmpty() && $indicadores->currentPage() > 1) {
            $request->session()->reflash();

            return redirect($indicadores->url($indicadores->lastPage()));
        }

        return view('indicadores.index', [
            'indicadores' => $indicadores,
            'filtros' => $filtros,
            'porFuente' => $this->conteoPorFuente(),
        ]);
    }

    public function store(IndicadorRequest $request): RedirectResponse
    {
        $indicador = Indicador::create($request->validated());

        return redirect()
            ->route('indicadores.index', ['q' => $indicador->codigo, 'estado' => 'todos'])
            ->with('status', "Indicador {$indicador->codigo} creado.");
    }

    public function update(IndicadorRequest $request, Indicador $indicador): RedirectResponse
    {
        $indicador->update($request->validated());

        return back()->with('status', "Indicador {$indicador->codigo} actualizado.");
    }

    /**
     * Activa o desactiva según el estado pedido por el botón (no invierte a ciegas: un doble envío
     * o una pestaña desactualizada no deshacen la acción).
     */
    public function cambiarEstado(Request $request, Indicador $indicador): RedirectResponse
    {
        $datos = $request->validate(['is_active' => ['required', 'boolean']]);

        $indicador->update(['is_active' => (bool) $datos['is_active']]);

        return back()->with('status', "Indicador {$indicador->codigo} ".($indicador->is_active ? 'activado.' : 'desactivado.'));
    }

    /**
     * Descarga en CSV (se abre en Excel) todos los indicadores que cumplen la búsqueda y los filtros actuales.
     */
    public function exportar(Request $request): StreamedResponse
    {
        $filtros = $this->filtros($request);

        return response()->streamDownload(function () use ($filtros): void {
            $salida = fopen('php://output', 'w');

            // BOM para que Excel reconozca los acentos; ";" es el separador de listas de Excel en español.
            fwrite($salida, "\xEF\xBB\xBF");
            fputcsv($salida, ['Código', 'Nombre', 'Tipo', 'Numerador', 'Denominador', 'Meta (%)', 'Periodicidad', 'Fuente', 'Estado'], ';', '"', '');

            Indicador::query()->filtrar($filtros)->orderBy('codigo')->lazy()->each(function (Indicador $indicador) use ($salida): void {
                fputcsv($salida, array_map($this->textoSeguroParaExcel(...), [
                    $indicador->codigo,
                    $indicador->nombre,
                    $indicador->tipo,
                    $indicador->numerador_description,
                    $indicador->denominador_description,
                    $indicador->meta_formateada,
                    $indicador->periodicidad,
                    $indicador->fu,
                    $indicador->is_active ? 'Activo' : 'Inactivo',
                ]), ';', '"', '');
            });

            fclose($salida);
        }, 'catalogo-indicadores-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Evita que Excel interprete como fórmula un texto que empieza con =, +, -, @, tabulación o retorno.
     */
    private function textoSeguroParaExcel(?string $texto): string
    {
        return preg_match('/^[=+\-@\t\r]/', (string) $texto) ? "'".$texto : (string) $texto;
    }

    /**
     * Búsqueda y filtros desde la URL. Valores desconocidos se ignoran; sin `estado` se muestran los activos.
     * "Todos" se representa con estado=todos (no vacío) para que sobreviva en los enlaces de paginación y exportar.
     *
     * @return array{q: string, tipo: ?string, fuente: ?string, estado: 'activo'|'inactivo'|'todos'}
     */
    private function filtros(Request $request): array
    {
        $valor = fn (string $clave): string => is_string($request->query($clave)) ? trim($request->query($clave)) : '';
        $estado = $request->has('estado') ? $valor('estado') : 'activo';

        return [
            'q' => mb_substr($valor('q'), 0, 100),
            'tipo' => in_array($valor('tipo'), Indicador::TIPOS, true) ? $valor('tipo') : null,
            'fuente' => in_array($valor('fuente'), Indicador::FUENTES, true) ? $valor('fuente') : null,
            'estado' => in_array($estado, ['activo', 'inactivo', 'todos'], true) ? $estado : 'activo',
        ];
    }

    /**
     * Indicadores activos por fuente y tipo, para el gráfico de barras.
     *
     * @return array<string, array<string, int>> fuente => [tipo => cantidad]
     */
    private function conteoPorFuente(): array
    {
        $conteos = Indicador::query()
            ->where('is_active', true)
            ->selectRaw('fu, tipo, count(*) as total')
            ->groupBy('fu', 'tipo')
            ->get();

        return collect(Indicador::FUENTES)
            ->mapWithKeys(fn (string $fuente) => [$fuente => collect(Indicador::TIPOS)->mapWithKeys(
                fn (string $tipo) => [$tipo => (int) $conteos->where('fu', $fuente)->firstWhere('tipo', $tipo)?->total]
            )->all()])
            ->all();
    }
}
