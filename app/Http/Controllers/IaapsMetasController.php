<?php

namespace App\Http\Controllers;

use App\Http\Requests\IaapsMetaRequest;
use App\Models\Establecimiento;
use App\Models\Indicador;
use App\Models\IndicadorMedida;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Módulo 2 · Mantenedor de Metas Sanitarias e IAAPS con detalle por establecimiento.
 */
class IaapsMetasController extends Controller
{
    private const POR_PAGINA = 10;

    /**
     * Listado paginado de 10 en 10 con filtros y mediciones por establecimiento.
     */
    public function index(Request $request): View|RedirectResponse
    {
        // 1. Periodos disponibles para el desglose de mediciones
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

        // Determinar periodo seleccionado
        if ($request->filled('periodo') && str_contains($request->input('periodo'), '-')) {
            $partes = explode('-', (string) $request->input('periodo'));
            $selectedAno = (int) $partes[0];
            $selectedMes = (int) $partes[1];
            $periodoSeleccionado = (string) $request->input('periodo');
        } elseif ($periodosDisponibles->isNotEmpty()) {
            $selectedAno = $periodosDisponibles->first()['ano'];
            $selectedMes = $periodosDisponibles->first()['mes'];
            $periodoSeleccionado = $periodosDisponibles->first()['key'];
        } else {
            $selectedAno = (int) date('Y');
            $selectedMes = (int) date('n');
            $periodoSeleccionado = sprintf('%04d-%02d', $selectedAno, $selectedMes);
        }

        // 2. Filtros
        $filtros = [
            'q' => trim((string) $request->input('q', '')),
            'tipo' => (string) $request->input('tipo', 'todas'),
            'estado' => (string) $request->input('estado', 'todos'),
            'periodo' => $periodoSeleccionado,
        ];

        // 3. Consulta de indicadores (solo IAAPS y Metas Sanitarias)
        $query = Indicador::query();

        if ($filtros['tipo'] === 'Meta Sanitaria' || $filtros['tipo'] === 'IAAPS') {
            $query->where('tipo', $filtros['tipo']);
        } else {
            $query->whereIn('tipo', ['Meta Sanitaria', 'IAAPS']);
        }

        if ($filtros['q'] !== '') {
            $texto = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $filtros['q']).'%';
            $query->where(function ($sub) use ($texto) {
                $sub->whereRaw("codigo like ? escape '!'", [$texto])
                    ->orWhereRaw("nombre like ? escape '!'", [$texto]);
            });
        }

        if ($filtros['estado'] === 'activo') {
            $query->where('is_active', true);
        } elseif ($filtros['estado'] === 'inactivo') {
            $query->where('is_active', false);
        }

        $pagina = min(max($request->integer('page', 1), 1), 100_000);

        $indicadores = $query->orderBy('tipo')
            ->orderBy('codigo')
            ->paginate(self::POR_PAGINA, ['*'], 'page', $pagina)
            ->withQueryString();

        if ($indicadores->isEmpty() && $indicadores->currentPage() > 1) {
            $request->session()->reflash();

            return redirect($indicadores->url($indicadores->lastPage()));
        }

        // 4. Cargar mediciones para los indicadores paginados en el periodo seleccionado
        $indicadores->load([
            'medidas' => function ($mQuery) use ($selectedAno, $selectedMes) {
                $mQuery->where('ano', $selectedAno)
                    ->where('mes', $selectedMes);
            },
        ]);

        // 5. Establecimientos activos de la red
        $establecimientos = Establecimiento::where('is_active', true)
            ->orderBy('tipo')
            ->orderBy('nombre')
            ->get();

        return view('iaaps-metas.index', [
            'indicadores' => $indicadores,
            'establecimientos' => $establecimientos,
            'filtros' => $filtros,
            'periodosDisponibles' => $periodosDisponibles,
            'periodoSeleccionado' => $periodoSeleccionado,
            'selectedAno' => $selectedAno,
            'selectedMes' => $selectedMes,
            'tipos' => ['Meta Sanitaria', 'IAAPS'],
            'periodicidades' => Indicador::PERIODICIDADES,
            'fuentes' => Indicador::FUENTES,
        ]);
    }

    /**
     * Crea un nuevo indicador (Meta Sanitaria o IAAPS).
     */
    public function store(IaapsMetaRequest $request): RedirectResponse
    {
        $indicador = Indicador::create($request->validated());

        return redirect()
            ->route('iaaps-metas.index', ['q' => $indicador->codigo])
            ->with('status', "Indicador {$indicador->codigo} (“{$indicador->nombre}”) creado exitosamente.");
    }

    /**
     * Actualiza un indicador existente.
     */
    public function update(IaapsMetaRequest $request, Indicador $indicador): RedirectResponse
    {
        $indicador->update($request->validated());

        return back()->with('status', "Indicador {$indicador->codigo} actualizado exitosamente.");
    }

    /**
     * Elimina un indicador y sus mediciones.
     */
    public function destroy(Indicador $indicador): RedirectResponse
    {
        $codigo = $indicador->codigo;
        $nombre = $indicador->nombre;

        $indicador->delete();

        return redirect()
            ->route('iaaps-metas.index')
            ->with('status', "Indicador {$codigo} (“{$nombre}”) eliminado exitosamente.");
    }
}
