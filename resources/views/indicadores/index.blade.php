@extends('layouts.app')
@use('App\Models\Indicador')

@section('title', 'Catálogo de Indicadores')
@section('page-title', 'Módulo 1 | Catálogo de Indicadores')

{{--
    NOTA PARA EL EQUIPO:
    Módulo 1 conectado a la tabla `indicadores` (IndicadorController). La búsqueda del header,
    los filtros, la paginación, guardar, desactivar y exportar funcionan en el servidor.
    "Cumplimiento act." y el gráfico de cumplimiento anual esperan el registro de mediciones
    (`indicador_medidas`); mientras tanto, la columna dice "Sin mediciones" y el gráfico usa datos de ejemplo.
    Los gráficos son SVG puro para no depender todavía de una librería de gráficos.
--}}

@section('content')
@php
    $tipos = [
        'IAAPS' => ['badge' => 'bg-brand-950/8 text-brand-950', 'punto' => 'bg-brand-950', 'color' => 'var(--color-brand-950)'],
        'Meta Sanitaria' => ['badge' => 'bg-accent-500/10 text-accent-600', 'punto' => 'bg-accent-500', 'color' => 'var(--color-accent-500)'],
        'Ministerial' => ['badge' => 'bg-[#93B4F8]/25 text-[#3B63B5]', 'punto' => 'bg-[#93B4F8]', 'color' => '#93B4F8'],
    ];

    // Periodo y Establecimiento se aplicarán a las mediciones; por ahora no filtran (no tienen name).
    $chips = [
        ['nombre' => null, 'etiqueta' => 'Periodo', 'prefijo' => 'Periodo', 'opciones' => ['SEP 2026' => 'SEP 2026', 'AGO 2026' => 'AGO 2026', 'JUL 2026' => 'JUL 2026']],
        ['nombre' => null, 'etiqueta' => 'Establecimiento', 'prefijo' => null, 'opciones' => ['comunal' => 'Comunal', 'san-vicente' => 'San Vicente', 'lirquen' => 'Lirquén', 'los-cerros' => 'Los Cerros', 'bellavista' => 'Bellavista']],
        ['nombre' => 'tipo', 'etiqueta' => 'Tipo', 'prefijo' => 'Tipo', 'opciones' => ['' => 'Todos'] + array_combine(Indicador::TIPOS, Indicador::TIPOS)],
        ['nombre' => 'fuente', 'etiqueta' => 'Fuente', 'prefijo' => 'Fuente', 'opciones' => ['' => 'Todas'] + array_combine(Indicador::FUENTES, Indicador::FUENTES)],
        ['nombre' => 'estado', 'etiqueta' => 'Estado', 'prefijo' => 'Estado', 'opciones' => ['activo' => 'Activos', 'inactivo' => 'Inactivos', 'todos' => 'Todos']],
    ];

    // Escala del gráfico de barras: 4 marcas que cubren el máximo.
    $maximoPorFuente = max(1, ...array_merge(...array_map('array_values', array_values($porFuente))));
    $pasoPorFuente = (int) ceil($maximoPorFuente / 4);
@endphp

