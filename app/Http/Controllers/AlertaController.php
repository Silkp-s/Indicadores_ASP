<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use App\Models\AlertaRegla;
use App\Models\Establecimiento;
use App\Models\IndicadorMedida;
use App\Services\AlertaEngine;
use App\Services\ProyeccionEstadisticaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Módulo 6 · Motor y Bandeja de Alertas.
 */
class AlertaController extends Controller
{
    private const POR_PAGINA = 15;

    /**
     * Bandeja priorizada por severidad, con filtros por período, establecimiento, severidad y estado.
     */
    public function index(Request $request): View
    {
        $periodos = $this->periodosDisponibles();

        // Período: el pedido, o el más reciente con alertas/mediciones, o el mes en curso.
        $periodoSeleccionado = (string) $request->input('periodo', $periodos->first()['key'] ?? sprintf('%04d-%02d', now()->year, now()->month));
        [$ano, $mes] = array_map('intval', array_pad(explode('-', $periodoSeleccionado), 2, 0));

        if ($mes < 1 || $mes > 12 || $ano < 2000) {
            $ano = (int) now()->year;
            $mes = (int) now()->month;
            $periodoSeleccionado = sprintf('%04d-%02d', $ano, $mes);
        }

        $filtros = [
            'periodo' => $periodoSeleccionado,
            'severidad' => in_array($request->input('severidad'), AlertaRegla::SEVERIDADES, true) ? $request->input('severidad') : 'todas',
            'estado' => in_array($request->input('estado'), [...Alerta::ESTADOS, 'todas', 'abiertas'], true) ? $request->input('estado') : 'abiertas',
            'establecimiento_id' => $request->filled('establecimiento_id') ? $request->integer('establecimiento_id') : null,
        ];

        $base = Alerta::query()
            ->join('alerta_reglas', 'alerta_reglas.id', '=', 'alertas.alerta_regla_id')
            ->where('alertas.ano', $ano)
            ->where('alertas.mes', $mes)
            ->when($filtros['establecimiento_id'], fn ($q, $id) => $q->where('alertas.establecimientos_id', $id));

        // Contadores del período (respetan establecimiento, no severidad ni estado) para las tarjetas.
        $abiertasPorSeveridad = (clone $base)
            ->whereIn('alertas.estado', ['Pendiente', 'Revisada'])
            ->selectRaw('alerta_reglas.severidad as severidad, count(*) as total')
            ->groupBy('alerta_reglas.severidad')
            ->pluck('total', 'severidad');

        $resueltas = (clone $base)->where('alertas.estado', 'Resuelta')->count();

        $alertas = Alerta::query()
            ->porSeveridad()
            ->with(['regla', 'establecimiento', 'indicador'])
            ->where('alertas.ano', $ano)
            ->where('alertas.mes', $mes)
            ->when($filtros['establecimiento_id'], fn ($q, $id) => $q->where('alertas.establecimientos_id', $id))
            ->when($filtros['severidad'] !== 'todas', fn ($q) => $q->where('alerta_reglas.severidad', $filtros['severidad']))
            ->when($filtros['estado'] === 'abiertas', fn ($q) => $q->whereIn('alertas.estado', ['Pendiente', 'Revisada']))
            ->when(in_array($filtros['estado'], Alerta::ESTADOS, true), fn ($q) => $q->where('alertas.estado', $filtros['estado']))
            ->paginate(self::POR_PAGINA)
            ->withQueryString();

        return view('alertas.index', [
            'alertas' => $alertas,
            'filtros' => $filtros,
            'periodos' => $periodos,
            'establecimientos' => Establecimiento::whereIn('tipo', ['CESFAM', 'CECOSF'])->orderBy('nombre')->get(['id', 'nombre']),
            'severidades' => AlertaRegla::SEVERIDADES,
            'estados' => Alerta::ESTADOS,
            'abiertasPorSeveridad' => $abiertasPorSeveridad,
            'resueltas' => $resueltas,
            'reglasActivas' => AlertaRegla::activas()->count(),
            'periodoLabel' => (ProyeccionEstadisticaService::MESES_NOMBRES[$mes] ?? "Mes {$mes}")." {$ano}",
        ]);
    }

    /**
     * Gestión de una alerta: cambio de estado y observación de la acción correctiva.
     */
    public function update(Request $request, Alerta $alerta): RedirectResponse
    {
        $datos = $request->validate([
            'estado' => ['required', 'in:'.implode(',', Alerta::ESTADOS)],
            'observacion_gestion' => ['nullable', 'string', 'max:2000', 'required_if:estado,Resuelta'],
        ], [
            'estado.required' => 'Seleccione el estado de la alerta.',
            'estado.in' => 'El estado seleccionado no es válido.',
            'observacion_gestion.required_if' => 'Para marcar la alerta como Resuelta indique la acción correctiva realizada.',
            'observacion_gestion.max' => 'La observación no puede superar los 2000 caracteres.',
        ]);

        $alerta->update($datos);

        return back()->with('status', 'Alerta actualizada: '.$alerta->estado.'.');
    }

    /**
     * Ejecuta el motor de reglas para un período.
     */
    public function evaluar(Request $request, AlertaEngine $motor): RedirectResponse
    {
        $datos = $request->validate([
            'periodo' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
        ], [
            'periodo.required' => 'Seleccione el período a evaluar.',
            'periodo.regex' => 'El período seleccionado no es válido.',
        ]);

        [$ano, $mes] = array_map('intval', explode('-', $datos['periodo']));
        $resultado = $motor->evaluar($ano, $mes);

        return redirect()
            ->route('alertas.index', ['periodo' => $datos['periodo']])
            ->with('status', "Evaluación completada: {$resultado['detectadas']} alerta(s) detectada(s)"
                .($resultado['retiradas'] > 0 ? " y {$resultado['retiradas']} pendiente(s) retirada(s) por haberse corregido el dato." : '.'));
    }

    /**
     * Períodos con mediciones o alertas, del más reciente al más antiguo.
     *
     * @return \Illuminate\Support\Collection<int, array{key: string, label: string}>
     */
    private function periodosDisponibles()
    {
        $medidas = IndicadorMedida::select('ano', 'mes')->distinct()->get();
        $alertas = Alerta::select('ano', 'mes')->distinct()->get();

        return $medidas->concat($alertas)
            ->unique(fn ($p) => $p->ano.'-'.$p->mes)
            ->sortByDesc(fn ($p) => $p->ano * 100 + $p->mes)
            ->values()
            ->map(fn ($p) => [
                'key' => sprintf('%04d-%02d', $p->ano, $p->mes),
                'label' => (ProyeccionEstadisticaService::MESES_NOMBRES[(int) $p->mes] ?? "Mes {$p->mes}")." {$p->ano}",
            ]);
    }
}
