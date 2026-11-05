@extends('layouts.app')

@section('title', 'Alertas')
@section('page-title', 'Bandeja de Alertas')
@section('page-subtitle', 'Incidentes de riesgo clínico-administrativo e incumplimiento de metas, priorizados por severidad')

@section('content')
@php
    $estilosSeveridad = [
        'Crítica' => ['badge' => 'bg-status-bad-bg text-status-bad-fg', 'borde' => 'border-l-status-bad-fg', 'tarjeta' => 'text-status-bad-fg'],
        'Advertencia' => ['badge' => 'bg-status-warn-bg text-status-warn-fg', 'borde' => 'border-l-status-warn-fg', 'tarjeta' => 'text-status-warn-fg'],
        'Informativa' => ['badge' => 'bg-brand-100 text-accent-600', 'borde' => 'border-l-accent-500', 'tarjeta' => 'text-accent-600'],
    ];
    $estilosEstado = [
        'Pendiente' => 'bg-status-bad-bg text-status-bad-fg',
        'Revisada' => 'bg-status-warn-bg text-status-warn-fg',
        'Resuelta' => 'bg-status-ok-bg text-status-ok-fg',
    ];
    $hayFiltros = $filtros['severidad'] !== 'todas' || $filtros['estado'] !== 'abiertas' || $filtros['establecimiento_id'];
