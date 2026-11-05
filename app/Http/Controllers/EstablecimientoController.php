<?php

namespace App\Http\Controllers;

use App\Models\Establecimiento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Módulo de consulta de los Establecimientos de Salud de la Red Asistencial.
 *
 * La administración (crear, editar, cambiar estado y eliminar) se realiza desde
 * la sección "Establecimientos" del panel de administración (AdminController).
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