<div class="space-y-6">

    {{-- Descripción, filtros y acciones --}}
    <div class="flex flex-wrap items-start gap-x-6 gap-y-4 xl:flex-nowrap">
        <p class="w-full text-[15px] leading-snug text-[#334155] xl:w-48 xl:shrink-0">
            Registro maestro de indicadores APS: fórmulas, metas y fuentes de información
        </p>

        <form method="GET" action="{{ route('indicadores.index') }}" data-filtros class="flex flex-1 flex-wrap items-center gap-2.5">
            @if ($filtros['q'] !== '')
                <input type="hidden" name="q" value="{{ $filtros['q'] }}">
            @endif

            @foreach ($chips as $chip)
                <div class="relative">
                    <select
                        @if ($chip['nombre']) name="{{ $chip['nombre'] }}" @else title="Se aplicará al registro de mediciones" @endif
                        aria-label="{{ $chip['nombre'] ? 'Filtrar por '.Str::lower($chip['etiqueta']) : $chip['etiqueta'] }}"
                        class="cursor-pointer appearance-none rounded-lg border border-accent-500/15 bg-brand-100 py-2 pl-3.5 pr-9 text-sm font-medium text-brand-950 hover:border-accent-500/40 focus:outline-none focus-visible:border-accent-500 focus-visible:ring-2 focus-visible:ring-accent-500 focus-visible:ring-offset-2 focus-visible:ring-offset-[#F5F7FB]"
                    >
                        @foreach ($chip['opciones'] as $valor => $texto)
                            <option value="{{ $valor }}" @selected($chip['nombre'] && (string) ($filtros[$chip['nombre']] ?? '') === (string) $valor)>{{ $chip['prefijo'] ? $chip['prefijo'].': '.$texto : $texto }}</option>
                        @endforeach
                    </select>
                    <svg class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-brand-950/70" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                    </svg>
                </div>
            @endforeach

            <button type="submit" class="sr-only rounded-lg bg-brand-950 px-3 py-2 text-sm font-medium text-white focus:not-sr-only">Aplicar filtros</button>
        </form>

        <div class="flex shrink-0 items-center gap-3">
            <a href="{{ route('indicadores.exportar', Arr::except(request()->query(), 'page')) }}" class="flex items-center gap-2 rounded-lg border border-[#D8DEE9] bg-white px-4 py-2.5 text-sm font-medium text-[#1E293B] shadow-sm hover:bg-[#F8FAFC]" title="Descargar en CSV (Excel) los indicadores de la búsqueda y filtros actuales">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                Exportar
            </a>
            <button type="button" data-nuevo-indicador class="flex items-center gap-2 rounded-lg bg-accent-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-accent-600">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Nuevo indicador
            </button>
        </div>
    </div>

    {{-- Gráficos --}}
    <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
        <section class="rounded-xl border border-[#E2E8F0] bg-white px-5 pb-3 pt-4 shadow-sm">
            <h2 class="text-center text-lg font-bold text-brand-950">Cumplimiento Anual por Tipo (Proyección)</h2>
            <p class="text-center text-xs text-[#64748B]">Datos de ejemplo hasta que exista el registro de mediciones</p>
            <div class="mt-1">
                @include('indicadores.partials.grafico-cumplimiento', [
                    'titulo' => 'Cumplimiento anual por tipo de indicador (datos de ejemplo)',
                    'anios' => ['2022', '2023', '2024', '2025', '2026'],
                    'ultimoReal' => 3,
                    'series' => [
                        ['nombre' => 'IAAPS', 'color' => $tipos['IAAPS']['color'], 'valores' => [61, 64, 70, 73, 78]],
                        ['nombre' => 'Metas Sanitarias', 'color' => $tipos['Meta Sanitaria']['color'], 'valores' => [55, 62, 59, 68, 74]],
                    ],
                ])
            </div>
        </section>

        <section class="rounded-xl border border-[#E2E8F0] bg-white px-5 pb-3 pt-4 shadow-sm">
            <h2 class="text-center text-lg font-bold text-brand-950">Indicadores por Fuente de Información</h2>
            <p class="text-center text-xs text-[#64748B]">Indicadores activos del catálogo</p>
            <div class="mt-1">
                @include('indicadores.partials.grafico-fuentes', [
                    'titulo' => 'Indicadores activos del catálogo por fuente de información',
                    'grupos' => array_keys($porFuente),
                    'maximo' => $pasoPorFuente * 4,
                    'paso' => $pasoPorFuente,
                    'series' => collect(Indicador::TIPOS)->map(fn (string $tipo) => [
                        'nombre' => $tipo === 'Meta Sanitaria' ? 'Metas Sanitarias' : $tipo,
                        'color' => $tipos[$tipo]['color'],
                        'valores' => array_values(array_column($porFuente, $tipo)),
                    ])->all(),
                ])
            </div>
        </section>
    </div>

    {{-- Tabla del catálogo --}}
    <section class="overflow-hidden rounded-xl border border-[#E2E8F0] bg-white shadow-sm" aria-labelledby="tabla-catalogo-titulo">
        <h2 id="tabla-catalogo-titulo" class="sr-only">Catálogo de indicadores</h2>

        @if ($filtros['q'] !== '')
            <div class="flex items-center justify-between gap-4 border-b border-[#E2E8F0] bg-[#F8FAFC] px-4 py-2.5 text-sm text-[#334155]">
                <span>Resultados para “<strong class="font-semibold text-brand-950">{{ $filtros['q'] }}</strong>”</span>
                <a href="{{ route('indicadores.index', Arr::except(request()->query(), ['q', 'page'])) }}" class="font-medium text-accent-600 hover:underline">Limpiar búsqueda</a>
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-left text-sm">
                <thead class="border-b border-[#E2E8F0]">
                    <tr class="text-xs font-bold uppercase tracking-wide text-brand-950">
                        <th scope="col" class="py-3 pl-4 pr-2">Código</th>
                        <th scope="col" class="px-2 py-3">Nombre indicador</th>
                        <th scope="col" class="px-2 py-3">Tipo</th>
                        <th scope="col" class="px-2 py-3">Fuente y<br>periodicidad</th>
                        <th scope="col" class="px-2 py-3">Meta<br>anual</th>
                        <th scope="col" class="px-2 py-3">Cumplimiento act.</th>
                        <th scope="col" class="px-2 py-3">Estado</th>
                        <th scope="col" class="px-2 py-3 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody id="catalogo-filas" class="divide-y divide-[#EEF2F7]">

                    @forelse ($indicadores as $indicador)
                        @php
                            $datos = $indicador->only(['id', 'codigo', 'nombre', 'tipo', 'numerador_description', 'denominador_description', 'target_value', 'periodicidad', 'fu', 'is_active']) + [
                                'meta_formateada' => $indicador->meta_formateada,
                                'estado_label' => $indicador->is_active ? 'Activo' : 'Inactivo',
                                'url_actualizar' => route('indicadores.update', $indicador),
                            ];
                        @endphp
                        <tr data-indicador="{{ json_encode($datos) }}" class="hover:bg-[#F8FAFC]">
                            <td class="whitespace-nowrap py-2.5 pl-4 pr-2 font-semibold text-brand-950">{{ $indicador->codigo }}</td>
                            <td class="min-w-[170px] px-2 py-2.5 text-[#1E293B]">
                                <p class="max-w-[280px] leading-tight">{{ $indicador->nombre }}</p>
                            </td>
                            <td class="px-2 py-2.5">
                                <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold {{ $tipos[$indicador->tipo]['badge'] }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $tipos[$indicador->tipo]['punto'] }}" aria-hidden="true"></span>
                                    {{ $indicador->tipo }}
                                </span>
                            </td>
                            <td class="px-2 py-2.5">
                                <span class="whitespace-nowrap rounded-md border border-[#E2E8F0] bg-[#F8FAFC] px-2 py-0.5 text-xs font-semibold text-[#334155]">{{ $indicador->fu }}</span>
                                <p class="mt-0.5 text-xs text-[#64748B]">{{ $indicador->periodicidad }}</p>
                            </td>
                            <td class="px-2 py-2.5 font-semibold text-brand-950">{{ $indicador->meta_formateada }}%</td>
                            <td class="px-2 py-2.5">
                                <div class="w-24">
                                    <p class="text-xs text-[#64748B]">Sin mediciones</p>
                                    <div class="mt-1.5 h-2 rounded-full bg-[#E2E8F0]"></div>
                                </div>
                            </td>
                            <td class="px-2 py-2.5">
                                <span class="inline-flex items-center gap-1.5 whitespace-nowrap font-medium text-[#1E293B]">
                                    @if ($indicador->is_active)
                                        <svg class="h-5 w-5 shrink-0 text-status-ok-fg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" />
                                        </svg>
                                        Activo
                                    @else
                                        <svg class="h-5 w-5 shrink-0 text-[#94A3B8]" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM6.75 9.25a.75.75 0 000 1.5h6.5a.75.75 0 000-1.5h-6.5z" />
                                        </svg>
                                        Inactivo
                                    @endif
                                </span>
                            </td>
                            <td class="px-2 py-2.5">
                                <div class="flex items-start justify-center gap-1">
                                    <button type="button" data-accion="detalle" class="flex min-w-10 flex-col items-center gap-1 px-0.5 text-[#475569] hover:text-accent-500" aria-label="Ver detalle de {{ $indicador->codigo }}">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                        </svg>
                                        <span class="text-[11px] font-medium">Detalle</span>
                                    </button>
                                    <button type="button" data-accion="editar" class="flex min-w-10 flex-col items-center gap-1 px-0.5 text-[#475569] hover:text-accent-500" aria-label="Editar {{ $indicador->codigo }}">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                                        </svg>
                                        <span class="text-[11px] font-medium">Editar</span>
                                    </button>
                                    <form method="POST" action="{{ route('indicadores.estado', $indicador) }}"
                                          @if ($indicador->is_active) data-confirmar="¿Desactivar el indicador {{ $indicador->codigo }}? Dejará de aparecer entre los activos." @endif>
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="is_active" value="{{ $indicador->is_active ? 0 : 1 }}">
                                        @if ($indicador->is_active)
                                            <button type="submit" class="flex min-w-10 flex-col items-center gap-1 px-0.5 text-[#475569] hover:text-status-bad-fg" aria-label="Desactivar {{ $indicador->codigo }}">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                                </svg>
                                                <span class="text-[11px] font-medium">Desactivar</span>
                                            </button>
                                        @else
                                            <button type="submit" class="flex min-w-10 flex-col items-center gap-1 px-0.5 text-[#475569] hover:text-status-ok-fg" aria-label="Activar {{ $indicador->codigo }}">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                                <span class="text-[11px] font-medium">Activar</span>
                                            </button>
                                        @endif
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-10 text-center text-sm text-[#64748B]">
                                No hay indicadores que coincidan con la búsqueda o los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginación --}}
        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-[#E2E8F0] px-4 py-3 text-sm text-[#64748B]">
            <span>
                @if ($indicadores->total() > 0)
                    Mostrando <span class="font-semibold text-brand-950">{{ $indicadores->firstItem() }}–{{ $indicadores->lastItem() }}</span> de {{ $indicadores->total() }} indicadores
                @else
                    Sin resultados
                @endif
            </span>
            {{ $indicadores->onEachSide(1)->links('indicadores.partials.paginacion') }}
        </div>
    </section>
</div>

@include('indicadores.partials.detalle-modal')
@include('indicadores.partials.form-modal')
@endsection

@push('scripts')
<script>
    (() => {
        const detalle = document.getElementById('modal-catalogo-detalle');
        const formulario = document.getElementById('modal-catalogo-form');
        const form = formulario.querySelector('form');
        let indicadorActual = null;
        let disparador = null;

        // Filtros: con el mouse se aplican al elegir; con el teclado (flechas) se aplican con Enter o "Aplicar filtros",
        // para no recargar la página en cada tecla.
        document.querySelectorAll('[data-filtros] select[name]').forEach((select) => {
            let conTeclado = false;

            select.addEventListener('keydown', (evento) => {
                conTeclado = true;

                if (evento.key === 'Enter') {
                    select.form.requestSubmit();
                }
            });
            select.addEventListener('pointerdown', () => { conTeclado = false; });
            select.addEventListener('change', () => {
                if (! conTeclado) {
                    select.form.requestSubmit();
                }
            });
        });

        // Confirmación antes de desactivar un indicador
        document.addEventListener('submit', (evento) => {
            const mensaje = evento.target.dataset.confirmar;

            if (mensaje && ! window.confirm(mensaje)) {
                evento.preventDefault();
            }
        });

        // Modales: al cerrar, el foco vuelve al botón que abrió el modal
        const abrir = (modal, origen) => {
            disparador = origen;
            modal.classList.replace('hidden', 'flex');
            (modal.querySelector('input:not([type="hidden"]), select, textarea') ?? modal.querySelector('button'))?.focus();
        };

        const cerrar = (modal) => {
            modal.classList.replace('flex', 'hidden');
            disparador?.focus();
        };

        // El clic en el fondo solo cierra si también empezó en el fondo (no al arrastrar una selección desde un campo)
        document.querySelectorAll('[data-modal]').forEach((modal) => {
            let pulsoEnFondo = false;

            modal.addEventListener('mousedown', (evento) => { pulsoEnFondo = evento.target === modal; });
            modal.addEventListener('click', (evento) => {
                if ((pulsoEnFondo && evento.target === modal) || evento.target.closest('[data-cerrar]')) {
                    cerrar(modal);
                }
            });
        });

        // Escape cierra; Tab y Shift+Tab quedan dentro del modal abierto
        document.addEventListener('keydown', (evento) => {
            const abierto = document.querySelector('[data-modal].flex');

            if (! abierto) {
                return;
            }

            if (evento.key === 'Escape') {
                cerrar(abierto);
            }

            if (evento.key === 'Tab') {
                const enfocables = [...abierto.querySelectorAll('button, input:not([type="hidden"]), select, textarea')];
                const [primero, ultimo] = [enfocables[0], enfocables.at(-1)];

                if (evento.shiftKey && document.activeElement === primero) {
                    evento.preventDefault();
                    ultimo.focus();
                } else if (! evento.shiftKey && document.activeElement === ultimo) {
                    evento.preventDefault();
                    primero.focus();
                }
            }
        });

        const mostrarDetalle = (indicador, origen) => {
            detalle.querySelectorAll('[data-campo]').forEach((campo) => {
                const valor = indicador[campo.dataset.campo];
                campo.textContent = valor === null || valor === '' ? '—' : `${valor}${campo.dataset.sufijo ?? ''}`;
            });
            abrir(detalle, origen);
        };

        // Deja el formulario listo para crear (sin indicador) o para editar el indicador dado
        const prepararFormulario = (indicador) => {
            form.querySelectorAll('[data-error]').forEach((error) => error.remove());
            form.querySelectorAll('[aria-invalid]').forEach((campo) => {
                campo.removeAttribute('aria-invalid');
                campo.removeAttribute('aria-describedby');
            });
            form.querySelectorAll('input:not([type="hidden"]), textarea').forEach((campo) => { campo.value = ''; });
            form.querySelectorAll('select').forEach((campo) => { campo.selectedIndex = 0; });

            document.getElementById('form-titulo').textContent = indicador ? 'Editar indicador' : 'Nuevo indicador';
            form.action = indicador ? indicador.url_actualizar : form.dataset.urlCrear;
            form.elements._method.disabled = ! indicador;
            form.elements.indicador_id.value = indicador?.id ?? '';

            if (indicador) {
                ['codigo', 'tipo', 'nombre', 'numerador_description', 'denominador_description', 'target_value', 'periodicidad', 'fu']
                    .forEach((campo) => { form.elements[campo].value = indicador[campo] ?? ''; });
                form.elements.is_active.value = indicador.is_active ? '1' : '0';
            }
        };

        const mostrarFormulario = (indicador, origen) => {
            prepararFormulario(indicador);
            abrir(formulario, origen);
        };

        document.getElementById('catalogo-filas').addEventListener('click', (evento) => {
            const boton = evento.target.closest('[data-accion]');

            if (! boton) {
                return;
            }

            indicadorActual = JSON.parse(boton.closest('tr').dataset.indicador);
            boton.dataset.accion === 'detalle' ? mostrarDetalle(indicadorActual, boton) : mostrarFormulario(indicadorActual, boton);
        });

        document.querySelector('[data-nuevo-indicador]').addEventListener('click', (evento) => mostrarFormulario(null, evento.currentTarget));

        detalle.querySelector('[data-editar-desde-detalle]').addEventListener('click', () => {
            const origen = disparador;
            cerrar(detalle);
            mostrarFormulario(indicadorActual, origen);
        });

        // Si el servidor devolvió errores de validación, reabrir el formulario tal como se envió
        if (formulario.dataset.reabrir === 'true') {
            abrir(formulario, document.querySelector('[data-nuevo-indicador]'));
            form.querySelector('[aria-invalid="true"]')?.focus();
        }
    })();
</script>
@endpush
