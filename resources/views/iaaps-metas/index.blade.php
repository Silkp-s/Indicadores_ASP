@extends('layouts.app')

@section('title', 'IAAPS y Metas')
@section('page-title', 'Módulo 2 · IAAPS y Metas Sanitarias')
@section('page-subtitle', 'Mantenedor de indicadores IAAPS y Metas Sanitarias')

@section('content')
<div class="space-y-6">

    <div class="flex flex-wrap items-center gap-3 rounded-md border border-[#E1E7E4] bg-white p-4">

        <div class="relative flex-1 min-w-[220px]">
            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#9AA6A1]" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z" />
            </svg>
            <input
                type="text"
                placeholder="Buscar por código o nombre del indicador..."
                class="w-full rounded-md border border-[#D7DEDB] bg-white py-2 pl-9 pr-3 text-sm placeholder:text-[#9AA6A1] focus:border-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-800/20"
            >
        </div>

        <select class="rounded-md border border-[#D7DEDB] bg-white px-3 py-2 text-sm text-[#4C5B56] focus:border-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-800/20">
            <option>Periodo: SEP 2026</option>
            <option>Periodo: AGO 2026</option>
            <option>Periodo: JUL 2026</option>
        </select>

        <select class="rounded-md border border-[#D7DEDB] bg-white px-3 py-2 text-sm text-[#4C5B56] focus:border-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-800/20">
            <option>Ver: IAAPS y Metas</option>
            <option>Ver: Solo IAAPS</option>
            <option>Ver: Solo Metas Sanitarias</option>
        </select>

        <select class="rounded-md border border-[#D7DEDB] bg-white px-3 py-2 text-sm text-[#4C5B56] focus:border-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-800/20">
            <option>Estado: Activo</option>
            <option>Estado: Inactivo</option>
            <option>Estado: Todos</option>
        </select>

        <button
            type="button"
            onclick="document.getElementById('modal-indicador').classList.remove('hidden')"
            class="ml-auto flex items-center gap-2 rounded-md bg-brand-950 px-4 py-2 text-sm font-medium text-white hover:bg-brand-800"
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Nuevo indicador
        </button>
    </div>

    <div class="overflow-hidden rounded-md border border-[#E1E7E4] bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-[#F4F6F5] text-xs font-medium uppercase tracking-wide text-[#6B7A75]">
                <tr>
                    <th class="px-5 py-3">Código</th>
                    <th class="px-5 py-3">Nombre indicador</th>
                    <th class="px-5 py-3">Tipo</th>
                    <th class="px-5 py-3">Meta</th>
                    <th class="px-5 py-3">Avance físico</th>
                    <th class="px-5 py-3">Proyección</th>
                    <th class="px-5 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#E9EEEC]">

                @foreach ([
                    ['cod' => 'MS-01', 'nombre' => 'Evaluación del Desarrollo Psicomotor (12 a 23 meses)', 'tipo' => 'Meta Sanitaria', 'meta' => '85%', 'avance' => 85, 'proy' => ['label' => '90%', 'estado' => 'ok']],
                    ['cod' => 'IAAPS-04', 'nombre' => 'Cobertura Efectiva DM2 en personas de 15 años y más', 'tipo' => 'IAAPS', 'meta' => '55%', 'avance' => 55, 'proy' => ['label' => '80%', 'estado' => 'ok']],
                    ['cod' => 'MS-01', 'nombre' => 'Evaluación del Desarrollo Psicomotor (12 a 23 meses)', 'tipo' => 'Meta Sanitaria', 'meta' => '55%', 'avance' => 55, 'proy' => ['label' => '70%', 'estado' => 'medio']],
                    ['cod' => 'MS-01', 'nombre' => 'Evaluación del Desarrollo Psicomotor (12 a 23 meses)', 'tipo' => 'Meta Sanitaria', 'meta' => '75%', 'avance' => 75, 'proy' => ['label' => '20%', 'estado' => 'bajo']],
                ] as $fila)
                <tr class="hover:bg-[#F9FBFA]">
                    <td class="px-5 py-3 font-medium text-brand-950">{{ $fila['cod'] }}</td>
                    <td class="px-5 py-3 text-[#3A4844]">{{ $fila['nombre'] }}</td>
                    <td class="px-5 py-3">
                        <span @class([
                            'rounded-full px-2.5 py-1 text-xs font-medium',
                            'bg-brand-100 text-brand-800' => $fila['tipo'] === 'Meta Sanitaria',
                            'bg-[#FBF1E4] text-[#96622A]' => $fila['tipo'] === 'IAAPS',
                        ])>
                            {{ $fila['tipo'] }}
                        </span>
                    </td>
                    <td class="px-5 py-3 text-[#3A4844]">{{ $fila['meta'] }}</td>
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-2">
                            <div class="h-1.5 w-24 overflow-hidden rounded-full bg-[#E9EEEC]">
                                <div class="h-full rounded-full bg-brand-800" style="width: {{ $fila['avance'] }}%"></div>
                            </div>
                            <span class="text-xs text-[#6B7A75]">{{ $fila['avance'] }}%</span>
                        </div>
                    </td>
                    <td class="px-5 py-3">
                        <span @class([
                            'rounded-full px-2.5 py-1 text-xs font-medium',
                            'bg-status-ok-bg text-status-ok-fg' => $fila['proy']['estado'] === 'ok',
                            'bg-status-warn-bg text-status-warn-fg' => $fila['proy']['estado'] === 'medio',
                            'bg-status-bad-bg text-status-bad-fg' => $fila['proy']['estado'] === 'bajo',
                        ])>
                            {{ $fila['proy']['label'] }}
                        </span>
                    </td>
                    <td class="px-5 py-3">
                        <div class="flex items-center justify-end gap-3">
                            <button type="button" onclick="document.getElementById('modal-indicador').classList.remove('hidden')" class="text-[#6B7A75] hover:text-brand-800" title="Editar">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                                </svg>
                            </button>
                            <button type="button" class="text-[#6B7A75] hover:text-status-bad-fg" title="Eliminar">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
                @endforeach

            </tbody>
        </table>

        <div class="flex items-center justify-between border-t border-[#E1E7E4] px-5 py-3 text-sm text-[#6B7A75]">
            <span>Mostrando 4 de 128 indicadores</span>
            <div class="flex items-center gap-1">
                <button class="rounded-md px-3 py-1.5 hover:bg-[#F4F6F5]" disabled>Anterior</button>
                <button class="rounded-md bg-brand-950 px-3 py-1.5 text-white">1</button>
                <button class="rounded-md px-3 py-1.5 hover:bg-[#F4F6F5]">2</button>
                <button class="rounded-md px-3 py-1.5 hover:bg-[#F4F6F5]">3</button>
                <button class="rounded-md px-3 py-1.5 hover:bg-[#F4F6F5]">Siguiente</button>
            </div>
        </div>
    </div>
</div>

@include('iaaps-metas.partials.form-modal')
@endsection
