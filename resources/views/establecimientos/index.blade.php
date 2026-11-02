@extends('layouts.app')
@use('App\Models\Establecimiento')

@section('title', 'Establecimientos de Salud')
@section('page-title', 'Establecimientos de Salud')
@section('page-subtitle', 'Administración de recintos de la red comunal')

@section('content')
<div class="space-y-6">

    {{-- Barra de Búsqueda, Filtros y Botón de Nuevo Establecimiento --}}
    <div class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-[#E2E8F0] bg-white p-4 shadow-xs">
        
        <form method="GET" action="{{ route('establecimientos.index') }}" data-filtros class="flex flex-1 flex-wrap items-center gap-3">
            
            {{-- Búsqueda libre --}}
            <div class="relative w-full flex-1 sm:min-w-[240px]">
                <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[#94A3B8]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input
                    type="text"
                    name="q"
                    value="{{ $filtros['q'] }}"
                    placeholder="Buscar por nombre o código DEIS..."
                    class="w-full rounded-lg border border-[#CBD5E1] bg-[#F8FAFC] py-2 pl-10 pr-3 text-sm text-brand-950 placeholder:text-[#94A3B8] focus:border-accent-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                >
            </div>

            {{-- Filtro por Tipo --}}
            <div class="w-full sm:w-auto min-w-0 sm:min-w-[160px]">
                <select
                    name="tipo"
                    onchange="this.form.submit()"
                    class="w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm font-medium text-brand-950 focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                >
                    <option value="">Tipo: Todos</option>
                    @foreach ($tipos as $tipo)
                        <option value="{{ $tipo }}" @selected($filtros['tipo'] === $tipo)>{{ $tipo }}</option>
                    @endforeach
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
                @if ($filtros['q'] || $filtros['tipo'] || $filtros['estado'] !== 'todos')
                    <a
                        href="{{ route('establecimientos.index') }}"
                        class="rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm font-medium text-[#64748B] hover:bg-[#F8FAFC] transition-colors"
                        title="Limpiar filtros"
                    >
                        Limpiar
                    </a>
                @endif
            </div>
        </form>

        {{-- Botón para abrir modal de nuevo establecimiento --}}
        <div class="w-full sm:w-auto shrink-0">
            <button
                type="button"
                data-nuevo-establecimiento
                class="flex w-full sm:w-auto items-center justify-center gap-2 rounded-lg bg-accent-500 px-4 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-accent-600 transition-colors"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>Nuevo establecimiento</span>
            </button>
        </div>

    </div>




    {{-- Alerta de error (por ejemplo, al intentar eliminar con mediciones vinculadas) --}}
    @if ($errors->has('establecimiento'))
        <div class="flex items-start gap-3 rounded-xl border border-status-bad-fg/30 bg-status-bad-bg p-4 text-status-bad-fg shadow-xs">
            <svg class="h-5 w-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div class="text-sm">
                <p class="font-bold">Acción Denegada</p>
                <p class="mt-0.5">{{ $errors->first('establecimiento') }}</p>
            </div>
        </div>
    @endif

    {{-- Panel de Métricas Rápidas --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        
        {{-- Total de Establecimientos --}}
        <div class="rounded-xl border border-[#E2E8F0] bg-white p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-[#64748B]">Red Asistencial</span>
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-100 text-brand-900 shadow-xs">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                    </svg>
                </span>
            </div>
            <p class="mt-2 text-3xl font-extrabold text-brand-950">{{ $stats['total'] }}</p>
            <p class="mt-1 text-xs text-[#64748B]">Recintos de salud en la comuna</p>
        </div>

        {{-- Establecimientos Activos --}}
        <div class="rounded-xl border border-[#E2E8F0] bg-white p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-[#16A34A]">Estado Operativo</span>
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-status-ok-bg text-status-ok-fg shadow-xs">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </span>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <p class="text-3xl font-extrabold text-brand-950">{{ $stats['activos'] }}</p>
                <span class="text-xs font-semibold text-status-ok-fg">activos</span>
                @if ($stats['inactivos'] > 0)
                    <span class="text-xs text-[#94A3B8]">({{ $stats['inactivos'] }} inactivos)</span>
                @endif
            </div>
            <p class="mt-1 text-xs text-[#64748B]">Habilitados para registro REM</p>
        </div>

        {{-- Población Inscrita Comunal --}}
        <div class="rounded-xl border border-[#E2E8F0] bg-white p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-[#64748B]">Población Comunal</span>
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-accent-50 text-accent-600 shadow-xs">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                </span>
            </div>
            <p class="mt-2 text-3xl font-extrabold text-brand-950">{{ number_format($stats['poblacion_total'], 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-[#64748B]">Inscritos en recintos activos</p>
        </div>

        {{-- Desglose de Tipos --}}
        <div class="rounded-xl border border-[#E2E8F0] bg-white p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-[#64748B]">Líneas de Atención</span>
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-100 text-brand-800 shadow-xs">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                    </svg>
                </span>
            </div>
            <div class="mt-2 flex flex-wrap gap-1.5">
                @foreach ($tipos as $t)
                    @php
                        $cnt = $stats['por_tipo'][$t] ?? 0;
                    @endphp
                    <span class="inline-flex items-center gap-1 rounded-md bg-[#F1F5F9] px-2 py-0.5 text-xs font-medium text-[#475569]">
                        <strong>{{ $t }}:</strong> {{ $cnt }}
                    </span>
                @endforeach
            </div>
            <p class="mt-2 text-[11px] text-[#94A3B8]">Centros clasificados por función asistencial</p>
        </div>

    </div>


    {{-- Tabla Principal de Establecimientos --}}
    <div class="overflow-hidden rounded-xl border border-[#E2E8F0] bg-white shadow-xs">
        
        @if ($filtros['q'] !== '')
            <div class="flex items-center justify-between gap-4 border-b border-[#E2E8F0] bg-[#F8FAFC] px-6 py-2.5 text-sm text-[#334155]">
                <span>Resultados de búsqueda para “<strong class="font-semibold text-brand-950">{{ $filtros['q'] }}</strong>”</span>
                <a href="{{ route('establecimientos.index') }}" class="font-medium text-accent-600 hover:underline text-xs">Limpiar búsqueda</a>
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-[#F8FAFC] border-b border-[#E2E8F0] text-xs font-semibold uppercase tracking-wider text-[#64748B]">
                    <tr>
                        <th scope="col" class="px-6 py-3.5">Código DEIS</th>
                        <th scope="col" class="px-6 py-3.5">Nombre del Establecimiento</th>
                        <th scope="col" class="px-6 py-3.5">Tipo</th>
                        <th scope="col" class="px-6 py-3.5 text-right">Población Inscrita</th>
                        
                        <th scope="col" class="px-6 py-3.5 text-center">Estado</th>
                        <th scope="col" class="px-6 py-3.5 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody id="establecimientos-filas" class="divide-y divide-[#F1F5F9]">
                    @forelse ($establecimientos as $est)
                        @php
                            $datos = [
                                'id' => $est->id,
                                'nombre' => $est->nombre,
                                'codigo_deis' => $est->codigo_deis ?? '',
                                'tipo' => $est->tipo,
                                'poblacion_inscrita' => (int) $est->poblacion_inscrita,
                                'is_active' => (bool) $est->is_active,
                                'url_actualizar' => route('establecimientos.update', $est),
                            ];
                        @endphp
                        <tr data-establecimiento="{{ json_encode($datos) }}" class="hover:bg-[#F8FAFC] transition-colors">
                            
                            {{-- Código DEIS --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($est->codigo_deis)
                                    <span class="inline-flex rounded-md bg-brand-100 px-2 py-0.5 font-mono text-xs font-bold text-brand-900 border border-brand-200">
                                        {{ $est->codigo_deis }}
                                    </span>
                                @else
                                    <span class="text-xs text-[#94A3B8] italic font-mono">Sin DEIS</span>
                                @endif
                            </td>

                            {{-- Nombre --}}
                            <td class="px-6 py-4">
                                <span class="block font-bold text-brand-950">{{ $est->nombre }}</span>
                                <span class="text-xs text-[#64748B]">Comuna de Talcahuano</span>
                            </td>

                            {{-- Tipo con badges temáticos --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $badgeStyle = match($est->tipo) {
                                        'CESFAM' => 'bg-blue-50 text-blue-700 border-blue-200',
                                        'CECOSF' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'SAR' => 'bg-purple-50 text-purple-700 border-purple-200',
                                        'SAPU' => 'bg-amber-50 text-amber-700 border-amber-200',
                                        'DAS' => 'bg-slate-100 text-slate-800 border-slate-300',
                                        default => 'bg-gray-50 text-gray-700 border-gray-200',
                                    };
                                @endphp
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold border {{ $badgeStyle }}">
                                    {{ $est->tipo }}
                                </span>
                            </td>

                            {{-- Población Inscrita --}}
                            <td class="px-6 py-4 text-right font-mono whitespace-nowrap">
                                <span class="font-bold text-brand-950">{{ number_format($est->poblacion_inscrita, 0, ',', '.') }}</span>
                                <span class="block text-[11px] text-[#94A3B8]">habitantes</span>
                            </td>



                            {{-- Estado Activo/Inactivo --}}
                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                @if ($est->is_active)
                                    <span class="inline-flex rounded-full bg-status-ok-bg px-2.5 py-0.5 text-xs font-semibold text-status-ok-fg">
                                        Activo
                                    </span>
                                @else
                                    <span class="inline-flex rounded-full bg-[#F1F5F9] px-2.5 py-0.5 text-xs font-semibold text-[#64748B]">
                                        Inactivo
                                    </span>
                                @endif
                            </td>

                            {{-- Botones de Acción (Editar / Estado / Eliminar) --}}
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-2">
                                    
                                    {{-- Botón Editar --}}
                                    <button
                                        type="button"
                                        data-accion="editar"
                                        class="rounded-lg border border-[#CBD5E1] bg-white px-2.5 py-1 text-xs font-medium text-[#475569] hover:bg-[#F8FAFC] hover:text-accent-600 transition-colors"
                                        title="Editar datos del establecimiento"
                                    >
                                        Editar
                                    </button>

                                    {{-- Botón Cambiar Estado --}}
                                    <form method="POST" action="{{ route('establecimientos.estado', $est) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="is_active" value="{{ $est->is_active ? 0 : 1 }}">
                                        @if ($est->is_active)
                                            <button
                                                type="submit"
                                                class="rounded-lg border border-[#CBD5E1] bg-white px-2.5 py-1 text-xs font-medium text-[#64748B] hover:bg-[#F8FAFC] hover:text-status-bad-fg transition-colors"
                                                title="Desactivar establecimiento"
                                            >
                                                Desactivar
                                            </button>
                                        @else
                                            <button
                                                type="submit"
                                                class="rounded-lg border border-[#CBD5E1] bg-white px-2.5 py-1 text-xs font-medium text-[#64748B] hover:bg-[#F8FAFC] hover:text-status-ok-fg transition-colors"
                                                title="Activar establecimiento"
                                            >
                                                Activar
                                            </button>
                                        @endif
                                    </form>

                                    {{-- Botón Eliminar --}}
                                    <form
                                        method="POST"
                                        action="{{ route('establecimientos.destroy', $est) }}"
                                        class="inline form-eliminar"
                                        data-swal-title="¿Eliminar establecimiento?"
                                        data-swal-text="¿Confirmas que deseas eliminar el establecimiento “{{ $est->nombre }}”? Esta acción no se puede deshacer."
                                        data-swal-confirm="Sí, eliminar"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="rounded-lg border border-status-bad-fg/30 bg-status-bad-bg px-2.5 py-1 text-xs font-medium text-status-bad-fg hover:bg-status-bad-fg/15 transition-colors"
                                            title="Eliminar establecimiento"
                                        >
                                            Eliminar
                                        </button>
                                    </form>

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-sm text-[#64748B]">
                                <svg class="mx-auto h-10 w-10 text-[#CBD5E1]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                <p class="mt-3 font-semibold text-brand-950">No se encontraron establecimientos</p>
                                <p class="mt-1 text-xs text-[#94A3B8]">Intenta ajustar o limpiar los filtros seleccionados.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginación --}}
        @if ($establecimientos->hasPages() || $establecimientos->total() > 0)
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-[#E2E8F0] px-6 py-3.5 text-sm text-[#64748B]">
                <span>
                    Mostrando <strong class="text-brand-950">{{ $establecimientos->firstItem() ?? 0 }}–{{ $establecimientos->lastItem() ?? 0 }}</strong> de {{ $establecimientos->total() }} recintos
                </span>
                {{ $establecimientos->links() }}
            </div>
        @endif

    </div>

</div>

{{-- Modal para Crear / Editar Establecimiento --}}
@include('establecimientos.partials.form-modal')
@endsection

@push('scripts')
<script>
    (() => {
        const formularioModal = document.getElementById('modal-establecimiento-form');
        const form = formularioModal.querySelector('form');
        let disparador = null;

        // Modales: control de apertura / cierre accesible
        const abrirModal = (modal, origen) => {
            disparador = origen;
            modal.classList.replace('hidden', 'flex');
            (modal.querySelector('input:not([type="hidden"]), select') ?? modal.querySelector('button'))?.focus();
        };

        const cerrarModal = (modal) => {
            modal.classList.replace('flex', 'hidden');
            disparador?.focus();
        };

        // Cierre al hacer click en el fondo o en [data-cerrar]
        formularioModal.addEventListener('click', (evento) => {
            if (evento.target === formularioModal || evento.target.closest('[data-cerrar]')) {
                cerrarModal(formularioModal);
            }
        });

        // Teclado: Escape cierra y Tab queda contenido en el modal
        document.addEventListener('keydown', (evento) => {
            if (!formularioModal.classList.contains('flex')) return;

            if (evento.key === 'Escape') {
                cerrarModal(formularioModal);
            }

            if (evento.key === 'Tab') {
                const enfocables = [...formularioModal.querySelectorAll('button, input:not([type="hidden"]), select')];
                const [primero, ultimo] = [enfocables[0], enfocables.at(-1)];

                if (evento.shiftKey && document.activeElement === primero) {
                    evento.preventDefault();
                    ultimo.focus();
                } else if (!evento.shiftKey && document.activeElement === ultimo) {
                    evento.preventDefault();
                    primero.focus();
                }
            }
        });

        // Prepara el formulario para Crear (vacío) o Editar (con datos)
        const prepararFormulario = (establecimiento) => {
            // Limpia mensajes de error previos
            form.querySelectorAll('[data-error]').forEach((err) => err.remove());
            form.querySelectorAll('[aria-invalid]').forEach((campo) => {
                campo.removeAttribute('aria-invalid');
                campo.removeAttribute('aria-describedby');
            });

            document.getElementById('form-titulo').textContent = establecimiento
                ? 'Editar establecimiento'
                : 'Nuevo establecimiento';

            form.action = establecimiento ? establecimiento.url_actualizar : form.dataset.urlCrear;
            form.elements._method.disabled = !establecimiento;
            form.elements.establecimiento_id.value = establecimiento?.id ?? '';

            if (establecimiento) {
                form.elements.nombre.value = establecimiento.nombre ?? '';
                form.elements.codigo_deis.value = establecimiento.codigo_deis ?? '';
                form.elements.tipo.value = establecimiento.tipo ?? 'CESFAM';
                form.elements.poblacion_inscrita.value = establecimiento.poblacion_inscrita ?? 0;
                form.elements.is_active.value = establecimiento.is_active ? '1' : '0';
            } else {
                form.elements.nombre.value = '';
                form.elements.codigo_deis.value = '';
                form.elements.tipo.value = 'CESFAM';
                form.elements.poblacion_inscrita.value = '0';
                form.elements.is_active.value = '1';
            }
        };

        // Evento botón "+ Nuevo establecimiento"
        document.querySelector('[data-nuevo-establecimiento]')?.addEventListener('click', (evento) => {
            prepararFormulario(null);
            abrirModal(formularioModal, evento.currentTarget);
        });

        // Evento botón "Editar" en las filas de la tabla
        document.getElementById('establecimientos-filas')?.addEventListener('click', (evento) => {
            const boton = evento.target.closest('[data-accion="editar"]');
            if (!boton) return;

            const tr = boton.closest('tr');
            if (!tr || !tr.dataset.establecimiento) return;

            const datos = JSON.parse(tr.dataset.establecimiento);
            prepararFormulario(datos);
            abrirModal(formularioModal, boton);
        });

        // Si el servidor devolvió errores de validación, reabrir el modal con los valores enviados
        if (formularioModal.dataset.reabrir === 'true') {
            abrirModal(formularioModal, document.querySelector('[data-nuevo-establecimiento]'));
            form.querySelector('[aria-invalid="true"]')?.focus();
        }
    })();
</script>
@endpush
