@extends('layouts.app')

@section('title', 'Importación Masiva REM')
@section('page-title', 'Módulo de Importación REM')
@section('page-subtitle', 'Procesamiento de planillas oficiales MINSAL (Serie A / Serie P) y cálculo de Metas Sanitarias Ley 19.813 e IAAPS 2025')

@section('content')
<div class="space-y-8">

    {{-- Panel de Métricas Rápidas --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-[#E2E8F0] bg-white p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-[#64748B]">Total Registros</span>
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-100 text-accent-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </span>
            </div>
            <p class="mt-2 text-2xl font-bold text-brand-950" id="stat-total">{{ $stats['total_mediciones'] }}</p>
            <div class="mt-1 flex items-center gap-2 text-xs text-[#64748B]">
                <span>Metas: <strong class="text-brand-950" id="stat-metas">{{ $stats['total_metas'] }}</strong></span>
                <span>&bull;</span>
                <span>IAAPS: <strong class="text-brand-950" id="stat-iaaps">{{ $stats['total_iaaps'] }}</strong></span>
            </div>
        </div>

        <div class="rounded-xl border border-[#E2E8F0] bg-white p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-[#16A34A]">Cumpliendo</span>
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#E7F9EF] text-[#16A34A]">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </span>
            </div>
            <p class="mt-2 text-2xl font-bold text-[#16A34A]" id="stat-cumpliendo">{{ $stats['cumpliendo'] }}</p>
            <p class="mt-1 text-xs text-[#94A3B8]">&ge; 100% de la meta fijada</p>
        </div>

        <div class="rounded-xl border border-[#E2E8F0] bg-white p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-[#F97316]">En Riesgo</span>
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#FFF4E5] text-[#F97316]">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </span>
            </div>
            <p class="mt-2 text-2xl font-bold text-[#F97316]" id="stat-riesgo">{{ $stats['en_riesgo'] }}</p>
            <p class="mt-1 text-xs text-[#94A3B8]">Entre 85% y 99% de la meta</p>
        </div>

        <div class="rounded-xl border border-[#E2E8F0] bg-white p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-[#E11D2E]">Crítico</span>
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#FDEDEC] text-[#E11D2E]">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </span>
            </div>
            <p class="mt-2 text-2xl font-bold text-[#E11D2E]" id="stat-critico">{{ $stats['critico'] }}</p>
            <p class="mt-1 text-xs text-[#94A3B8]">&lt; 85% de la meta fijada</p>
        </div>
    </div>

    {{-- Zona de Carga Drag & Drop --}}
    <div class="rounded-xl border border-[#E2E8F0] bg-white p-6 shadow-xs">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2 border-b border-[#F1F5F9] pb-4">
            <div>
                <h2 class="text-lg font-bold text-brand-950">Subir Planilla Oficial REM</h2>
                <p class="text-sm text-[#64748B]">
                    El sistema detectará automáticamente el Establecimiento (código DEIS), Año y Mes de corte sin intervención manual.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-100 px-3 py-1 text-xs font-medium text-brand-800">
                    <span class="h-2 w-2 rounded-full bg-accent-500"></span>
                    Formatos aceptados: .xlsm, .xlsx
                </span>
            </div>
        </div>

        <form id="form-importar" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <input type="hidden" name="establecimiento_id" id="override-establecimiento-id" value="">

            {{-- Dropzone visual --}}
            <div
                id="dropzone"
                class="group relative flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-[#CBD5E1] bg-[#F8FAFC] px-6 py-10 text-center transition-all hover:border-accent-500 hover:bg-[#F1F5FD]"
            >
                <input
                    type="file"
                    id="archivo"
                    name="archivo"
                    accept=".xlsm,.xlsx,.xls"
                    class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                >

                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-white shadow-xs transition-transform group-hover:scale-105">
                    <svg class="h-7 w-7 text-accent-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                </div>

                <div class="mt-4 space-y-1">
                    <p class="text-sm font-semibold text-brand-950">
                        Arrastra y suelta tu archivo REM aquí o <span class="text-accent-600 underline">haz clic para examinar</span>
                    </p>
                    <p class="text-xs text-[#64748B]">
                        Archivos oficiales MINSAL (ejemplo: <code class="rounded bg-[#E2E8F0] px-1 py-0.5 text-brand-950 font-mono">119308A06.xlsm</code> o <code class="rounded bg-[#E2E8F0] px-1 py-0.5 text-brand-950 font-mono">119308P06.xlsm</code>)
                    </p>
                </div>

                {{-- Archivo seleccionado detalle --}}
                <div id="file-info" class="mt-4 hidden items-center gap-3 rounded-lg border border-[#CBD5E1] bg-white px-4 py-2.5 shadow-xs">
                    <svg class="h-5 w-5 text-[#16A34A]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <div class="text-left leading-tight">
                        <p id="file-name" class="max-w-xs truncate text-sm font-medium text-brand-950">archivo.xlsm</p>
                        <p id="file-size" class="text-xs text-[#64748B]">0 KB</p>
                    </div>
                    <button type="button" id="btn-remove-file" class="ml-2 text-xs text-[#E11D2E] hover:underline">Quitar</button>
                </div>
            </div>

            {{-- Opciones de Alcance de Evaluación --}}
            <div class="flex flex-wrap items-center justify-between gap-4 rounded-lg bg-[#F8FAFC] border border-[#E2E8F0] p-3 text-sm">
                <div class="flex items-center gap-2">
                    <svg class="h-4 w-4 text-[#64748B]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                    </svg>
                    <span class="text-xs font-bold uppercase tracking-wider text-[#475569]">Alcance de Evaluación:</span>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-[#CBD5E1] bg-white px-3 py-1.5 text-xs font-semibold text-brand-950 shadow-xs has-[:checked]:border-accent-600 has-[:checked]:bg-[#EEF5FF] has-[:checked]:text-accent-700 transition-all">
                        <input type="radio" name="tipo_indicadores" value="todas" checked class="text-accent-600 focus:ring-accent-500">
                        <span>Todas (Metas + IAAPS 2025)</span>
                    </label>
                    <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-[#CBD5E1] bg-white px-3 py-1.5 text-xs font-semibold text-brand-950 shadow-xs has-[:checked]:border-accent-600 has-[:checked]:bg-[#EEF5FF] has-[:checked]:text-accent-700 transition-all">
                        <input type="radio" name="tipo_indicadores" value="metas" class="text-accent-600 focus:ring-accent-500">
                        <span>Solo Metas Sanitarias (Ley 19.813)</span>
                    </label>
                    <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-[#CBD5E1] bg-white px-3 py-1.5 text-xs font-semibold text-brand-950 shadow-xs has-[:checked]:border-accent-600 has-[:checked]:bg-[#EEF5FF] has-[:checked]:text-accent-700 transition-all">
                        <input type="radio" name="tipo_indicadores" value="iaaps" class="text-accent-600 focus:ring-accent-500">
                        <span>Solo IAAPS 2025</span>
                    </label>
                </div>
            </div>

            {{-- Barra de acción --}}
            <div class="flex items-center justify-end gap-3 pt-2">
                <button
                    type="submit"
                    id="btn-procesar"
                    disabled
                    class="flex items-center gap-2 rounded-lg bg-brand-950 px-6 py-2.5 text-sm font-semibold text-white shadow-xs transition-colors hover:bg-brand-800 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <svg id="spinner" class="hidden h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span id="btn-text">Procesar y Proyectar Indicadores</span>
                </button>
            </div>
        </form>
    </div>

    {{-- Banner de Metadatos Detectados (Oculto inicialmente) --}}
    <div id="metadata-banner" class="hidden rounded-xl border border-[#B8D5FA] bg-[#EEF5FF] p-5">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-accent-500 text-white shadow-xs">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </span>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 id="meta-establecimiento" class="text-base font-bold text-brand-950">CESFAM Los Cerros</h3>
                        <span id="meta-deis-badge" class="rounded bg-accent-500/10 px-2 py-0.5 text-xs font-semibold text-accent-600">DEIS: 119308</span>
                    </div>
                    <p class="text-xs text-[#64748B]">
                        Población validada inscrita: <strong id="meta-poblacion" class="text-brand-950">35.000</strong> beneficiarios
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2 sm:gap-3 w-full sm:w-auto">
                <div class="flex-1 sm:flex-initial min-w-[100px] rounded-lg border border-[#CBD5E1] bg-white px-3.5 py-2 text-center shadow-xs">
                    <span class="block text-[11px] font-medium uppercase text-[#64748B]">Mes de Corte</span>
                    <span id="meta-mes" class="text-sm font-bold text-brand-950">Junio (06)</span>
                </div>
                <div class="flex-1 sm:flex-initial min-w-[100px] rounded-lg border border-[#CBD5E1] bg-white px-3.5 py-2 text-center shadow-xs">
                    <span class="block text-[11px] font-medium uppercase text-[#64748B]">Año de Evaluación</span>
                    <span id="meta-año" class="text-sm font-bold text-brand-950">2026</span>
                </div>
                <div class="flex-1 sm:flex-initial min-w-[100px] rounded-lg border border-[#CBD5E1] bg-white px-3.5 py-2 text-center shadow-xs">
                    <span class="block text-[11px] font-medium uppercase text-[#64748B]">Serie DEIS</span>
                    <span id="meta-serie" class="text-sm font-bold text-accent-600">Serie A</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Sección de Resultados y Gráficos ApexCharts (Oculta hasta procesar) --}}
    <div id="section-resultados" class="hidden space-y-6">

        {{-- Encabezado de Resultados --}}
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold uppercase tracking-tight text-brand-950">
                    Resultados de Cumplimiento · Metas Sanitarias e IAAPS 2025
                </h2>
                <p class="text-sm text-[#64748B]">
                    Mediciones calculadas según Ley 19.813 e Indicadores IAAPS 2025 a partir de la planilla oficial y datos demográficos FONASA.
                </p>
            </div>
            <button
                type="button"
                onclick="window.print()"
                class="inline-flex w-full sm:w-auto justify-center items-center gap-2 rounded-lg border border-[#D8DEE9] bg-white px-4 py-2 text-sm font-medium text-brand-950 shadow-xs hover:bg-[#F8FAFC]"
            >
                <svg class="h-4 w-4 text-[#64748B]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Imprimir Informe
            </button>
        </div>

        {{-- Pestañas / Filtro de Resultados --}}
        <div class="flex border-b border-[#E2E8F0] gap-2 overflow-x-auto whitespace-nowrap pb-px">
            <button type="button" class="tab-filter-btn shrink-0 flex items-center gap-2 border-b-2 border-brand-950 px-4 py-2.5 text-sm font-bold text-brand-950 transition-colors" data-filter="todas">
                <span>Todos los Indicadores</span>
                <span id="badge-count-todas" class="rounded-full bg-brand-100 px-2 py-0.5 text-xs font-semibold text-brand-800">0</span>
            </button>
            <button type="button" class="tab-filter-btn shrink-0 flex items-center gap-2 border-b-2 border-transparent px-4 py-2.5 text-sm font-semibold text-[#64748B] hover:text-brand-950 transition-colors" data-filter="metas">
                <span>Metas Sanitarias (Ley 19.813)</span>
                <span id="badge-count-metas" class="rounded-full bg-[#E2E8F0] px-2 py-0.5 text-xs font-semibold text-[#475569]">0</span>
            </button>
            <button type="button" class="tab-filter-btn shrink-0 flex items-center gap-2 border-b-2 border-transparent px-4 py-2.5 text-sm font-semibold text-[#64748B] hover:text-brand-950 transition-colors" data-filter="iaaps">
                <span>IAAPS 2025</span>
                <span id="badge-count-iaaps" class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">0</span>
            </button>
        </div>

        {{-- Gráficos ApexCharts --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            {{-- Gráfico 1: Cumplimiento vs Meta (Barras) --}}
            <div class="rounded-xl border border-[#E2E8F0] bg-white p-5 shadow-xs lg:col-span-2">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-brand-950">Avance vs Objetivo Nacional / Comunal</h3>
                        <p class="text-xs text-[#64748B]">Comparativa porcentual o tasa por indicador (Ley 19.813 / IAAPS 2025)</p>
                    </div>
                    <span class="rounded bg-brand-100 px-2 py-1 text-xs font-semibold text-brand-800">ApexCharts</span>
                </div>
                <div id="chart-barras" class="min-h-[320px]"></div>
            </div>

            {{-- Gráfico 2: Distribución de Estados (Donut) --}}
            <div class="rounded-xl border border-[#E2E8F0] bg-white p-5 shadow-xs">
                <div class="mb-4">
                    <h3 class="text-base font-bold text-brand-950">Distribución de Cumplimiento</h3>
                    <p class="text-xs text-[#64748B]">Proporción de indicadores según regla de estado</p>
                </div>
                <div id="chart-donut" class="flex min-h-[320px] items-center justify-center"></div>
            </div>
        </div>

        {{-- Tabla de Detalle de Mediciones --}}
        <div class="overflow-hidden rounded-xl border border-[#E2E8F0] bg-white shadow-xs">
            <div class="border-b border-[#F1F5F9] px-6 py-4">
                <h3 class="text-base font-bold text-brand-950">Detalle de Mediciones Calculadas</h3>
                <p class="text-xs text-[#64748B]">Desglose de numeradores, denominadores y porcentaje de avance físico</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-[#F8FAFC] text-xs font-semibold uppercase tracking-wider text-[#64748B]">
                        <tr>
                            <th class="px-6 py-3.5">Tipo</th>
                            <th class="px-6 py-3.5">Código</th>
                            <th class="px-6 py-3.5">Indicador / Meta Sanitaria</th>
                            <th class="px-6 py-3.5 text-right">Numerador</th>
                            <th class="px-6 py-3.5 text-right">Denominador</th>
                            <th class="px-6 py-3.5 text-center">Cumplimiento</th>
                            <th class="px-6 py-3.5 text-center">Meta Anual</th>
                            <th class="px-6 py-3.5 text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-resultados-body" class="divide-y divide-[#F1F5F9]">
                        {{-- Se puebla dinámicamente vía JavaScript --}}
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    {{-- Historial de Mediciones Recientes (Auditoría) --}}
    <div class="rounded-xl border border-[#E2E8F0] bg-white shadow-xs">
        <div class="flex items-center justify-between border-b border-[#F1F5F9] px-6 py-4">
            <div>
                <h3 class="text-base font-bold text-brand-950">Mediciones Registradas Recientemente</h3>
                <p class="text-xs text-[#64748B]">Últimas importaciones persistidas en base de datos</p>
            </div>
            <a href="{{ route('iaaps-metas.index') }}" class="text-xs font-semibold text-accent-600 hover:underline">
                Ver Módulo IAAPS y Metas &rarr;
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-[#F8FAFC] text-xs font-semibold uppercase tracking-wider text-[#64748B]">
                    <tr>
                        <th class="px-6 py-3">Tipo</th>
                        <th class="px-6 py-3">Establecimiento</th>
                        <th class="px-6 py-3">Indicador</th>
                        <th class="px-6 py-3 text-center">Periodo</th>
                        <th class="px-6 py-3 text-right">Numerador</th>
                        <th class="px-6 py-3 text-right">Denominador</th>
                        <th class="px-6 py-3 text-center">Cumplimiento</th>
                        <th class="px-6 py-3 text-center">Estado</th>
                        <th class="px-6 py-3 text-right">Fecha Registro</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#F1F5F9]">
                    @forelse ($medidasRecientes as $medida)
                        <tr class="hover:bg-[#F8FAFC]">
                            <td class="px-6 py-3">
                                @if (($medida->indicador->tipo ?? '') === 'IAAPS')
                                    <span class="inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700 border border-amber-200">IAAPS</span>
                                @else
                                    <span class="inline-flex rounded-full bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700 border border-blue-200">Meta</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 font-medium text-brand-950">
                                {{ $medida->establecimiento->nombre ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-3 text-[#334155]">
                                <span class="font-bold text-brand-950">{{ $medida->indicador->codigo ?? 'N/A' }}</span>
                                <span class="block text-xs text-[#64748B]">{{ Str::limit($medida->indicador->nombre ?? '', 45) }}</span>
                            </td>
                            <td class="px-6 py-3 text-center font-mono text-xs text-[#475569]">
                                {{ str_pad($medida->mes, 2, '0', STR_PAD_LEFT) }}/{{ $medida->ano }}
                            </td>
                            <td class="px-6 py-3 text-right font-mono text-xs text-[#334155]">
                                {{ number_format((float) $medida->numerador_value, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-3 text-right font-mono text-xs text-[#334155]">
                                {{ number_format((float) $medida->denominador_value, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-3 text-center font-bold text-brand-950">
                                {{ number_format((float) $medida->porcentaje_cumplimiento, 2, ',', '.') }}%
                            </td>
                            <td class="px-6 py-3 text-center">
                                @if ($medida->status === 'Cumpliendo')
                                    <span class="inline-flex rounded-full bg-[#E7F9EF] px-2.5 py-0.5 text-xs font-semibold text-[#16A34A]">Cumpliendo</span>
                                @elseif ($medida->status === 'En Riesgo')
                                    <span class="inline-flex rounded-full bg-[#FFF4E5] px-2.5 py-0.5 text-xs font-semibold text-[#F97316]">En Riesgo</span>
                                @else
                                    <span class="inline-flex rounded-full bg-[#FDEDEC] px-2.5 py-0.5 text-xs font-semibold text-[#E11D2E]">Crítico</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-right text-xs text-[#94A3B8]">
                                {{ $medida->created_at?->format('d/m/Y H:i') ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-8 text-center text-sm text-[#94A3B8]">
                                Aún no se han registrado mediciones. Sube un archivo REM arriba para comenzar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@push('scripts')
{{-- Librerías externas oficiales: SweetAlert2 y ApexCharts --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const fileInput = document.getElementById('archivo');
    const dropzone = document.getElementById('dropzone');
    const fileInfo = document.getElementById('file-info');
    const fileName = document.getElementById('file-name');
    const fileSize = document.getElementById('file-size');
    const btnRemoveFile = document.getElementById('btn-remove-file');
    const btnProcesar = document.getElementById('btn-procesar');
    const btnText = document.getElementById('btn-text');
    const spinner = document.getElementById('spinner');
    const formImportar = document.getElementById('form-importar');

    let currentFile = null;
    let chartBarras = null;
    let chartDonut = null;

    // Manejo de drag and drop
    ['dragenter', 'dragover'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.add('border-accent-500', 'bg-[#F1F5FD]');
        });
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.remove('border-accent-500', 'bg-[#F1F5FD]');
        });
    });

    dropzone.addEventListener('drop', (e) => {
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            handleSelectedFile(files[0]);
        }
    });

    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            handleSelectedFile(e.target.files[0]);
        }
    });

    btnRemoveFile.addEventListener('click', (e) => {
        e.stopPropagation();
        resetFileInput();
    });

    function handleSelectedFile(file) {
        currentFile = file;
        fileName.textContent = file.name;
        fileSize.textContent = (file.size / 1024).toFixed(1) + ' KB';
        fileInfo.classList.remove('hidden');
        fileInfo.classList.add('flex');
        btnProcesar.disabled = false;
    }

    function resetFileInput() {
        currentFile = null;
        fileInput.value = '';
        fileInfo.classList.add('hidden');
        fileInfo.classList.remove('flex');
        btnProcesar.disabled = true;
    }

    // Envío AJAX
    formImportar.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!currentFile) return;

        await ejecutarProcesamiento();
    });

    async function ejecutarProcesamiento(overrideEstablecimientoId = null) {
        setLoading(true);

        const formData = new FormData();
        formData.append('archivo', currentFile);
        const tipoIndicadores = document.querySelector('input[name="tipo_indicadores"]:checked')?.value || 'todas';
        formData.append('tipo_indicadores', tipoIndicadores);
        if (overrideEstablecimientoId) {
            formData.append('establecimiento_id', overrideEstablecimientoId);
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        try {
            const response = await fetch("{{ route('importacion.previsualizar') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: formData
            });

            const data = await response.json();
            setLoading(false);

            if (!response.ok || data.status === 'error') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Lectura',
                    text: data.message || 'No fue posible procesar la planilla seleccionada.'
                });
                return;
            }

            // Excepción SweetAlert: Establecimiento no encontrado
            if (data.status === 'establishment_not_found') {
                await manejarEstablecimientoNoEncontrado(data);
                return;
            }

            // Actualizar banner visual de metadatos detectados
            mostrarBannerMetadatos(data.establecimiento, data.periodo);

            // Denominadores pendientes: Iterar preguntas interactivas con SweetAlert2
            if (data.status === 'requires_input' && data.pending_inputs && data.pending_inputs.length > 0) {
                await iterarPreguntasSweetAlert(data);
                return;
            }

            // Todo listo sin preguntas pendientes
            if (data.status === 'ready') {
                await guardarResultados(data.temp_token, data.establecimiento.id, data.periodo.año, data.periodo.mes, {});
            }

        } catch (err) {
            setLoading(false);
            console.error(err);
            Swal.fire({
                icon: 'error',
                title: 'Error de Red',
                text: 'Ocurrió una falla de comunicación con el servidor.'
            });
        }
    }

    // Modal de selección cuando el establecimiento no coincide en la BD
    async function manejarEstablecimientoNoEncontrado(data) {
        const optionsHtml = data.establecimientos_disponibles.map(e => {
            const deis = e.codigo_deis ? ` (DEIS: ${e.codigo_deis})` : '';
            return `<option value="${e.id}">${e.nombre}${deis}</option>`;
        }).join('');

        const { value: selectedId, isConfirmed } = await Swal.fire({
            icon: 'warning',
            title: 'Establecimiento No Registrado',
            html: `
                <div class="text-left text-sm text-[#475569] space-y-2">
                    <p>La planilla indica el recinto:</p>
                    <div class="rounded-lg bg-[#F8FAFC] border border-[#CBD5E1] p-3">
                        <p class="font-bold text-brand-950">${data.detected.nombre}</p>
                        <p class="text-xs text-[#64748B]">Código DEIS: <strong>${data.detected.codigo_deis}</strong> · Periodo: ${data.detected.mes_nombre} ${data.detected.año}</p>
                    </div>
                    <p class="pt-2">Seleccione el establecimiento de la red correspondiente para vincular la importación:</p>
                    <select id="swal-est-select" class="w-full rounded-md border border-[#CBD5E1] p-2 text-sm text-brand-950 focus:outline-none focus:ring-2 focus:ring-accent-500">
                        ${optionsHtml}
                    </select>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Vincular y Continuar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#0f1424',
            preConfirm: () => {
                return document.getElementById('swal-est-select').value;
            }
        });

        if (isConfirmed && selectedId) {
            await ejecutarProcesamiento(selectedId);
        }
    }

    // Iteración secuencial de preguntas usando SweetAlert2
    async function iterarPreguntasSweetAlert(data) {
        const pending = data.pending_inputs;
        const total = pending.length;
        const userInputs = {};

        // Alerta introductoria explicativa
        const intro = await Swal.fire({
            icon: 'info',
            title: 'Datos Complementarios Requeridos',
            html: `
                <p class="text-sm text-[#475569]">
                    Se detectaron exitosamente los numeradores del <strong>REM Serie ${data.periodo.serie}</strong> para <strong>${data.establecimiento.nombre}</strong>.
                </p>
                <p class="mt-2 text-xs text-[#64748B]">
                    A continuación se solicitarán los denominadores demográficos (población FONASA y estimaciones de prevalencia ENS) para proyectar el cumplimiento definitivo.
                </p>
            `,
            confirmButtonText: 'Comenzar (' + total + ' preguntas)',
            confirmButtonColor: '#2f6fed',
            showCancelButton: true,
            cancelButtonText: 'Cancelar'
        });

        if (!intro.isConfirmed) return;

        // Bucle secuencial de SweetAlert
        for (let i = 0; i < total; i++) {
            const item = pending[i];

            const { value: respuesta, isDismissed } = await Swal.fire({
                title: item.title,
                text: item.text,
                input: 'number',
                inputValue: item.default_value !== null ? item.default_value : '',
                inputAttributes: {
                    min: '0',
                    step: '1'
                },
                footer: `<span class="text-xs font-semibold text-[#64748B]">Paso ${i + 1} de ${total}</span>`,
                showCancelButton: true,
                confirmButtonText: (i === total - 1) ? 'Calcular y Guardar' : 'Siguiente &rarr;',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#0f1424',
                allowOutsideClick: false,
                inputValidator: (val) => {
                    if (val === '' || val === null) {
                        return 'Debe ingresar un valor numérico (0 si no aplica).';
                    }
                    if (parseFloat(val) < 0) {
                        return 'El valor no puede ser negativo.';
                    }
                }
            });

            if (isDismissed) {
                Swal.fire({
                    icon: 'info',
                    title: 'Operación Cancelada',
                    text: 'La importación no se guardó. Puedes reanudarla cuando lo desees.'
                });
                return;
            }

            userInputs[item.input_key] = parseFloat(respuesta);
        }

        // Enviar al endpoint de confirmación y guardado
        await guardarResultados(data.temp_token, data.establecimiento.id, data.periodo.año, data.periodo.mes, userInputs);
    }

    // Envía la confirmación definitiva a /importacion/guardar
    async function guardarResultados(tempToken, establecimientoId, año, mes, inputs) {
        setLoading(true);
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        try {
            const response = await fetch("{{ route('importacion.guardar') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    temp_token: tempToken,
                    establecimiento_id: establecimientoId,
                    año: año,
                    mes: mes,
                    inputs: inputs
                })
            });

            const data = await response.json();
            setLoading(false);

            if (!response.ok || data.status !== 'success') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error al Guardar',
                    text: data.message || 'No fue posible guardar las mediciones calculadas.'
                });
                return;
            }

            // Actualizar tarjetas de estadísticas
            if (data.totales) {
                const elMetas = document.getElementById('stat-metas');
                if (elMetas && data.totales.metas_count !== undefined) {
                    elMetas.textContent = (parseInt(elMetas.textContent || '0') + data.totales.metas_count).toString();
                }
                const elIaaps = document.getElementById('stat-iaaps');
                if (elIaaps && data.totales.iaaps_count !== undefined) {
                    elIaaps.textContent = (parseInt(elIaaps.textContent || '0') + data.totales.iaaps_count).toString();
                }

                document.getElementById('stat-total').textContent = (parseInt(document.getElementById('stat-total').textContent) + data.totales.total).toString();
                document.getElementById('stat-cumpliendo').textContent = (parseInt(document.getElementById('stat-cumpliendo').textContent) + data.totales.cumpliendo).toString();
                document.getElementById('stat-riesgo').textContent = (parseInt(document.getElementById('stat-riesgo').textContent) + data.totales.en_riesgo).toString();
                document.getElementById('stat-critico').textContent = (parseInt(document.getElementById('stat-critico').textContent) + data.totales.critico).toString();
            }

            // Notificación de éxito
            Swal.fire({
                icon: 'success',
                title: '¡Importación Exitosa!',
                text: `${data.medidas.length} Indicadores (Metas Sanitarias e IAAPS 2025) calculados y almacenados para ${data.establecimiento.nombre}.`,
                timer: 3000,
                showConfirmButton: true,
                confirmButtonColor: '#2f6fed'
            });

            // Renderizar gráficos ApexCharts y tabla
            renderizarResultados(data);

        } catch (err) {
            setLoading(false);
            console.error(err);
            Swal.fire({
                icon: 'error',
                title: 'Error al Guardar',
                text: 'Falla al persistir los cálculos.'
            });
        }
    }

    function mostrarBannerMetadatos(est, per) {
        document.getElementById('meta-establecimiento').textContent = est.nombre;
        document.getElementById('meta-deis-badge').textContent = est.codigo_deis ? `DEIS: ${est.codigo_deis}` : 'Sin código';
        document.getElementById('meta-poblacion').textContent = Number(est.poblacion_inscrita || 0).toLocaleString('es-CL');
        document.getElementById('meta-mes').textContent = `${per.mes_nombre} (${String(per.mes).padStart(2, '0')})`;
        document.getElementById('meta-año').textContent = per.año;
        document.getElementById('meta-serie').textContent = `Serie ${per.serie || 'A'}`;

        document.getElementById('metadata-banner').classList.remove('hidden');
    }

    // Estado global de resultados para filtrado interactivo por pestañas
    let currentResultadosData = null;
    let activeFilter = 'todas';

    // Manejo de clics en las pestañas de filtro
    document.querySelectorAll('.tab-filter-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const filtro = btn.getAttribute('data-filter');
            aplicarFiltroResultados(filtro);
        });
    });

    function aplicarFiltroResultados(filtro) {
        activeFilter = filtro;

        document.querySelectorAll('.tab-filter-btn').forEach(btn => {
            const f = btn.getAttribute('data-filter');
            if (f === filtro) {
                btn.className = 'tab-filter-btn shrink-0 flex items-center gap-2 border-b-2 border-brand-950 px-4 py-2.5 text-sm font-bold text-brand-950 transition-colors';
            } else {
                btn.className = 'tab-filter-btn shrink-0 flex items-center gap-2 border-b-2 border-transparent px-4 py-2.5 text-sm font-semibold text-[#64748B] hover:text-brand-950 transition-colors';
            }
        });

        if (!currentResultadosData) return;

        let filteredMedidas = currentResultadosData.medidas;
        if (filtro === 'metas') {
            filteredMedidas = currentResultadosData.medidas.filter(m => m.tipo === 'Meta Sanitaria');
        } else if (filtro === 'iaaps') {
            filteredMedidas = currentResultadosData.medidas.filter(m => m.tipo === 'IAAPS');
        }

        renderizarTablaYGraficos(filteredMedidas);
    }

    function renderizarResultados(data) {
        currentResultadosData = data;
        const sectionResultados = document.getElementById('section-resultados');
        sectionResultados.classList.remove('hidden');

        // Contadores en las pestañas
        const countTodas = data.medidas.length;
        const countMetas = data.medidas.filter(m => m.tipo === 'Meta Sanitaria').length;
        const countIaaps = data.medidas.filter(m => m.tipo === 'IAAPS').length;

        document.getElementById('badge-count-todas').textContent = countTodas;
        document.getElementById('badge-count-metas').textContent = countMetas;
        document.getElementById('badge-count-iaaps').textContent = countIaaps;

        aplicarFiltroResultados(activeFilter || 'todas');

        // Scroll suave hacia los resultados
        sectionResultados.scrollIntoView({ behavior: 'smooth' });
    }

    function renderizarTablaYGraficos(medidas) {
        // Población de la tabla
        const tbody = document.getElementById('tabla-resultados-body');
        tbody.innerHTML = '';

        if (medidas.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="px-6 py-8 text-center text-sm text-[#94A3B8]">No hay indicadores para la pestaña seleccionada.</td></tr>';
        } else {
            medidas.forEach(m => {
                let statusBadge = '';
                let progressColor = 'bg-[#16A34A]';

                if (m.estado === 'Cumpliendo') {
                    statusBadge = '<span class="inline-flex rounded-full bg-[#E7F9EF] px-2.5 py-0.5 text-xs font-semibold text-[#16A34A]">Cumpliendo</span>';
                    progressColor = 'bg-[#16A34A]';
                } else if (m.estado === 'En Riesgo') {
                    statusBadge = '<span class="inline-flex rounded-full bg-[#FFF4E5] px-2.5 py-0.5 text-xs font-semibold text-[#F97316]">En Riesgo</span>';
                    progressColor = 'bg-[#F97316]';
                } else {
                    statusBadge = '<span class="inline-flex rounded-full bg-[#FDEDEC] px-2.5 py-0.5 text-xs font-semibold text-[#E11D2E]">Crítico</span>';
                    progressColor = 'bg-[#E11D2E]';
                }

                const tipoBadge = m.tipo === 'IAAPS'
                    ? '<span class="inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700 border border-amber-200">IAAPS</span>'
                    : '<span class="inline-flex rounded-full bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700 border border-blue-200">Meta</span>';

                const tr = document.createElement('tr');
                tr.className = 'hover:bg-[#F8FAFC] transition-colors';
                tr.innerHTML = `
                    <td class="px-6 py-4">${tipoBadge}</td>
                    <td class="px-6 py-4 font-mono font-bold text-brand-950">${m.codigo}</td>
                    <td class="px-6 py-4 font-medium text-[#334155]">${m.nombre}</td>
                    <td class="px-6 py-4 text-right font-mono text-[#334155]">${Number(m.numerador).toLocaleString('es-CL')}</td>
                    <td class="px-6 py-4 text-right font-mono text-[#334155]">${Number(m.denominador).toLocaleString('es-CL')}</td>
                    <td class="px-6 py-4 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <div class="h-2 w-20 overflow-hidden rounded-full bg-[#E2E8F0]">
                                <div class="h-full rounded-full ${progressColor}" style="width: ${Math.min(m.porcentaje, 100)}%"></div>
                            </div>
                            <span class="font-bold text-brand-950">${m.porcentaje.toFixed(2)}%</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-center font-semibold text-[#64748B]">${m.objetivo.toFixed(1)}%</td>
                    <td class="px-6 py-4 text-center">${statusBadge}</td>
                `;
                tbody.appendChild(tr);
            });
        }

        // Gráfico ApexCharts 1: Barras comparativas
        if (chartBarras) {
            chartBarras.destroy();
        }

        const barOptions = {
            series: [
                {
                    name: 'Cumplimiento Actual (%)',
                    data: medidas.map(m => m.porcentaje)
                },
                {
                    name: 'Meta Nacional / Comunal (%)',
                    data: medidas.map(m => m.objetivo)
                }
            ],
            chart: {
                type: 'bar',
                height: 330,
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
            dataLabels: {
                enabled: false
            },
            stroke: {
                show: true,
                width: 2,
                colors: ['transparent']
            },
            xaxis: {
                categories: medidas.map(m => m.codigo),
                labels: {
                    style: { colors: '#64748B', fontWeight: 600 }
                }
            },
            yaxis: {
                title: { text: 'Porcentaje / Tasa' },
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

        chartBarras = new ApexCharts(document.querySelector("#chart-barras"), barOptions);
        chartBarras.render();

        // Gráfico ApexCharts 2: Donut de Estados
        if (chartDonut) {
            chartDonut.destroy();
        }

        const totalItems = medidas.length;
        const cumpliendo = medidas.filter(m => m.estado === 'Cumpliendo').length;
        const enRiesgo = medidas.filter(m => m.estado === 'En Riesgo').length;
        const critico = medidas.filter(m => m.estado === 'Crítico').length;

        const donutOptions = {
            series: [cumpliendo, enRiesgo, critico],
            labels: ['Cumpliendo', 'En Riesgo', 'Crítico'],
            chart: {
                type: 'donut',
                height: 300,
                fontFamily: 'inherit'
            },
            colors: ['#16A34A', '#F97316', '#E11D2E'],
            legend: {
                position: 'bottom'
            },
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

        chartDonut = new ApexCharts(document.querySelector("#chart-donut"), donutOptions);
        chartDonut.render();
    }

    function setLoading(isLoading) {
        if (isLoading) {
            btnProcesar.disabled = true;
            spinner.classList.remove('hidden');
            btnText.textContent = 'Procesando planilla REM...';
        } else {
            btnProcesar.disabled = false;
            spinner.classList.add('hidden');
            btnText.textContent = 'Procesar y Proyectar Indicadores';
        }
    }
});
</script>
@endpush
