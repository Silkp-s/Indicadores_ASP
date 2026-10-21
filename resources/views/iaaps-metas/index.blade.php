@extends('layouts.app')

@section('title', 'IAAPS y Metas')
@section('page-title', 'Módulo 2 · IAAPS y Metas Sanitarias')
@section('page-subtitle', 'Mantenedor de indicadores IAAPS y Metas Sanitarias con desglose por establecimiento')

@section('content')
<div class="space-y-6">

    {{-- Notificaciones flash de éxito o advertencia --}}
    @if (session('status'))
        <div class="flex items-center gap-3 rounded-xl border border-status-ok-fg/30 bg-status-ok-bg p-4 text-sm font-medium text-status-ok-fg shadow-xs">
            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="flex items-start gap-3 rounded-xl border border-status-bad-fg/30 bg-status-bad-bg p-4 text-sm text-status-bad-fg shadow-xs">
            <svg class="h-5 w-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div>
                <p class="font-bold">Ocurrieron errores al procesar la solicitud:</p>
                <ul class="list-disc list-inside mt-1 space-y-0.5 text-xs">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- Barra de filtros y acciones --}}
    <div class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-[#E2E8F0] bg-white p-4 shadow-xs">
        
        <form method="GET" action="{{ route('iaaps-metas.index') }}" data-filtros class="flex flex-1 flex-wrap items-center gap-3">
            
            {{-- Búsqueda por código o nombre --}}
            <div class="relative w-full flex-1 sm:min-w-[240px]">
                <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[#94A3B8]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input
                    type="text"
                    name="q"
                    value="{{ $filtros['q'] }}"
                    placeholder="Buscar por código o nombre del indicador..."
                    class="w-full rounded-lg border border-[#CBD5E1] bg-[#F8FAFC] py-2 pl-10 pr-3 text-sm text-brand-950 placeholder:text-[#94A3B8] focus:border-accent-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                >
            </div>

            {{-- Selector de Periodo (Mes y Año) --}}
            <div class="w-full sm:w-auto min-w-0 sm:min-w-[170px]">
                <select
                    name="periodo"
                    onchange="this.form.submit()"
                    class="w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm font-medium text-brand-950 focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                    title="Periodo de medición para el desglose por establecimiento"
                >
                    @forelse ($periodosDisponibles as $p)
                        <option value="{{ $p['key'] }}" @selected($filtros['periodo'] === $p['key'])>
                            Periodo: {{ $p['label'] }}
                        </option>
                    @empty
                        <option value="{{ $periodoSeleccionado }}">
                            Periodo: {{ $periodoSeleccionado }}
                        </option>
                    @endforelse
                </select>
            </div>

            {{-- Filtro por Tipo --}}
            <div class="w-full sm:w-auto min-w-0 sm:min-w-[170px]">
                <select
                    name="tipo"
                    onchange="this.form.submit()"
                    class="w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm font-medium text-brand-950 focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                >
                    <option value="todas" @selected($filtros['tipo'] === 'todas')>Ver: IAAPS y Metas</option>
                    <option value="Meta Sanitaria" @selected($filtros['tipo'] === 'Meta Sanitaria')>Solo Metas Sanitarias</option>
                    <option value="IAAPS" @selected($filtros['tipo'] === 'IAAPS')>Solo IAAPS</option>
                </select>
            </div>

            {{-- Filtro por Estado --}}
            <div class="w-full sm:w-auto min-w-0 sm:min-w-[150px]">
                <select
                    name="estado"
                    onchange="this.form.submit()"
                    class="w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm font-medium text-brand-950 focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                >
                    <option value="todos" @selected($filtros['estado'] === 'todos')>Estado: Todos</option>
                    <option value="activo" @selected($filtros['estado'] === 'activo')>Activos</option>
                    <option value="inactivo" @selected($filtros['estado'] === 'inactivo')>Inactivos</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="submit"
                    class="rounded-lg bg-brand-950 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-brand-800 transition-colors"
                >
                    Filtrar
                </button>
                @if ($filtros['q'] || $filtros['tipo'] !== 'todas' || $filtros['estado'] !== 'todos')
                    <a
                        href="{{ route('iaaps-metas.index', ['periodo' => $filtros['periodo']]) }}"
                        class="rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm font-medium text-[#64748B] hover:bg-[#F8FAFC] transition-colors"
                        title="Limpiar filtros"
                    >
                        Limpiar
                    </a>
                @endif
            </div>
        </form>

    </div>

    {{-- Tabla / Mantenedor principal (Paginada de 10 en 10) --}}
    <div class="overflow-hidden rounded-xl border border-[#E2E8F0] bg-white shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[850px] text-left text-sm" id="tabla-iaaps-metas">
                <thead class="bg-[#F8FAFC] text-xs font-semibold uppercase tracking-wider text-[#64748B] border-b border-[#E2E8F0]">
                    <tr>
                        <th class="w-12 px-4 py-3.5 text-center" aria-label="Expandir"></th>
                        <th class="px-5 py-3.5">Código</th>
                        <th class="px-5 py-3.5">Nombre del Indicador</th>
                        <th class="px-5 py-3.5">Tipo</th>
                        <th class="px-5 py-3.5 text-right">Meta</th>
                        <th class="px-5 py-3.5">Periodicidad / Fuente</th>
                        <th class="px-5 py-3.5 text-center">Estado</th>
                        <th class="px-5 py-3.5 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E2E8F0]" id="iaaps-metas-filas">
                    @forelse ($indicadores as $indicador)
                        @php
                            $datosJson = json_encode([
                                'id' => $indicador->id,
                                'codigo' => $indicador->codigo,
                                'nombre' => $indicador->nombre,
                                'tipo' => $indicador->tipo,
                                'target_value' => (float) $indicador->target_value,
                                'periodicidad' => $indicador->periodicidad,
                                'fu' => $indicador->fu,
                                'numerador_description' => $indicador->numerador_description,
                                'denominador_description' => $indicador->denominador_description,
                                'is_active' => (bool) $indicador->is_active,
                                'url_actualizar' => route('iaaps-metas.update', $indicador),
                            ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

                            // Medidas cargadas para este indicador en el periodo seleccionado
                            $medidasConValores = $indicador->medidas->filter(fn ($m) => $m->numerador_value !== null || $m->denominador_value !== null);
                            $totalNum = $medidasConValores->sum(fn ($m) => (float) $m->numerador_value);
                            $totalDen = $medidasConValores->sum(fn ($m) => (float) $m->denominador_value);
                            $cumplimientoGlobal = $totalDen > 0 ? round(($totalNum / $totalDen) * 100, 1) : null;
                        @endphp

                        {{-- Fila Principal del Indicador (Clickable para desplegar) --}}
                        <tr
                            class="hover:bg-[#F8FAFC]/80 transition-colors cursor-pointer group"
                            data-indicador="{{ $datosJson }}"
                            data-id="{{ $indicador->id }}"
                            onclick="window.toggleDetalle({{ $indicador->id }}, event)"
                        >
                            {{-- Icono de Despliegue tipo acordeón --}}
                            <td class="px-4 py-3.5 text-center">
                                <button
                                    type="button"
                                    id="btn-chevron-{{ $indicador->id }}"
                                    class="inline-flex items-center justify-center h-7 w-7 rounded-lg text-[#64748B] group-hover:text-brand-950 hover:bg-[#E2E8F0]/70 transition-transform duration-200"
                                    title="Desplegar u ocultar desglose de establecimientos"
                                    aria-label="Desplegar u ocultar desglose de establecimientos"
                                >
                                    <svg id="chevron-icon-{{ $indicador->id }}" class="h-4 w-4 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                            </td>

                            {{-- Código --}}
                            <td class="px-5 py-3.5 font-bold font-mono text-brand-950 whitespace-nowrap">
                                <span class="bg-[#F1F5F9] px-2 py-0.5 rounded text-xs border border-[#CBD5E1]">
                                    {{ $indicador->codigo }}
                                </span>
                            </td>

                            {{-- Nombre del Indicador --}}
                            <td class="px-5 py-3.5">
                                <div class="font-medium text-brand-950 group-hover:text-accent-600 transition-colors">
                                    {{ $indicador->nombre }}
                                </div>
                                @if ($indicador->numerador_description || $indicador->denominador_description)
                                    <div class="mt-0.5 text-xs text-[#64748B] line-clamp-1">
                                        N: {{ $indicador->numerador_description ?: '—' }} | D: {{ $indicador->denominador_description ?: '—' }}
                                    </div>
                                @endif
                            </td>

                            {{-- Tipo --}}
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                @if ($indicador->tipo === 'Meta Sanitaria')
                                    <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-800 border border-emerald-200">
                                        Meta Sanitaria
                                    </span>
                                @else
                                    <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-800 border border-amber-200">
                                        IAAPS
                                    </span>
                                @endif
                            </td>

                            {{-- Meta --}}
                            <td class="px-5 py-3.5 text-right font-mono font-bold text-brand-950 whitespace-nowrap">
                                {{ $indicador->meta_formateada }}%
                            </td>

                            {{-- Periodicidad / Fuente --}}
                            <td class="px-5 py-3.5 text-xs text-[#64748B] whitespace-nowrap">
                                <span class="font-medium text-brand-950">{{ $indicador->periodicidad }}</span>
                                <span class="text-[#94A3B8]">·</span>
                                <span>{{ $indicador->fu }}</span>
                            </td>

                            {{-- Estado --}}
                            <td class="px-5 py-3.5 text-center whitespace-nowrap">
                                @if ($indicador->is_active)
                                    <span class="inline-flex rounded-full bg-status-ok-bg px-2.5 py-0.5 text-xs font-semibold text-status-ok-fg">
                                        Activo
                                    </span>
                                @else
                                    <span class="inline-flex rounded-full bg-[#F1F5F9] px-2.5 py-0.5 text-xs font-semibold text-[#64748B]">
                                        Inactivo
                                    </span>
                                @endif
                            </td>

                            {{-- Acciones --}}
                            <td class="px-5 py-3.5 text-right whitespace-nowrap" onclick="event.stopPropagation()">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button
                                        type="button"
                                        data-accion="editar"
                                        class="rounded-lg p-1.5 text-[#64748B] hover:bg-[#F1F5F9] hover:text-brand-950 transition-colors"
                                        title="Editar indicador"
                                        aria-label="Editar indicador {{ $indicador->codigo }}"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>

                                    <form
                                        method="POST"
                                        action="{{ route('iaaps-metas.destroy', $indicador) }}"
                                        class="inline form-eliminar"
                                        data-swal-title="¿Eliminar indicador {{ $indicador->codigo }}?"
                                        data-swal-text="¿Confirmas que deseas eliminar permanentemente “{{ $indicador->nombre }}”? Esta acción eliminará también sus mediciones asociadas."
                                        data-swal-confirm="Sí, eliminar"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="rounded-lg p-1.5 text-[#94A3B8] hover:bg-rose-50 hover:text-rose-600 transition-colors"
                                            title="Eliminar indicador"
                                            aria-label="Eliminar indicador {{ $indicador->codigo }}"
                                        >
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        {{-- Fila Desplegable (Acordeón con Numeradores, Denominadores y Cumplimiento de cada establecimiento) --}}
                        <tr id="detalle-indicador-{{ $indicador->id }}" class="hidden bg-[#F8FAFC] border-b-2 border-brand-800/10">
                            <td colspan="8" class="p-4 sm:p-6">
                                <div class="rounded-xl border border-[#CBD5E1] bg-white p-4 sm:p-5 shadow-xs space-y-4">
                                    
                                    {{-- Encabezado del Acordeón con Definición de Fórmulas y Resumen Comunal --}}
                                    <div class="flex flex-wrap items-start justify-between gap-4 border-b border-[#E2E8F0] pb-4">
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="inline-flex rounded bg-brand-950 px-2 py-0.5 text-xs font-bold text-white font-mono">
                                                    {{ $indicador->codigo }}
                                                </span>
                                                <h3 class="text-sm font-bold text-brand-950">
                                                    Desglose de Mediciones por Establecimiento · Periodo: {{ $filtros['periodo'] }}
                                                </h3>
                                            </div>
                                            <p class="text-xs text-[#64748B] mt-1">
                                                Meta Comunal Establecida: <strong class="text-brand-950">{{ $indicador->meta_formateada }}%</strong>
                                            </p>
                                        </div>

                                        {{-- Métricas Comunales Consolidadas --}}
                                        <div class="flex flex-wrap items-center gap-3 text-xs">
                                            <div class="rounded-lg bg-[#F8FAFC] border border-[#CBD5E1] px-3 py-1.5 text-center">
                                                <span class="text-[#64748B] block text-[10px] uppercase font-bold">Total Numerador (N)</span>
                                                <span class="font-bold font-mono text-brand-950 text-sm">
                                                    {{ $medidasConValores->isNotEmpty() ? number_format($totalNum, 0, ',', '.') : '—' }}
                                                </span>
                                            </div>
                                            <div class="rounded-lg bg-[#F8FAFC] border border-[#CBD5E1] px-3 py-1.5 text-center">
                                                <span class="text-[#64748B] block text-[10px] uppercase font-bold">Total Denominador (D)</span>
                                                <span class="font-bold font-mono text-brand-950 text-sm">
                                                    {{ $medidasConValores->isNotEmpty() ? number_format($totalDen, 0, ',', '.') : '—' }}
                                                </span>
                                            </div>
                                            <div class="rounded-lg bg-[#F8FAFC] border border-[#CBD5E1] px-3 py-1.5 text-center">
                                                <span class="text-[#64748B] block text-[10px] uppercase font-bold">Cumplimiento Comunal</span>
                                                <span @class([
                                                    'font-bold font-mono text-sm',
                                                    'text-status-ok-fg' => $cumplimientoGlobal !== null && $cumplimientoGlobal >= (float) $indicador->target_value,
                                                    'text-status-bad-fg' => $cumplimientoGlobal !== null && $cumplimientoGlobal < (float) $indicador->target_value,
                                                    'text-[#64748B]' => $cumplimientoGlobal === null,
                                                ])>
                                                    {{ $cumplimientoGlobal !== null ? $cumplimientoGlobal . '%' : 'Sin datos' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Definiciones matemáticas del Numerador y Denominador --}}
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                                        <div class="rounded-lg bg-[#F1F5F9] border border-[#E2E8F0] p-3">
                                            <div class="flex items-center gap-1.5 font-bold uppercase text-brand-950 text-[11px]">
                                                <span class="inline-block h-2 w-2 rounded-full bg-brand-800"></span>
                                                Numerador (N):
                                            </div>
                                            <p class="mt-1 text-[#475569] leading-relaxed">
                                                {{ $indicador->numerador_description ?: 'No se especificó fórmula o descripción del numerador para este indicador.' }}
                                            </p>
                                        </div>
                                        <div class="rounded-lg bg-[#F1F5F9] border border-[#E2E8F0] p-3">
                                            <div class="flex items-center gap-1.5 font-bold uppercase text-brand-950 text-[11px]">
                                                <span class="inline-block h-2 w-2 rounded-full bg-accent-500"></span>
                                                Denominador (D):
                                            </div>
                                            <p class="mt-1 text-[#475569] leading-relaxed">
                                                {{ $indicador->denominador_description ?: 'No se especificó fórmula o descripción del denominador para este indicador.' }}
                                            </p>
                                        </div>
                                    </div>

                                    {{-- Tabla Detallada por Establecimiento --}}
                                    <div class="overflow-x-auto rounded-lg border border-[#E2E8F0]">
                                        <table class="w-full text-left text-xs">
                                            <thead class="bg-[#F8FAFC] text-[11px] font-semibold uppercase tracking-wider text-[#64748B] border-b border-[#E2E8F0]">
                                                <tr>
                                                    <th class="px-4 py-2.5">Establecimiento</th>
                                                    <th class="px-4 py-2.5">Tipo</th>
                                                    <th class="px-4 py-2.5 text-right">Numerador (N)</th>
                                                    <th class="px-4 py-2.5 text-right">Denominador (D)</th>
                                                    <th class="px-4 py-2.5 text-right">% Cumplimiento</th>
                                                    <th class="px-4 py-2.5 text-center">Estado</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-[#E2E8F0]">
                                                @forelse ($establecimientos as $est)
                                                    @php
                                                        $medida = $indicador->medidas->firstWhere('establecimientos_id', $est->id);
                                                        $numVal = $medida?->numerador_value !== null ? (float) $medida->numerador_value : null;
                                                        $denVal = $medida?->denominador_value !== null ? (float) $medida->denominador_value : null;
                                                        $cumplVal = $medida?->porcentaje_cumplimiento !== null ? (float) $medida->porcentaje_cumplimiento : null;
                                                        $estado = $medida?->status;

                                                        if (! $estado && ($numVal !== null || $denVal !== null)) {
                                                            if ($cumplVal !== null) {
                                                                $estado = $cumplVal >= (float) $indicador->target_value ? 'Cumpliendo' : ($cumplVal >= ((float) $indicador->target_value * 0.8) ? 'En Riesgo' : 'Crítico');
                                                            } else {
                                                                $estado = 'Registrado';
                                                            }
                                                        }
                                                    @endphp
                                                    <tr class="hover:bg-[#F8FAFC] transition-colors">
                                                        {{-- Nombre y Código DEIS --}}
                                                        <td class="px-4 py-2.5">
                                                            <div class="font-semibold text-brand-950">{{ $est->nombre }}</div>
                                                            <div class="text-[10px] text-[#94A3B8]">Código DEIS: {{ $est->codigo_deis ?: '—' }}</div>
                                                        </td>

                                                        {{-- Tipo de Centro --}}
                                                        <td class="px-4 py-2.5">
                                                            <span class="inline-flex rounded px-1.5 py-0.5 text-[10px] font-semibold bg-[#F1F5F9] text-brand-950 border border-[#CBD5E1]">
                                                                {{ $est->tipo }}
                                                            </span>
                                                        </td>

                                                        {{-- Numerador --}}
                                                        <td class="px-4 py-2.5 text-right font-mono font-medium text-brand-950">
                                                            {{ $numVal !== null ? number_format($numVal, 0, ',', '.') : '—' }}
                                                        </td>

                                                        {{-- Denominador --}}
                                                        <td class="px-4 py-2.5 text-right font-mono font-medium text-brand-950">
                                                            {{ $denVal !== null ? number_format($denVal, 0, ',', '.') : '—' }}
                                                        </td>

                                                        {{-- Cumplimiento --}}
                                                        <td class="px-4 py-2.5 text-right font-mono font-bold whitespace-nowrap">
                                                            @if ($cumplVal !== null)
                                                                <div class="flex items-center justify-end gap-2">
                                                                    <div class="h-1.5 w-16 overflow-hidden rounded-full bg-[#E2E8F0] hidden sm:block">
                                                                        <div
                                                                            @class([
                                                                                'h-full rounded-full',
                                                                                'bg-emerald-600' => $cumplVal >= (float) $indicador->target_value,
                                                                                'bg-amber-500' => $cumplVal < (float) $indicador->target_value && $cumplVal >= ((float) $indicador->target_value * 0.8),
                                                                                'bg-rose-600' => $cumplVal < ((float) $indicador->target_value * 0.8),
                                                                            ])
                                                                            style="width: {{ min(100, max(0, $cumplVal)) }}%"
                                                                        ></div>
                                                                    </div>
                                                                    <span @class([
                                                                        'text-xs',
                                                                        'text-emerald-700' => $cumplVal >= (float) $indicador->target_value,
                                                                        'text-amber-700' => $cumplVal < (float) $indicador->target_value && $cumplVal >= ((float) $indicador->target_value * 0.8),
                                                                        'text-rose-700' => $cumplVal < ((float) $indicador->target_value * 0.8),
                                                                    ])>
                                                                        {{ number_format($cumplVal, 1, ',', '.') }}%
                                                                    </span>
                                                                </div>
                                                            @else
                                                                <span class="text-[#94A3B8] font-normal text-xs">Sin registro</span>
                                                            @endif
                                                        </td>

                                                        {{-- Estado --}}
                                                        <td class="px-4 py-2.5 text-center whitespace-nowrap">
                                                            @if ($estado === 'Cumpliendo')
                                                                <span class="inline-flex rounded-full bg-status-ok-bg px-2 py-0.5 text-[11px] font-semibold text-status-ok-fg">
                                                                    Cumpliendo
                                                                </span>
                                                            @elseif ($estado === 'En Riesgo')
                                                                <span class="inline-flex rounded-full bg-status-warn-bg px-2 py-0.5 text-[11px] font-semibold text-status-warn-fg">
                                                                    En Riesgo
                                                                </span>
                                                            @elseif ($estado === 'Crítico')
                                                                <span class="inline-flex rounded-full bg-status-bad-bg px-2 py-0.5 text-[11px] font-semibold text-status-bad-fg">
                                                                    Crítico
                                                                </span>
                                                            @elseif ($estado)
                                                                <span class="inline-flex rounded-full bg-blue-50 px-2 py-0.5 text-[11px] font-semibold text-blue-700 border border-blue-200">
                                                                    {{ $estado }}
                                                                </span>
                                                            @else
                                                                <span class="inline-flex rounded-full bg-[#F1F5F9] px-2 py-0.5 text-[11px] font-semibold text-[#64748B]">
                                                                    Sin Medición
                                                                </span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="6" class="px-4 py-6 text-center text-xs text-[#64748B]">
                                                            No hay establecimientos activos registrados en la red.
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>

                                </div>
                            </td>
                        </tr>

                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-sm text-[#64748B]">
                                <svg class="mx-auto h-10 w-10 text-[#CBD5E1]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                <p class="mt-3 font-semibold text-brand-950">No se encontraron metas sanitarias ni indicadores IAAPS</p>
                                <p class="mt-1 text-xs text-[#94A3B8]">Intenta ajustar o limpiar los filtros seleccionados.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginación (de 10 en 10) --}}
        @if ($indicadores->hasPages() || $indicadores->total() > 0)
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-[#E2E8F0] px-6 py-3.5 text-sm text-[#64748B]">
                <span>
                    Mostrando <strong class="text-brand-950">{{ $indicadores->firstItem() ?? 0 }}–{{ $indicadores->lastItem() ?? 0 }}</strong> de {{ $indicadores->total() }} indicadores (10 por página)
                </span>
                {{ $indicadores->links() }}
            </div>
        @endif
    </div>

</div>

{{-- Modal Crear / Editar Indicador --}}
@include('iaaps-metas.partials.form-modal')
@endsection

@push('scripts')
<script>
    (() => {
        // Toggle desplegable de acordeón para cada indicador
        window.toggleDetalle = (id, event) => {
            if (event && event.target.closest('button, form, a, input')) {
                return;
            }

            const detalleRow = document.getElementById(`detalle-indicador-${id}`);
            const chevronIcon = document.getElementById(`chevron-icon-${id}`);
            const btnTexto = document.getElementById(`btn-texto-${id}`);

            if (!detalleRow) return;

            const isHidden = detalleRow.classList.contains('hidden');
            if (isHidden) {
                detalleRow.classList.remove('hidden');
                if (chevronIcon) chevronIcon.classList.add('rotate-180');
                if (btnTexto) btnTexto.textContent = 'Ocultar';
            } else {
                detalleRow.classList.add('hidden');
                if (chevronIcon) chevronIcon.classList.remove('rotate-180');
                if (btnTexto) btnTexto.textContent = 'Desplegar';
            }
        };

        const modalIndicador = document.getElementById('modal-indicador-form');
        const form = document.getElementById('form-indicador');
        let elementoDisparador = null;

        const abrirModal = (modal, origen) => {
            elementoDisparador = origen;
            modal.classList.replace('hidden', 'flex');
            (modal.querySelector('input:not([type="hidden"]), select') ?? modal.querySelector('button'))?.focus();
        };

        const cerrarModal = (modal) => {
            modal.classList.replace('flex', 'hidden');
            elementoDisparador?.focus();
        };

        // Cierre al pulsar afuera o en [data-cerrar-modal]
        modalIndicador.addEventListener('click', (evento) => {
            if (evento.target === modalIndicador || evento.target.closest('[data-cerrar-modal]')) {
                cerrarModal(modalIndicador);
            }
        });

        // Cierre con tecla Escape
        document.addEventListener('keydown', (evento) => {
            if (!modalIndicador.classList.contains('flex')) return;

            if (evento.key === 'Escape') {
                cerrarModal(modalIndicador);
            }
        });

        // Preparar modal para crear o editar
        const prepararFormulario = (indicador) => {
            form.querySelectorAll('[data-error]').forEach((err) => err.remove());

            document.getElementById('form-indicador-titulo').textContent = indicador
                ? `Editar indicador ${indicador.codigo}`
                : 'Nuevo indicador';

            form.action = indicador ? indicador.url_actualizar : form.dataset.urlCrear;
            form.elements._method.disabled = !indicador;
            form.elements.indicador_id.value = indicador?.id ?? '';

            if (indicador) {
                form.elements.codigo.value = indicador.codigo ?? '';
                form.elements.nombre.value = indicador.nombre ?? '';
                form.elements.tipo.value = indicador.tipo ?? 'Meta Sanitaria';
                form.elements.target_value.value = indicador.target_value ?? '85';
                form.elements.periodicidad.value = indicador.periodicidad ?? 'Mensual';
                form.elements.fu.value = indicador.fu ?? 'REM-A';
                form.elements.numerador_description.value = indicador.numerador_description ?? '';
                form.elements.denominador_description.value = indicador.denominador_description ?? '';
                form.elements.is_active.value = indicador.is_active ? '1' : '0';
            } else {
                form.elements.codigo.value = '';
                form.elements.nombre.value = '';
                form.elements.tipo.value = 'Meta Sanitaria';
                form.elements.target_value.value = '85';
                form.elements.periodicidad.value = 'Mensual';
                form.elements.fu.value = 'REM-A';
                form.elements.numerador_description.value = '';
                form.elements.denominador_description.value = '';
                form.elements.is_active.value = '1';
            }
        };

        // Botón "+ Nuevo indicador"
        document.querySelector('[data-nuevo-indicador]')?.addEventListener('click', (evento) => {
            prepararFormulario(null);
            abrirModal(modalIndicador, evento.currentTarget);
        });

        // Botón "Editar" en las filas de la tabla
        document.getElementById('iaaps-metas-filas')?.addEventListener('click', (evento) => {
            const boton = evento.target.closest('[data-accion="editar"]');
            if (!boton) return;

            const tr = boton.closest('tr');
            if (!tr || !tr.dataset.indicador) return;

            const datos = JSON.parse(tr.dataset.indicador);
            prepararFormulario(datos);
            abrirModal(modalIndicador, boton);
        });

        // Reabrir modal en caso de error de validación
        if (modalIndicador.dataset.reabrir === 'true') {
            abrirModal(modalIndicador, document.querySelector('[data-nuevo-indicador]'));
        }
    })();
</script>
@endpush
