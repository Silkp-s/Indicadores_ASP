@extends('layouts.app')

@section('title', 'Centro de Mando')
@section('page-title', 'Centro de Mando')
@section('page-subtitle', 'Vista comunal de cumplimiento e indicadores')

{{--
    NOTA PARA EL EQUIPO:
    El navbar/sidebar viene del layout (layouts.app).
    El gráfico "Evolución mensual de atenciones" queda como placeholder
    a propósito — se conecta cuando se defina la librería de gráficos.
--}}

@section('content')
<div class="space-y-6">

    {{-- Barra de filtros --}}
    <div class="flex flex-wrap items-center gap-3 rounded-md border border-[#E1E7E4] bg-white p-4">

        <div class="relative flex-1 min-w-[220px]">
            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#9AA6A1]" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z" />
            </svg>
            <input
                type="text"
                placeholder="Búsqueda de reportes..."
                class="w-full rounded-md border border-[#D7DEDB] bg-white py-2 pl-9 pr-3 text-sm placeholder:text-[#9AA6A1] focus:border-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-800/20"
            >
        </div>

        <select class="rounded-md border border-[#D7DEDB] bg-white px-3 py-2 text-sm text-[#4C5B56] focus:border-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-800/20">
            <option>Periodo: SEP 2026</option>
            <option>Periodo: AGO 2026</option>
            <option>Periodo: JUL 2026</option>
        </select>

        <select class="rounded-md border border-[#D7DEDB] bg-white px-3 py-2 text-sm text-[#4C5B56] focus:border-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-800/20">
            <option>Establecimiento: Comunal</option>
            <option>Establecimiento: CESFAM San Vicente</option>
            <option>Establecimiento: CECOSF Principal</option>
        </select>

        <select class="rounded-md border border-[#D7DEDB] bg-white px-3 py-2 text-sm text-[#4C5B56] focus:border-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-800/20">
            <option>Ver: Todos</option>
            <option>Ver: Bajo meta</option>
            <option>Ver: En proceso</option>
            <option>Ver: Cumpliendo</option>
        </select>

        <button
            type="button"
            class="ml-auto flex items-center gap-2 rounded-md bg-brand-950 px-4 py-2 text-sm font-medium text-white hover:bg-brand-800"
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Nuevo reporte
        </button>
    </div>

    {{-- KPIs --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">

        <div class="rounded-md border border-[#E1E7E4] bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-[#6B7A75]">Cumplimiento promedio</p>
            <div class="mt-3 flex items-center gap-3">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-status-ok-bg text-sm font-semibold text-status-ok-fg">
                    75%
                </div>
                <p class="text-xs leading-snug text-[#6B7A75]">IAAPS + Metas Sanitarias — 279 indicadores operativos</p>
            </div>
        </div>

        <div class="rounded-md border border-[#E1E7E4] bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-[#6B7A75]">Población beneficiaria comunal</p>
            <p class="mt-3 text-2xl font-semibold text-brand-950">130.794</p>
            <p class="mt-1 text-xs text-[#6B7A75]">Población estimada FONASA A-B-C-D por CESFAM</p>
        </div>

        <div class="rounded-md border border-[#E1E7E4] bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-[#6B7A75]">Atenciones · Julio 2026</p>
            <p class="mt-3 text-2xl font-semibold text-brand-950">70.730</p>
            <p class="mt-1 text-xs text-[#6B7A75]">de 82.500 registros mensuales</p>
        </div>

        <div class="rounded-md border border-[#E1E7E4] bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-[#6B7A75]">Establecimientos activos</p>
            <p class="mt-3 text-2xl font-semibold text-brand-950">10</p>
            <p class="mt-1 text-xs text-[#6B7A75]">CESFAM · CECOSF · Postas</p>
        </div>
    </div>

    {{-- Vista de indicadores --}}
    <div class="rounded-md border border-[#E1E7E4] bg-white">
        <div class="border-b border-[#E1E7E4] px-5 py-4">
            <h2 class="text-sm font-semibold text-brand-950">Vista de indicadores</h2>
        </div>

        <div class="divide-y divide-[#E9EEEC]">
            @foreach ([
                ['valor' => '50%', 'estado' => 'bajo', 'label' => 'Bajo meta', 'nombre' => 'Evaluación Desarrollo Psicomotor (12 a 23 meses)', 'meta' => '100%', 'detalle' => '10% de la meta', 'avance' => 10],
                ['valor' => '59,5%', 'estado' => 'medio', 'label' => 'En proceso', 'nombre' => 'Cobertura DM2 Efectiva en personas de 15 y más', 'meta' => '70%', 'detalle' => '50% de la meta', 'avance' => 50],
                ['valor' => '0,48', 'estado' => 'ok', 'label' => 'Cumpliendo', 'nombre' => 'Tasa de conflictos por establecimiento', 'meta' => '< 1.00', 'detalle' => 'Alineado con meta', 'avance' => 90],
            ] as $ind)
            <div class="flex flex-wrap items-center gap-4 px-5 py-4">

                <div @class([
                    'flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-sm font-semibold',
                    'bg-status-bad-bg text-status-bad-fg' => $ind['estado'] === 'bajo',
                    'bg-status-warn-bg text-status-warn-fg' => $ind['estado'] === 'medio',
                    'bg-status-ok-bg text-status-ok-fg' => $ind['estado'] === 'ok',
                ])>
                    {{ $ind['valor'] }}
                </div>

                <span @class([
                    'rounded-full px-2.5 py-1 text-xs font-medium',
                    'bg-status-bad-bg text-status-bad-fg' => $ind['estado'] === 'bajo',
                    'bg-status-warn-bg text-status-warn-fg' => $ind['estado'] === 'medio',
                    'bg-status-ok-bg text-status-ok-fg' => $ind['estado'] === 'ok',
                ])>
                    {{ $ind['label'] }}
                </span>

                <p class="min-w-[240px] flex-1 text-sm font-medium text-[#1C2A26]">{{ $ind['nombre'] }}</p>

                <p class="text-sm text-[#6B7A75]">Meta: {{ $ind['meta'] }}</p>

                <div class="flex w-40 items-center gap-2">
                    <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-[#E9EEEC]">
                        <div @class([
                            'h-full rounded-full',
                            'bg-status-bad-fg' => $ind['estado'] === 'bajo',
                            'bg-status-warn-fg' => $ind['estado'] === 'medio',
                            'bg-status-ok-fg' => $ind['estado'] === 'ok',
                        ]) style="width: {{ $ind['avance'] }}%"></div>
                    </div>
                    <span class="whitespace-nowrap text-xs text-[#6B7A75]">{{ $ind['detalle'] }}</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Gráficos: placeholders a propósito --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">

        <div class="rounded-md border border-[#E1E7E4] bg-white p-5 lg:col-span-2">
            <h2 class="text-sm font-semibold text-brand-950">Evolución mensual de atenciones</h2>
            <div class="mt-4 flex h-48 items-center justify-center rounded-md border border-dashed border-[#C7D3CE] bg-[#F9FBFA] text-xs text-[#6B7A75]">
                Gráfico pendiente — se conecta con la librería de gráficos que defina el equipo
            </div>
        </div>

        <div class="rounded-md border border-[#E1E7E4] bg-white p-5">
            <h2 class="text-sm font-semibold text-brand-950">Cumplimiento por establecimiento</h2>
            <div class="mt-4 flex h-48 items-center justify-center rounded-md border border-dashed border-[#C7D3CE] bg-[#F9FBFA] text-xs text-[#6B7A75]">
                Gráfico pendiente
            </div>
        </div>
    </div>

</div>
@endsection
