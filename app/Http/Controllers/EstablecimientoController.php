<?php

namespace App\Http\Controllers;

use App\Http\Requests\EstablecimientoRequest;
use App\Models\Establecimiento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Módulo de Administración de Establecimientos de Salud de la Red Asistencial.
 */
class EstablecimientoController extends Controller
{
    private const POR_PAGINA = 12;

    /**
     * Muestra el listado de establecimientos con métricas y filtros.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $filtros = $this->extraerFiltros($request);

        $pagina = min(max($request->integer('page', 1), 1), 100_000);

        $establecimientos = Establecimiento::query()
            ->withCount('medidas')
            ->filtrar($filtros)
            ->orderBy('tipo')
            ->orderBy('nombre')
            ->paginate(self::POR_PAGINA, ['*'], 'page', $pagina)
            ->withQueryString();

        // Si la página pedida quedó vacía tras una acción, redirigir a la última página disponible
        if ($establecimientos->isEmpty() && $establecimientos->currentPage() > 1) {
            $request->session()->reflash();

            return redirect($establecimientos->url($establecimientos->lastPage()));
        }

        $stats = [
            'total' => Establecimiento::count(),
            'activos' => Establecimiento::where('is_active', true)->count(),
            'inactivos' => Establecimiento::where('is_active', false)->count(),
            'poblacion_total' => (int) Establecimiento::where('is_active', true)->sum('poblacion_inscrita'),
            'por_tipo' => Establecimiento::query()
                ->selectRaw('tipo, count(*) as cantidad')
                ->groupBy('tipo')
                ->pluck('cantidad', 'tipo')
                ->all(),
        ];

        return view('establecimientos.index', [
            'establecimientos' => $establecimientos,
            'filtros' => $filtros,
            'stats' => $stats,
            'tipos' => Establecimiento::TIPOS,
        ]);
    }

    /**
     * Almacena un nuevo establecimiento en la base de datos.
     */
    public function store(EstablecimientoRequest $request): RedirectResponse
    {
        $establecimiento = Establecimiento::create($request->validated());

        return redirect()
            ->route('establecimientos.index', ['q' => $establecimiento->codigo_deis ?: $establecimiento->nombre])
            ->with('status', "Establecimiento “{$establecimiento->nombre}” creado exitosamente.");
    }

    /**
     * Actualiza la información de un establecimiento existente.
     */
    public function update(EstablecimientoRequest $request, Establecimiento $establecimiento): RedirectResponse
    {
        $establecimiento->update($request->validated());

        return back()->with('status', "Establecimiento “{$establecimiento->nombre}” actualizado exitosamente.");
    }

    /**
     * Elimina un establecimiento si no posee mediciones registradas asociadas.
     */
    public function destroy(Establecimiento $establecimiento): RedirectResponse
    {
        $conteoMedidas = $establecimiento->medidas()->count();

        if ($conteoMedidas > 0) {
            return back()->withErrors([
                'establecimiento' => "No se puede eliminar “{$establecimiento->nombre}” porque cuenta con {$conteoMedidas} mediciones registradas en el sistema. Puedes desactivarlo para que no figure en nuevas operaciones.",
            ])->with('error', "No se puede eliminar “{$establecimiento->nombre}” porque cuenta con {$conteoMedidas} mediciones registradas en el sistema. Puedes desactivarlo para que no figure en nuevas operaciones.");
        }

        $nombre = $establecimiento->nombre;
        $establecimiento->delete();

        return redirect()
            ->route('establecimientos.index')
            ->with('status', "Establecimiento “{$nombre}” eliminado exitosamente.");
    }

    /**
     * Cambia rápidamente el estado activo/inactivo del establecimiento.
     */
    public function cambiarEstado(Request $request, Establecimiento $establecimiento): RedirectResponse
    {
        $datos = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $establecimiento->update([
            'is_active' => (bool) $datos['is_active'],
        ]);

        $accion = $establecimiento->is_active ? 'activado' : 'desactivado';

        return back()->with('status', "Establecimiento “{$establecimiento->nombre}” {$accion} exitosamente.");
    }

    /**
     * Normaliza y extrae los filtros de la petición.
     *
     * @return array{q: string, tipo: ?string, estado: 'activo'|'inactivo'|'todos'}
     */
    private function extraerFiltros(Request $request): array
    {
        $tipo = $request->string('tipo')->toString();
        $estado = $request->string('estado')->toString();

        return [
            'q' => trim($request->string('q')->toString()),
            'tipo' => in_array($tipo, Establecimiento::TIPOS, true) ? $tipo : null,
            'estado' => in_array($estado, ['activo', 'inactivo', 'todos'], true) ? $estado : 'todos',
        ];
    }
}