@endphp
<div class="space-y-6">

    @if ($errors->any())
        <div class="flex items-start gap-3 rounded-xl border border-status-bad-fg/30 bg-status-bad-bg p-4 text-sm text-status-bad-fg shadow-xs" id="alertas-errores">
            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div>
                <p class="font-bold">No se pudo procesar la solicitud:</p>
                <ul class="mt-1 list-inside list-disc space-y-0.5 text-xs">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- Tarjetas resumen: alertas abiertas (Pendiente + Revisada) del período --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($severidades as $severidad)
            <div class="rounded-xl border border-[#E2E8F0] border-l-4 {{ $estilosSeveridad[$severidad]['borde'] }} bg-white p-4 shadow-xs">
                <p class="text-xs font-semibold uppercase tracking-wider text-[#64748B]">{{ $severidad }}s abiertas</p>
                <p class="mt-1 text-3xl font-bold {{ $estilosSeveridad[$severidad]['tarjeta'] }}" data-resumen="{{ $severidad }}">{{ (int) ($abiertasPorSeveridad[$severidad] ?? 0) }}</p>
            </div>
        @endforeach
        <div class="rounded-xl border border-[#E2E8F0] border-l-4 border-l-status-ok-fg bg-white p-4 shadow-xs">
            <p class="text-xs font-semibold uppercase tracking-wider text-[#64748B]">Resueltas</p>
            <p class="mt-1 text-3xl font-bold text-status-ok-fg" data-resumen="Resueltas">{{ $resueltas }}</p>
        </div>
    </div>

    {{-- Filtros y ejecución del motor --}}
    <div class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-[#E2E8F0] bg-white p-4 shadow-xs">

        <form method="GET" action="{{ route('alertas.index') }}" class="flex flex-1 flex-wrap items-center gap-3">
            <select name="periodo" onchange="this.form.submit()" title="Período evaluado"
                class="w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm font-medium text-brand-950 focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20 sm:w-auto">
                @forelse ($periodos as $p)
                    <option value="{{ $p['key'] }}" @selected($filtros['periodo'] === $p['key'])>Período: {{ $p['label'] }}</option>
                @empty
                    <option value="{{ $filtros['periodo'] }}">Período: {{ $periodoLabel }}</option>
                @endforelse
            </select>

            <select name="establecimiento_id" onchange="this.form.submit()"
                class="w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm font-medium text-brand-950 focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20 sm:w-auto">
                <option value="">Todos los establecimientos</option>
                @foreach ($establecimientos as $e)
                    <option value="{{ $e->id }}" @selected($filtros['establecimiento_id'] === $e->id)>{{ $e->nombre }}</option>
                @endforeach
            </select>

            <select name="severidad" onchange="this.form.submit()"
                class="w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm font-medium text-brand-950 focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20 sm:w-auto">
                <option value="todas" @selected($filtros['severidad'] === 'todas')>Severidad: Todas</option>
                @foreach ($severidades as $severidad)
                    <option value="{{ $severidad }}" @selected($filtros['severidad'] === $severidad)>{{ $severidad }}</option>
                @endforeach
            </select>

            <select name="estado" onchange="this.form.submit()"
                class="w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm font-medium text-brand-950 focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20 sm:w-auto">
                <option value="abiertas" @selected($filtros['estado'] === 'abiertas')>Estado: Abiertas</option>
                @foreach ($estados as $estado)
                    <option value="{{ $estado }}" @selected($filtros['estado'] === $estado)>{{ $estado }}</option>
                @endforeach
                <option value="todas" @selected($filtros['estado'] === 'todas')>Todas</option>
            </select>

            @if ($hayFiltros)
                <a href="{{ route('alertas.index', ['periodo' => $filtros['periodo']]) }}"
                    class="rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm font-medium text-[#64748B] transition-colors hover:bg-[#F8FAFC]">Limpiar</a>
            @endif
        </form>

        <form method="POST" action="{{ route('alertas.evaluar') }}" class="flex items-center gap-2">
            @csrf
            <input type="hidden" name="periodo" value="{{ $filtros['periodo'] }}">
            <button type="submit"
                class="inline-flex items-center gap-2 rounded-lg bg-accent-500 px-4 py-2 text-sm font-semibold text-white shadow-xs transition-colors hover:bg-accent-600"
                title="Aplica las {{ $reglasActivas }} reglas activas a las mediciones de {{ $periodoLabel }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>
                Evaluar {{ $periodoLabel }}
            </button>
        </form>
    </div>

    {{-- Bandeja --}}
    <div class="overflow-hidden rounded-xl border border-[#E2E8F0] bg-white shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-left text-sm" id="tabla-alertas">
                <thead class="border-b border-[#E2E8F0] bg-[#F8FAFC] text-xs font-semibold uppercase tracking-wider text-[#64748B]">
                    <tr>
                        <th class="px-5 py-3.5">Severidad</th>
                        <th class="px-5 py-3.5">Establecimiento</th>
                        <th class="px-5 py-3.5">Regla / Indicador</th>
                        <th class="px-5 py-3.5">Detalle</th>
                        <th class="px-5 py-3.5 text-right">Brecha</th>
                        <th class="px-5 py-3.5 text-center">Estado</th>
                        <th class="px-5 py-3.5 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E2E8F0]">
                    @forelse ($alertas as $alerta)
                        <tr class="align-top transition-colors hover:bg-[#F8FAFC]">
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $estilosSeveridad[$alerta->regla->severidad]['badge'] }}">{{ $alerta->regla->severidad }}</span>
                            </td>
                            <td class="px-5 py-4 font-medium text-brand-950">{{ $alerta->establecimiento?->nombre ?? '—' }}</td>
                            <td class="px-5 py-4">
                                <p class="text-xs font-bold text-[#64748B]">{{ $alerta->regla->codigo }} · {{ $alerta->regla->nombre }}</p>
                                <p class="mt-0.5 text-brand-950">{{ $alerta->indicador ? $alerta->indicador->codigo.' · '.$alerta->indicador->nombre : 'Todo el establecimiento' }}</p>
                            </td>
                            <td class="max-w-sm px-5 py-4 text-xs text-[#475569]">
                                {{ $alerta->mensaje }}
                                @if ($alerta->observacion_gestion)
                                    <p class="mt-1.5 rounded-md bg-[#F1F5F9] p-2 text-[#334155]"><span class="font-semibold">Gestión:</span> {{ $alerta->observacion_gestion }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right font-semibold tabular-nums text-brand-950">
                                {{ $alerta->brecha_detectada !== null ? number_format((float) $alerta->brecha_detectada, 1, ',', '.') : '—' }}
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $estilosEstado[$alerta->estado] }}">{{ $alerta->estado }}</span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <button type="button" data-gestionar
                                    data-url="{{ route('alertas.update', $alerta) }}"
                                    data-estado="{{ $alerta->estado }}"
                                    data-observacion="{{ $alerta->observacion_gestion }}"
                                    data-titulo="{{ $alerta->regla->codigo }} · {{ $alerta->establecimiento?->nombre }}"
                                    class="rounded-lg border border-[#CBD5E1] bg-white px-3 py-1.5 text-xs font-semibold text-brand-950 transition-colors hover:bg-[#F1F5F9]">
                                    Gestionar
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-14 text-center">
                                <p class="text-base font-semibold text-brand-950">No hay alertas para los filtros seleccionados</p>
                                <p class="mt-1 text-sm text-[#64748B]">
                                    Pulse «Evaluar {{ $periodoLabel }}» para ejecutar las reglas sobre las mediciones del período.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($alertas->hasPages())
            <div class="border-t border-[#E2E8F0] bg-[#F8FAFC] px-5 py-3">
                {{ $alertas->links() }}
            </div>
        @endif
    </div>
