@extends('layouts.app')

@section('title', 'Centro de Mando Ejecutivo')
@section('page-title', 'Centro de Mando Ejecutivo')
@section('page-subtitle', 'Supervisión comunal de cumplimiento, semáforo de metas e indicadores críticos de la red asistencial')

@section('content')
<div class="space-y-8">

    {{-- Barra de Filtros Ejecutivos --}}
    <form method="GET" action="{{ route('centro-mando') }}" class="flex flex-wrap items-center gap-3 rounded-xl border border-[#E2E8F0] bg-white p-4 shadow-xs">
        
        {{-- Búsqueda por texto libre --}}
        <div class="relative w-full flex-1 sm:min-w-[240px]">
            <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[#94A3B8]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input
                type="text"
                name="q"
                value="{{ $filtros['q'] }}"
                placeholder="Buscar por código (ej: MS-01, IAAPS-03) o nombre..."
                class="w-full rounded-lg border border-[#CBD5E1] bg-[#F8FAFC] py-2 pl-10 pr-3 text-sm text-brand-950 placeholder:text-[#94A3B8] focus:border-accent-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-500/20"
            >
        </div>

        {{-- Selector de Periodo (Año-Mes) --}}
        <div class="w-full sm:w-auto min-w-0 sm:min-w-[160px]">
            <select
                name="periodo"
                onchange="this.form.submit()"
                class="w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm font-medium text-brand-950 focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
            >
                @forelse ($periodosDisponibles as $per)
                    <option value="{{ $per['key'] }}" @selected($filtros['periodo'] === $per['key'])>
                        Periodo: {{ $per['label'] }}
                    </option>
                @empty
                    <option value="{{ $periodoSeleccionado }}">
                        Periodo: {{ $mesNombre }} {{ $año }}
                    </option>
                @endforelse
            </select>
        </div>

        {{-- Selector de Establecimiento --}}
        <div class="w-full sm:w-auto min-w-0 sm:min-w-[200px]">
            <select
                name="establecimiento_id"
                onchange="this.form.submit()"
                class="w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm font-medium text-brand-950 focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
            >
                <option value="todos" @selected($filtros['establecimiento_id'] === 'todos')>
                    Establecimiento: Toda la Red Comunal
                </option>
                @foreach ($establecimientos as $est)
                    <option value="{{ $est->id }}" @selected((string)$filtros['establecimiento_id'] === (string)$est->id)>
                        {{ $est->nombre }} ({{ $est->codigo_deis ?? 'S/C' }})
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Selector de Tipo de Indicador --}}
        <div class="w-full sm:w-auto min-w-0 sm:min-w-[170px]">
            <select
                name="tipo"
                onchange="this.form.submit()"
                class="w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm font-medium text-brand-950 focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
            >
                <option value="todos" @selected($filtros['tipo'] === 'todos')>Tipo: Todos</option>
                <option value="Meta Sanitaria" @selected($filtros['tipo'] === 'Meta Sanitaria')>Metas Sanitarias</option>
                <option value="IAAPS" @selected($filtros['tipo'] === 'IAAPS')>IAAPS</option>
            </select>
        </div>

        {{-- Selector de Estado / Semáforo --}}
        <div class="w-full sm:w-auto min-w-0 sm:min-w-[150px]">
            <select
                name="estado"
                onchange="this.form.submit()"
                class="w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm font-medium text-brand-950 focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
            >
                <option value="todos" @selected($filtros['estado'] === 'todos')>Estado: Todos</option>
                <option value="criticos" @selected($filtros['estado'] === 'criticos')>🚨 Críticos y En Riesgo</option>
                <option value="Cumpliendo" @selected($filtros['estado'] === 'Cumpliendo')>✅ Cumpliendo</option>
                <option value="En Riesgo" @selected($filtros['estado'] === 'En Riesgo')>⚠️ En Riesgo</option>
                <option value="Crítico" @selected($filtros['estado'] === 'Crítico')>⛔ Crítico</option>
            </select>
        </div>

        {{-- Botones de Acción --}}
        <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto pt-1 sm:pt-0">
            <button
                type="submit"
                class="flex-1 sm:flex-initial text-center rounded-lg bg-brand-950 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-brand-800 transition-colors"
            >
                Filtrar
            </button>
            @if ($filtros['q'] || $filtros['establecimiento_id'] !== 'todos' || $filtros['tipo'] !== 'todos' || $filtros['estado'] !== 'todos')
                <a
                    href="{{ route('centro-mando', ['periodo' => $filtros['periodo']]) }}"
                    class="rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm font-medium text-[#64748B] hover:bg-[#F8FAFC] transition-colors text-center"
                    title="Limpiar filtros"
                >
                    Limpiar
                </a>
            @endif
            <a
                href="{{ route('importacion') }}"
                class="flex items-center justify-center gap-1.5 rounded-lg border border-accent-600/30 bg-[#EEF5FF] px-3.5 py-2 text-sm font-semibold text-accent-700 hover:bg-accent-100 transition-colors"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                Importar REM
            </a>
        </div>
    </form>

    {{-- Panel de KPIs Ejecutivos (Tarjetas Rápidas) --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        
        {{-- KPI 1: Cumplimiento Promedio Global --}}
        <div class="rounded-xl border border-[#E2E8F0] bg-white p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-[#64748B]">Cumplimiento Promedio</span>
                <span @class([
                    'flex h-9 w-9 items-center justify-center rounded-lg text-sm font-bold shadow-xs',
                    'bg-[#E7F9EF] text-[#16A34A]' => $cumplimientoPromedio >= 85,
                    'bg-[#FFF4E5] text-[#F97316]' => $cumplimientoPromedio >= 65 && $cumplimientoPromedio < 85,
                    'bg-[#FDEDEC] text-[#E11D2E]' => $cumplimientoPromedio < 65,
                ])>
                    %
                </span>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <p class="text-3xl font-extrabold text-brand-950">{{ $cumplimientoPromedio }}%</p>
                <span @class([
                    'inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold',
                    'bg-[#E7F9EF] text-[#16A34A]' => $cumplimientoPromedio >= 85,
                    'bg-[#FFF4E5] text-[#F97316]' => $cumplimientoPromedio >= 65 && $cumplimientoPromedio < 85,
                    'bg-[#FDEDEC] text-[#E11D2E]' => $cumplimientoPromedio < 65,
                ])>
                    @if ($cumplimientoPromedio >= 85)
                        Rendimiento Óptimo
                    @elseif ($cumplimientoPromedio >= 65)
                        Monitoreo Activo
                    @else
                        Atención Urgente
                    @endif
                </span>
            </div>
            <p class="mt-1 text-xs text-[#64748B]">
                Sobre {{ $totalMediciones }} indicadores evaluados en {{ $mesNombre }} {{ $año }}
            </p>
        </div>

        {{-- KPI 2: Semáforo Comunal de Indicadores --}}
        <div class="rounded-xl border border-[#E2E8F0] bg-white p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-[#64748B]">Semáforo Comunal</span>
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-100 text-brand-900 shadow-xs">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </span>
            </div>
            <div class="mt-3 flex items-center gap-2">
                <div class="flex-1 rounded-lg bg-[#E7F9EF] p-2 text-center" title="Cumpliendo meta">
                    <span class="block text-lg font-bold text-[#16A34A]">{{ $cumpliendoCount }}</span>
                    <span class="block text-[10px] font-semibold uppercase text-[#16A34A]">Cumple</span>
                </div>
                <div class="flex-1 rounded-lg bg-[#FFF4E5] p-2 text-center" title="En riesgo de incumplir">
                    <span class="block text-lg font-bold text-[#F97316]">{{ $enRiesgoCount }}</span>
                    <span class="block text-[10px] font-semibold uppercase text-[#F97316]">Riesgo</span>
                </div>
                <div class="flex-1 rounded-lg bg-[#FDEDEC] p-2 text-center" title="Crítico bajo meta">
                    <span class="block text-lg font-bold text-[#E11D2E]">{{ $criticoCount }}</span>
                    <span class="block text-[10px] font-semibold uppercase text-[#E11D2E]">Crítico</span>
                </div>
            </div>
            <p class="mt-1 text-xs text-[#64748B]">
                {{ $totalMediciones > 0 ? round(($cumpliendoCount / $totalMediciones) * 100, 0) : 0 }}% de la cartera con meta lograda
            </p>
        </div>

        {{-- KPI 3: Población Beneficiaria --}}
        <div class="rounded-xl border border-[#E2E8F0] bg-white p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-[#64748B]">Población Beneficiaria</span>
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-accent-50 text-accent-600 shadow-xs">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </span>
            </div>
            <p class="mt-3 text-3xl font-extrabold text-brand-950">{{ number_format($poblacionBeneficiaria, 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-[#64748B] truncate" title="{{ $establecimientoNombre }}">
                {{ $establecimientoNombre }}
            </p>
        </div>

        {{-- KPI 4: Balance de Programas --}}
        <div class="rounded-xl border border-[#E2E8F0] bg-white p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-[#64748B]">Líneas Asistenciales</span>
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-100 text-brand-800 shadow-xs">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                </span>
            </div>
            <div class="mt-3 flex items-center justify-between text-sm">
                <div class="flex items-center gap-1.5">
                    <span class="h-2.5 w-2.5 rounded-full bg-accent-500"></span>
                    <span class="text-xs text-[#475569]">Metas Sanitarias:</span>
                </div>
                <span class="font-bold text-brand-950">{{ $metasCount }}</span>
            </div>
            <div class="mt-1.5 flex items-center justify-between text-sm">
                <div class="flex items-center gap-1.5">
                    <span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span>
                    <span class="text-xs text-[#475569]">IAAPS:</span>
                </div>
                <span class="font-bold text-brand-950">{{ $iaapsCount }}</span>
            </div>
            <p class="mt-2 text-[11px] text-[#94A3B8]">
                Mediciones sincronizadas vía planillas oficiales REM
            </p>
        </div>

    </div>

    {{-- SECCIÓN CRÍTICA DESTACADA: Indicadores Críticos del Momento (Atención Prioritaria) --}}
    <div class="rounded-xl border border-[#FCA5A5]/60 bg-gradient-to-br from-white via-white to-[#FEF2F2] p-6 shadow-xs">
        
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-[#FEE2E2] pb-4">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#FDEDEC] text-[#E11D2E] shadow-xs">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </span>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-lg font-bold text-brand-950">Indicadores Críticos y en Riesgo del Momento</h2>
                        <span class="rounded-full bg-[#FDEDEC] px-2.5 py-0.5 text-xs font-bold text-[#E11D2E]">
                            {{ count($indicadoresCriticos) }} requieren atención
                        </span>
                    </div>
                    <p class="text-xs text-[#64748B]">
                        Indicadores de la red asistencial con rendimiento bajo la meta (< 85% o < 100%) en {{ $mesNombre }} {{ $año }}.
                    </p>
                </div>
            </div>

            <a
                href="{{ route('importacion') }}"
                class="inline-flex w-full sm:w-auto justify-center items-center gap-1.5 rounded-lg bg-brand-950 px-3.5 py-2 text-xs font-semibold text-white shadow-xs hover:bg-brand-800 transition-colors"
            >
                <span>Actualizar Planilla REM</span>
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </a>
        </div>

        @if (count($indicadoresCriticos) > 0)
            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($indicadoresCriticos->take(6) as $critico)
                    <div class="flex flex-col justify-between rounded-lg border border-[#FCD34D]/80 bg-white p-4 shadow-xs hover:border-[#F59E0B] transition-all">
                        <div>
                            {{-- Cabecera de tarjeta --}}
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-mono text-sm font-bold text-brand-950">{{ $critico['codigo'] }}</span>
                                <div class="flex items-center gap-1.5">
                                    @if ($critico['tipo'] === 'IAAPS')
                                        <span class="rounded bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-800 border border-amber-200">IAAPS</span>
                                    @else
                                        <span class="rounded bg-blue-50 px-2 py-0.5 text-[11px] font-semibold text-blue-800 border border-blue-200">Meta Sanitaria</span>
                                    @endif
                                    @if ($critico['status'] === 'Crítico')
                                        <span class="rounded-full bg-[#FDEDEC] px-2 py-0.5 text-[11px] font-bold text-[#E11D2E]">Crítico</span>
                                    @else
                                        <span class="rounded-full bg-[#FFF4E5] px-2 py-0.5 text-[11px] font-bold text-[#F97316]">En Riesgo</span>
                                    @endif
                                </div>
                            </div>

                            {{-- Nombre del indicador --}}
                            <h3 class="mt-2 text-xs font-semibold text-[#1E293B] line-clamp-2" title="{{ $critico['nombre'] }}">
                                {{ $critico['nombre'] }}
                            </h3>

                            <p class="mt-1 text-[11px] text-[#64748B]">
                                Centro: <strong class="text-brand-950">{{ $critico['establecimiento'] }}</strong>
                            </p>
                        </div>

                        {{-- Progreso y Brecha --}}
                        <div class="mt-4 space-y-2 border-t border-[#F1F5F9] pt-3">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-[#64748B]">Cumplimiento Actual:</span>
                                <span class="font-bold text-[#E11D2E]">{{ number_format($critico['porcentaje'], 1) }}%</span>
                            </div>

                            <div class="h-2 w-full overflow-hidden rounded-full bg-[#E2E8F0]">
                                <div
                                    class="h-full rounded-full {{ $critico['status'] === 'Crítico' ? 'bg-[#E11D2E]' : 'bg-[#F97316]' }}"
                                    style="width: {{ min(max($critico['porcentaje'], 0), 100) }}%"
                                ></div>
                            </div>

                            <div class="flex items-center justify-between text-[11px] text-[#64748B]">
                                <span>Meta: <strong class="text-brand-950">{{ number_format($critico['target'], 1) }}%</strong></span>
                                <span class="font-semibold text-[#E11D2E]">
                                    Brecha: {{ $critico['brecha'] }}%
                                </span>
                            </div>

                            <div class="flex items-center justify-between text-[11px] text-[#94A3B8] font-mono">
                                <span>Num: {{ number_format($critico['numerador'], 0, ',', '.') }}</span>
                                <span>Den: {{ number_format($critico['denominador'], 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if (count($indicadoresCriticos) > 6)
                <p class="mt-3 text-right text-xs text-[#64748B]">
                    Mostrando los 6 indicadores más desfavorables de un total de {{ count($indicadoresCriticos) }} críticos.
                </p>
            @endif
        @else
            <div class="mt-4 flex items-center gap-3 rounded-lg bg-[#E7F9EF] p-4 text-[#16A34A]">
                <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <div class="text-sm">
                    <p class="font-bold">¡Excelente Desempeño!</p>
                    <p class="text-xs text-[#15803D]">Todos los indicadores evaluados en el periodo se encuentran dentro de los parámetros esperados de cumplimiento.</p>
                </div>
            </div>
        @endif
    </div>

    {{-- MOTOR DE PROYECCIÓN ESTADÍSTICA PREDICTIVA · CIERRE ANUAL (MES 12) --}}
    <div class="rounded-xl border border-indigo-200/80 bg-gradient-to-br from-white via-white to-indigo-50/40 p-6 shadow-xs space-y-6">
        
        {{-- Encabezado del Motor Predictivo --}}
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-indigo-100 pb-4">
            <div class="flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-xs">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </span>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-lg font-bold text-brand-950">Motor de Proyección Estadística · Estimación de Cierre Anual</h2>
                        <span class="rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-bold text-indigo-700 border border-indigo-200">
                            Regresión Lineal Simple + Media Móvil (SMA-3)
                        </span>
                        <span class="rounded-full bg-brand-50 px-2.5 py-0.5 text-[11px] font-semibold text-brand-800 border border-brand-200">
                            Horizonte: Mes 12 (Diciembre {{ $año }})
                        </span>
                    </div>
                    <p class="text-xs text-[#64748B]">
                        Anticipa el cumplimiento anual de la cartera según la tasa de cambio y tendencia histórica observada hasta {{ $mesNombre }} {{ $año }}.
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2 text-xs">
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-[#475569] border border-[#CBD5E1] shadow-2xs">
                    <span class="h-2 w-2 rounded-full bg-indigo-500"></span>
                    Corte actual: <strong>{{ $mesNombre }} (Mes {{ $mes }})</strong>
                </span>
            </div>
        </div>

        {{-- Tarjetas KPI de Pronóstico Anual --}}
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
            {{-- KPI 1: Tasa de Éxito Anual Proyectada --}}
            <div class="rounded-lg border border-indigo-100 bg-white p-4 shadow-2xs">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-[#64748B]">Éxito Proyectado (Meta Lograda)</span>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl font-extrabold text-indigo-700">{{ $proyecciones['resumen']['tasa_exito_proyectada'] ?? 0 }}%</span>
                </div>
                <p class="mt-1 text-[11px] text-[#64748B]">
                    {{ $proyecciones['resumen']['cumpliran_count'] ?? 0 }} de {{ $proyecciones['resumen']['total_evaluados'] ?? 0 }} indicadores sobre meta
                </p>
            </div>

            {{-- KPI 2: Cumplirán Meta --}}
            <div class="rounded-lg border border-[#BBF7D0] bg-[#F0FDF4] p-4 shadow-2xs">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-[#15803D]">Cumplirán Meta</span>
                    <span class="flex h-5 w-5 items-center justify-center rounded-full bg-[#DCFCE7] text-[#16A34A] text-xs">✓</span>
                </div>
                <div class="mt-2 flex items-baseline gap-1">
                    <span class="text-2xl font-extrabold text-[#15803D]">{{ $proyecciones['resumen']['cumpliran_count'] ?? 0 }}</span>
                    <span class="text-xs text-[#15803D] font-medium">indicadores</span>
                </div>
                <p class="mt-1 text-[11px] text-[#16A34A]">Proyectan alcanzar o superar el 100%</p>
            </div>

            {{-- KPI 3: En Riesgo al Cierre --}}
            <div class="rounded-lg border border-[#FED7AA] bg-[#FFFBEB] p-4 shadow-2xs">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-[#B45309]">En Riesgo al Cierre</span>
                    <span class="flex h-5 w-5 items-center justify-center rounded-full bg-[#FEF3C7] text-[#D97706] text-xs font-bold">!</span>
                </div>
                <div class="mt-2 flex items-baseline gap-1">
                    <span class="text-2xl font-extrabold text-[#B45309]">{{ $proyecciones['resumen']['en_riesgo_count'] ?? 0 }}</span>
                    <span class="text-xs text-[#B45309] font-medium">indicadores</span>
                </div>
                <p class="mt-1 text-[11px] text-[#D97706]">Cierre proyectado entre 85% y 99%</p>
            </div>

            {{-- KPI 4: Críticos al Cierre --}}
            <div class="rounded-lg border border-[#FECACA] bg-[#FEF2F2] p-4 shadow-2xs">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-[#B91C1C]">Críticos al Cierre</span>
                    <span class="flex h-5 w-5 items-center justify-center rounded-full bg-[#FEE2E2] text-[#DC2626] text-xs font-bold">✕</span>
                </div>
                <div class="mt-2 flex items-baseline gap-1">
                    <span class="text-2xl font-extrabold text-[#B91C1C]">{{ $proyecciones['resumen']['criticos_count'] ?? 0 }}</span>
                    <span class="text-xs text-[#B91C1C] font-medium">indicadores</span>
                </div>
                <p class="mt-1 text-[11px] text-[#DC2626]">Déficit proyectado severo (< 85%)</p>
            </div>

            {{-- KPI 5: Cumplimiento Promedio Proyectado --}}
            <div class="rounded-lg border border-[#E2E8F0] bg-white p-4 shadow-2xs">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-[#64748B]">Cierre Promedio Anual</span>
                <div class="mt-2 flex items-baseline gap-1">
                    <span class="text-2xl font-extrabold text-brand-950">{{ $proyecciones['resumen']['cumplimiento_promedio_proyectado'] ?? 0 }}%</span>
                </div>
                <p class="mt-1 text-[11px] text-[#64748B]">Estimación comunal al mes 12</p>
            </div>
        </div>

        {{-- Gráfico Interactivo de Trayectoria Anual --}}
        <div class="rounded-xl border border-indigo-100 bg-white p-5 shadow-2xs">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h3 class="text-sm font-bold text-brand-950 flex items-center gap-2">
                        <span>Trayectoria Anual: Avance Real vs Proyección Estadística a Diciembre</span>
                    </h3>
                    <p class="text-xs text-[#64748B]">
                        Comparación de la senda real observada (meses 1..{{ $mes }}) y las proyecciones de Regresión Lineal y Media Móvil (meses {{ min(12, $mes + 1) }}..12).
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-3 text-[11px] font-medium text-[#475569]">
                    <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-[#2f6fed]"></span> Real Observado</span>
                    <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-[#8B5CF6]"></span> Regresión Lineal</span>
                    <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-[#06B6D4]"></span> Media Móvil (SMA-3)</span>
                    <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-[#10B981]"></span> Meta Objetivo</span>
                </div>
            </div>
            <div id="chart-proyeccion-anual" class="min-h-[320px]"></div>
        </div>

        {{-- Detalle de Indicadores con Proyección Estadística --}}
        <div class="rounded-xl border border-[#E2E8F0] bg-white p-5 shadow-2xs">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#F1F5F9] pb-4">
                <div>
                    <h3 class="text-sm font-bold text-brand-950">Detalle de Indicadores · Proyección y Anticipación Anual</h3>
                    <p class="text-xs text-[#64748B]">Tendencia mensual, pendiente (ritmo), cálculo de modelos y pronóstico al cierre del ejercicio.</p>
                </div>

                {{-- Filtro rápido de pestañas --}}
                <div class="flex items-center gap-1.5 rounded-lg bg-[#F1F5F9] p-1 text-xs font-semibold">
                    <button type="button" onclick="filtrarProyecciones('todos', this)" class="btn-tab-proy rounded-md bg-white px-3 py-1 text-brand-950 shadow-2xs">
                        Todos ({{ count($proyecciones['indicadores_proyectados']) }})
                    </button>
                    <button type="button" onclick="filtrarProyecciones('Crítico', this)" class="btn-tab-proy rounded-md px-3 py-1 text-[#DC2626] hover:bg-white/80">
                        Críticos ({{ $proyecciones['resumen']['criticos_count'] }})
                    </button>
                    <button type="button" onclick="filtrarProyecciones('En Riesgo', this)" class="btn-tab-proy rounded-md px-3 py-1 text-[#D97706] hover:bg-white/80">
                        En Riesgo ({{ $proyecciones['resumen']['en_riesgo_count'] }})
                    </button>
                    <button type="button" onclick="filtrarProyecciones('Cumplirá', this)" class="btn-tab-proy rounded-md px-3 py-1 text-[#16A34A] hover:bg-white/80">
                        Cumplirán ({{ $proyecciones['resumen']['cumpliran_count'] }})
                    </button>
                </div>
            </div>

            {{-- Tabla de Indicadores --}}
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-[#E2E8F0] text-[11px] font-bold uppercase tracking-wider text-[#64748B]">
                            <th class="py-3 px-3">Indicador</th>
                            <th class="py-3 px-3">Actual (Mes {{ $mes }})</th>
                            <th class="py-3 px-3">Ritmo Mensual</th>
                            <th class="py-3 px-3">Reg. Lineal</th>
                            <th class="py-3 px-3">Media Móvil</th>
                            <th class="py-3 px-3">Pronóstico Cierre</th>
                            <th class="py-3 px-3">Meta Anual</th>
                            <th class="py-3 px-3">Diagnóstico / Alerta</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#F1F5F9]" id="tabla-proyecciones-body">
                        @forelse ($proyecciones['indicadores_proyectados'] as $p)
                            <tr class="fila-proyeccion hover:bg-[#F8FAFC] transition-colors" data-estado="{{ $p['estado_anticipado'] }}">
                                <td class="py-3 px-3 max-w-[240px]">
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-mono font-bold text-brand-950">{{ $p['codigo'] }}</span>
                                        <span @class([
                                            'rounded px-1.5 py-0.5 text-[10px] font-semibold border',
                                            'bg-blue-50 text-blue-700 border-blue-200' => $p['tipo'] === 'Meta Sanitaria',
                                            'bg-amber-50 text-amber-800 border-amber-200' => $p['tipo'] === 'IAAPS',
                                            'bg-gray-50 text-gray-700 border-gray-200' => !in_array($p['tipo'], ['Meta Sanitaria', 'IAAPS']),
                                        ])>
                                            {{ $p['tipo'] }}
                                        </span>
                                    </div>
                                    <p class="mt-0.5 text-[11px] text-[#475569] line-clamp-1" title="{{ $p['nombre'] }}">
                                        {{ $p['nombre'] }}
                                    </p>
                                </td>
                                <td class="py-3 px-3 font-semibold text-brand-950">
                                    {{ number_format($p['cumplimiento_actual'], 1) }}%
                                </td>
                                <td class="py-3 px-3">
                                    @php $pend = $p['regresion']['pendiente']; @endphp
                                    @if ($pend > 0.25)
                                        <span class="inline-flex items-center gap-1 font-semibold text-[#16A34A]" title="Tendencia favorable al alza">
                                            ↗ +{{ number_format($pend, 1) }}%/m
                                        </span>
                                    @elseif ($pend < -0.25)
                                        <span class="inline-flex items-center gap-1 font-semibold text-[#DC2626]" title="Tendencia desfavorable a la baja">
                                            ↘ {{ number_format($pend, 1) }}%/m
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-[#64748B]" title="Tendencia estable">
                                            → {{ number_format($pend, 1) }}%/m
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-3 font-mono text-[#475569]">
                                    {{ number_format($p['regresion']['proyeccion_mes_12'], 1) }}%
                                    <span class="block text-[9px] text-[#94A3B8]">R²: {{ number_format($p['regresion']['r2'] * 100, 0) }}%</span>
                                </td>
                                <td class="py-3 px-3 font-mono text-[#475569]">
                                    {{ number_format($p['media_movil']['proyeccion_mes_12'], 1) }}%
                                    <span class="block text-[9px] text-[#94A3B8]">SMA: {{ number_format($p['media_movil']['sma_reciente'], 1) }}%</span>
                                </td>
                                <td class="py-3 px-3">
                                    <div class="flex items-center gap-1.5">
                                        <span @class([
                                            'inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-bold border',
                                            'bg-[#E7F9EF] text-[#16A34A] border-[#BBF7D0]' => $p['estado_anticipado'] === 'Cumplirá',
                                            'bg-[#FFF4E5] text-[#D97706] border-[#FED7AA]' => $p['estado_anticipado'] === 'En Riesgo',
                                            'bg-[#FDEDEC] text-[#DC2626] border-[#FECACA]' => $p['estado_anticipado'] === 'Crítico',
                                        ])>
                                            {{ $p['estado_anticipado'] }}: {{ number_format($p['proyeccion_anual'], 1) }}%
                                        </span>
                                    </div>
                                    <span class="block text-[10px] text-[#64748B] mt-0.5">
                                        Brecha: {{ $p['brecha_proyectada'] >= 0 ? '+' : '' }}{{ number_format($p['brecha_proyectada'], 1) }}%
                                    </span>
                                </td>
                                <td class="py-3 px-3 font-semibold text-brand-950">
                                    {{ number_format($p['target'], 1) }}%
                                </td>
                                <td class="py-3 px-3 max-w-[260px]">
                                    <p class="text-[11px] text-[#475569] leading-snug">
                                        {{ $p['alerta_ejecutiva'] }}
                                    </p>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-8 text-center text-sm text-[#94A3B8]">
                                    No se encontraron indicadores con mediciones históricas en este periodo.
                                </td>
                            </tr>
                        @endforelse
                        <tr id="proy-fila-vacia" class="hidden">
                            <td colspan="8" class="py-8 text-center text-sm text-[#94A3B8]">
                                No se encontraron indicadores para el filtro seleccionado.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Paginación / Tabulación en 5 de la Tabla de Proyecciones --}}
            <div id="proyecciones-paginacion-container" class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-[#F1F5F9] pt-4 text-xs text-[#64748B]">
                <div id="proyecciones-paginacion-info">
                    Mostrando <span id="proy-desde" class="font-bold text-brand-950">0</span> a <span id="proy-hasta" class="font-bold text-brand-950">0</span> de <span id="proy-total" class="font-bold text-brand-950">{{ count($proyecciones['indicadores_proyectados']) }}</span> indicadores (tabulados de 5 en 5)
                </div>

                <div class="flex items-center gap-1.5" id="proyecciones-paginacion-botones">
                    <button
                        type="button"
                        id="btn-proy-prev"
                        onclick="cambiarPaginaProyeccion(-1)"
                        class="rounded-lg border border-[#CBD5E1] bg-white px-3 py-1.5 text-xs font-semibold text-brand-950 shadow-2xs hover:bg-[#F8FAFC] disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                    >
                        Anterior
                    </button>

                    <div id="proyecciones-paginas-numeros" class="flex items-center gap-1"></div>

                    <button
                        type="button"
                        id="btn-proy-next"
                        onclick="cambiarPaginaProyeccion(1)"
                        class="rounded-lg border border-[#CBD5E1] bg-white px-3 py-1.5 text-xs font-semibold text-brand-950 shadow-2xs hover:bg-[#F8FAFC] disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                    >
                        Siguiente
                    </button>
                </div>
            </div>
        </div>

    </div>

    {{-- GRÁFICOS EJECUTIVOS APEXCHARTS --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        
        {{-- Gráfico 1: Cumplimiento vs Meta Nacional / Comunal --}}
        <div class="rounded-xl border border-[#E2E8F0] bg-white p-5 shadow-xs lg:col-span-2">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h3 class="text-base font-bold text-brand-950">Comparativa de Indicadores vs Meta Objetivo</h3>
                    <p class="text-xs text-[#64748B]">Porcentaje de avance actual comparado contra la meta nacional / comunal</p>
                </div>
               
            </div>
            <div id="chart-ejecutivo-barras" class="min-h-[350px]"></div>
        </div>

        {{-- Gráfico 2: Distribución de Estado Ejecutivo --}}
        <div class="rounded-xl border border-[#E2E8F0] bg-white p-5 shadow-xs">
            <div class="mb-4">
                <h3 class="text-base font-bold text-brand-950">Distribución de Cumplimiento</h3>
                <p class="text-xs text-[#64748B]">Proporción según regla técnica MINSAL</p>
            </div>
            <div id="chart-ejecutivo-donut" class="flex min-h-[350px] items-center justify-center"></div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Datos inyectados desde el controlador
    const chartBarrasData = @json($chartBarras);
    const chartDonutData = @json($chartDonut);

    // 1. Gráfico de Barras: Cumplimiento Actual vs Meta Objetivo
    if (document.querySelector("#chart-ejecutivo-barras") && chartBarrasData.categories.length > 0) {
        const barOptions = {
            series: [
                {
                    name: 'Cumplimiento Actual (%)',
                    data: chartBarrasData.cumplimiento
                },
                {
                    name: 'Meta Nacional / Comunal (%)',
                    data: chartBarrasData.objetivos
                }
            ],
            chart: {
                type: 'bar',
                height: 350,
                toolbar: { show: false },
                fontFamily: 'inherit'
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '55%',
                    borderRadius: 4
                }
            },
            dataLabels: { enabled: false },
            stroke: {
                show: true,
                width: 2,
                colors: ['transparent']
            },
            xaxis: {
                categories: chartBarrasData.categories,
                labels: {
                    style: { colors: '#64748B', fontWeight: 600 }
                }
            },
            yaxis: {
                title: { text: 'Porcentaje (%)' },
                max: 120,
                labels: {
                    formatter: (val) => val.toFixed(0) + '%'
                }
            },
            colors: ['#2f6fed', '#0f1424'],
            fill: { opacity: 1 },
            tooltip: {
                y: {
                    formatter: (val) => val.toFixed(2) + '%'
                }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'right'
            },
            responsive: [
                {
                    breakpoint: 640,
                    options: {
                        plotOptions: {
                            bar: {
                                columnWidth: '80%'
                            }
                        },
                        legend: {
                            position: 'bottom',
                            horizontalAlign: 'center'
                        },
                        xaxis: {
                            labels: {
                                rotate: -45,
                                style: { fontSize: '10px' }
                            }
                        }
                    }
                }
            ]
        };

        const chartBarras = new ApexCharts(document.querySelector("#chart-ejecutivo-barras"), barOptions);
        chartBarras.render();
    } else if (document.querySelector("#chart-ejecutivo-barras")) {
        document.querySelector("#chart-ejecutivo-barras").innerHTML = `
            <div class="flex h-64 items-center justify-center text-sm text-[#94A3B8]">
                Sin mediciones registradas para graficar en este periodo.
            </div>
        `;
    }

    // 2. Gráfico Donut: Distribución de Semáforo
    if (document.querySelector("#chart-ejecutivo-donut")) {
        const totalItems = chartDonutData.series.reduce((a, b) => a + b, 0);

        if (totalItems > 0) {
            const donutOptions = {
                series: chartDonutData.series,
                labels: chartDonutData.labels,
                chart: {
                    type: 'donut',
                    height: 330,
                    fontFamily: 'inherit'
                },
                colors: ['#16A34A', '#F97316', '#E11D2E'],
                legend: { position: 'bottom' },
                dataLabels: {
                    enabled: true,
                    formatter: (val) => val.toFixed(0) + '%'
                },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '65%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Indicadores',
                                    formatter: () => totalItems
                                }
                            }
                        }
                    }
                }
            };

            const chartDonut = new ApexCharts(document.querySelector("#chart-ejecutivo-donut"), donutOptions);
            chartDonut.render();
        } else {
            document.querySelector("#chart-ejecutivo-donut").innerHTML = `
                <div class="flex h-64 items-center justify-center text-sm text-[#94A3B8]">
                    Sin indicadores para calcular semáforo.
                </div>
            `;
        }
    }

    // 3. Gráfico de Proyección Anual (Regresión Lineal Simple y Media Móvil)
    const chartProyeccionData = @json($proyecciones['chart_anual'] ?? null);

    if (document.querySelector("#chart-proyeccion-anual") && chartProyeccionData) {
        const proyeccionOptions = {
            series: [
                {
                    name: 'Avance Real Observado (%)',
                    type: 'area',
                    data: chartProyeccionData.series_real
                },
                {
                    name: 'Proyección Regresión Lineal (%)',
                    type: 'line',
                    data: chartProyeccionData.series_regresion
                },
                {
                    name: 'Proyección Media Móvil (SMA-3) (%)',
                    type: 'line',
                    data: chartProyeccionData.series_media_movil
                },
                {
                    name: 'Meta Objetivo Promedio (%)',
                    type: 'line',
                    data: chartProyeccionData.series_target
                }
            ],
            chart: {
                height: 320,
                type: 'line',
                toolbar: { show: false },
                fontFamily: 'inherit'
            },
            colors: ['#2f6fed', '#8B5CF6', '#06B6D4', '#10B981'],
            stroke: {
                width: [3, 2.5, 2.5, 2],
                curve: ['smooth', 'straight', 'smooth', 'straight'],
                dashArray: [0, 5, 3, 4]
            },
            fill: {
                type: ['gradient', 'solid', 'solid', 'solid'],
                gradient: {
                    shade: 'light',
                    type: 'vertical',
                    shadeIntensity: 0.3,
                    opacityFrom: 0.35,
                    opacityTo: 0.05,
                    stops: [0, 90, 100]
                }
            },
            markers: {
                size: [4, 4, 4, 0],
                strokeWidth: 2,
                hover: { size: 6 }
            },
            xaxis: {
                categories: chartProyeccionData.categories,
                labels: {
                    style: { colors: '#64748B', fontWeight: 600 }
                }
            },
            yaxis: {
                title: { text: 'Cumplimiento (%)' },
                labels: {
                    formatter: (val) => val !== null && val !== undefined ? val.toFixed(0) + '%' : ''
                }
            },
            tooltip: {
                shared: true,
                intersect: false,
                y: {
                    formatter: (val) => val !== null && val !== undefined ? val.toFixed(1) + '%' : 'N/A'
                }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'right'
            }
        };

        const chartProyeccion = new ApexCharts(document.querySelector("#chart-proyeccion-anual"), proyeccionOptions);
        chartProyeccion.render();
    }

    // 4. Tabulación y Paginación en 5 de la Tabla de Proyecciones
    const PROY_POR_PAGINA = 5;
    let proyPaginaActual = 1;
    let proyEstadoActivo = 'todos';

    function renderizarPaginacionProyecciones() {
        const todasFilas = Array.from(document.querySelectorAll('.fila-proyeccion'));
        if (todasFilas.length === 0) {
            const cont = document.querySelector('#proyecciones-paginacion-container');
            if (cont) cont.style.display = 'none';
            return;
        }

        // Filtrar filas según el estado activo en las pestañas
        const filasFiltradas = todasFilas.filter(row => {
            return proyEstadoActivo === 'todos' || row.dataset.estado === proyEstadoActivo;
        });

        const totalFiltradas = filasFiltradas.length;
        const totalPaginas = Math.max(1, Math.ceil(totalFiltradas / PROY_POR_PAGINA));

        if (proyPaginaActual > totalPaginas) {
            proyPaginaActual = totalPaginas;
        }
        if (proyPaginaActual < 1) {
            proyPaginaActual = 1;
        }

        // Mostrar u ocultar fila de estado vacío
        const filaVacia = document.querySelector('#proy-fila-vacia');
        if (filaVacia) {
            filaVacia.classList.toggle('hidden', totalFiltradas > 0);
        }

        // Ocultar todas las filas y mostrar solo las correspondientes a la página activa
        const inicioIndice = (proyPaginaActual - 1) * PROY_POR_PAGINA;
        const finIndice = inicioIndice + PROY_POR_PAGINA;

        todasFilas.forEach(row => {
            row.style.display = 'none';
        });

        filasFiltradas.forEach((row, idx) => {
            if (idx >= inicioIndice && idx < finIndice) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });

        // Actualizar información textual de paginación
        const elDesde = document.querySelector('#proy-desde');
        const elHasta = document.querySelector('#proy-hasta');
        const elTotal = document.querySelector('#proy-total');

        if (elDesde && elHasta && elTotal) {
            const desdeNum = totalFiltradas > 0 ? inicioIndice + 1 : 0;
            const hastaNum = Math.min(finIndice, totalFiltradas);
            elDesde.textContent = desdeNum;
            elHasta.textContent = hastaNum;
            elTotal.textContent = totalFiltradas;
        }

        // Estado de botones Anterior / Siguiente
        const btnPrev = document.querySelector('#btn-proy-prev');
        const btnNext = document.querySelector('#btn-proy-next');

        if (btnPrev) btnPrev.disabled = (proyPaginaActual <= 1);
        if (btnNext) btnNext.disabled = (proyPaginaActual >= totalPaginas || totalFiltradas === 0);

        // Renderizar números de página
        const contenedorNumeros = document.querySelector('#proyecciones-paginas-numeros');
        if (contenedorNumeros) {
            contenedorNumeros.innerHTML = '';

            for (let i = 1; i <= totalPaginas; i++) {
                const btnNum = document.createElement('button');
                btnNum.type = 'button';
                btnNum.textContent = i;
                btnNum.onclick = () => window.irAPaginaProyeccion(i);

                if (i === proyPaginaActual) {
                    btnNum.className = 'h-7 w-7 rounded-lg bg-indigo-600 text-xs font-bold text-white shadow-2xs flex items-center justify-center';
                } else {
                    btnNum.className = 'h-7 w-7 rounded-lg border border-[#CBD5E1] bg-white text-xs font-medium text-[#475569] hover:bg-[#F8FAFC] shadow-2xs flex items-center justify-center';
                }

                contenedorNumeros.appendChild(btnNum);
            }
        }
    }

    window.cambiarPaginaProyeccion = function(delta) {
        proyPaginaActual += delta;
        renderizarPaginacionProyecciones();
    };

    window.irAPaginaProyeccion = function(numPagina) {
        proyPaginaActual = numPagina;
        renderizarPaginacionProyecciones();
    };

    window.filtrarProyecciones = function(estado, btn) {
        document.querySelectorAll('.btn-tab-proy').forEach(b => {
            b.classList.remove('bg-white', 'shadow-2xs');
        });
        btn.classList.add('bg-white', 'shadow-2xs');

        proyEstadoActivo = estado;
        proyPaginaActual = 1;
        renderizarPaginacionProyecciones();
    };

    // Inicializar la tabulación en 5 inmediatamente
    renderizarPaginacionProyecciones();
});
</script>
@endpush