</div>

{{-- Modal de gestión --}}
<div id="modal-gestion" class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto bg-brand-950/50 p-4 backdrop-blur-xs"
    role="dialog" aria-modal="true" aria-labelledby="gestion-titulo" data-reabrir="{{ $errors->has('estado') || $errors->has('observacion_gestion') ? 'true' : 'false' }}">
    <div class="relative my-8 w-full max-w-lg overflow-hidden rounded-2xl border border-[#E2E8F0] bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-[#E2E8F0] bg-[#F8FAFC] px-6 py-4">
            <div>
                <h2 id="gestion-titulo" class="text-base font-bold text-brand-950">Gestionar alerta</h2>
                <p class="text-xs text-[#64748B]" data-gestion-subtitulo></p>
            </div>
            <button type="button" data-cerrar-gestion class="rounded-lg p-1.5 text-[#94A3B8] transition-colors hover:bg-[#E2E8F0] hover:text-brand-950" aria-label="Cerrar ventana">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <form method="POST" id="form-gestion" action="#" class="space-y-4 px-6 py-5">
            @csrf
            @method('PATCH')

            <div>
                <label for="gestion-estado" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-[#64748B]">Estado</label>
                <select id="gestion-estado" name="estado" required
                    class="w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm text-brand-950 focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20">
                    @foreach ($estados as $estado)
                        <option value="{{ $estado }}">{{ $estado }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="gestion-observacion" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-[#64748B]">
                    Observación de gestión <span class="font-normal normal-case" data-obligatoria hidden>(obligatoria para resolver)</span>
                </label>
                <textarea id="gestion-observacion" name="observacion_gestion" rows="4" maxlength="2000"
                    placeholder="Acción correctiva realizada, responsable, plazo…"
                    class="w-full rounded-lg border border-[#CBD5E1] bg-[#F8FAFC] px-3 py-2 text-sm text-brand-950 placeholder:text-[#94A3B8] focus:border-accent-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-500/20"></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-1">
                <button type="button" data-cerrar-gestion class="rounded-lg border border-[#CBD5E1] bg-white px-4 py-2 text-sm font-medium text-[#64748B] transition-colors hover:bg-[#F8FAFC]">Cancelar</button>
                <button type="submit" class="rounded-lg bg-brand-950 px-4 py-2 text-sm font-semibold text-white shadow-xs transition-colors hover:bg-brand-800">Guardar</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (() => {
        const modal = document.getElementById('modal-gestion');
        const form = document.getElementById('form-gestion');
        const estado = document.getElementById('gestion-estado');
        const observacion = document.getElementById('gestion-observacion');
        const marcaObligatoria = modal.querySelector('[data-obligatoria]');

        const abrir = () => { modal.classList.remove('hidden'); modal.classList.add('flex'); estado.focus(); };
        const cerrar = () => { modal.classList.add('hidden'); modal.classList.remove('flex'); };
        const actualizarObligatoria = () => { marcaObligatoria.hidden = estado.value !== 'Resuelta'; };

        estado.addEventListener('change', actualizarObligatoria);

        document.getElementById('tabla-alertas').addEventListener('click', (e) => {
            const boton = e.target.closest('[data-gestionar]');
            if (!boton) return;

            form.action = boton.dataset.url;
            estado.value = boton.dataset.estado;
            observacion.value = boton.dataset.observacion || '';
            modal.querySelector('[data-gestion-subtitulo]').textContent = boton.dataset.titulo;
            actualizarObligatoria();
            abrir();
        });

        modal.querySelectorAll('[data-cerrar-gestion]').forEach((b) => b.addEventListener('click', cerrar));
        modal.addEventListener('click', (e) => { if (e.target === modal) cerrar(); });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') cerrar(); });
    })();
</script>
@endpush
