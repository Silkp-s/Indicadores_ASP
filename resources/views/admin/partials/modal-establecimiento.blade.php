{{--
    Modal para Crear y Editar Establecimientos de Salud desde el Panel de Administración.
--}}
<div
    id="modal-admin-establecimiento"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-brand-950/50 backdrop-blur-xs p-4 overflow-y-auto"
    role="dialog"
    aria-modal="true"
    aria-labelledby="form-admin-establecimiento-titulo"
>
    <div class="relative w-full max-w-2xl rounded-2xl bg-white shadow-2xl border border-[#E2E8F0] my-8 overflow-hidden">

        {{-- Cabecera del modal --}}
        <div class="flex items-center justify-between border-b border-[#E2E8F0] px-6 py-4 bg-[#F8FAFC]">
            <div>
                <h2 id="form-admin-establecimiento-titulo" class="text-base font-bold text-brand-950">Nuevo Establecimiento</h2>
                <p class="text-xs text-[#64748B]">Registre un recinto de salud de la red comunal</p>
            </div>
            <button
                type="button"
                data-cerrar-modal-establecimiento
                class="rounded-lg p-1.5 text-[#94A3B8] hover:bg-[#E2E8F0] hover:text-brand-950 transition-colors"
                aria-label="Cerrar ventana"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Formulario --}}
        <form
            id="form-admin-establecimiento"
            method="POST"
            action="{{ route('admin.establecimientos.store') }}"
            data-url-crear="{{ route('admin.establecimientos.store') }}"
            class="space-y-4 px-6 py-5 max-h-[calc(100vh-160px)] overflow-y-auto"
        >
            @csrf
            <input type="hidden" name="_method" value="PUT" disabled>
            <input type="hidden" name="establecimiento_id" value="">

            {{-- Nombre --}}
            <div>
                <label for="campo-est-nombre" class="block text-xs font-semibold uppercase tracking-wider text-brand-950">
                    Nombre del Establecimiento <span class="text-rose-500">*</span>
                </label>
                <input
                    type="text"
                    id="campo-est-nombre"
                    name="nombre"
                    required
                    maxlength="255"
                    placeholder="Ej: CESFAM San Vicente"
                    class="mt-1.5 w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm text-brand-950 placeholder:text-[#94A3B8] focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                >
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Código DEIS --}}
                <div>
                    <label for="campo-est-codigo-deis" class="block text-xs font-semibold uppercase tracking-wider text-brand-950">
                        Código DEIS (Oficial)
                    </label>
                    <input
                        type="text"
                        id="campo-est-codigo-deis"
                        name="codigo_deis"
                        maxlength="20"
                        placeholder="Ej: 119302"
                        class="mt-1.5 w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm text-brand-950 placeholder:text-[#94A3B8] focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20 font-mono"
                    >
                    <p class="mt-1 text-[11px] text-[#64748B]">Identificador numérico MINSAL</p>
                </div>

                {{-- Tipo de Recinto --}}
                <div>
                    <label for="campo-est-tipo" class="block text-xs font-semibold uppercase tracking-wider text-brand-950">
                        Tipo de Recinto <span class="text-rose-500">*</span>
                    </label>
                    <select
                        id="campo-est-tipo"
                        name="tipo"
                        required
                        class="mt-1.5 w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm text-brand-950 focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                    >
                        @foreach ($tiposEstablecimiento as $tipo)
                            <option value="{{ $tipo }}">{{ $tipo }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Población Inscrita --}}
                <div>
                    <label for="campo-est-poblacion" class="block text-xs font-semibold uppercase tracking-wider text-brand-950">
                        Población Inscrita <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="number"
                        id="campo-est-poblacion"
                        name="poblacion_inscrita"
                        required
                        min="0"
                        max="10000000"
                        placeholder="Ej: 28500"
                        class="mt-1.5 w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm text-brand-950 placeholder:text-[#94A3B8] focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                    >
                    <p class="mt-1 text-[11px] text-[#64748B]">Validada FONASA</p>
                </div>

                {{-- Estado --}}
                <div>
                    <label for="campo-est-estado" class="block text-xs font-semibold uppercase tracking-wider text-brand-950">
                        Estado Operativo <span class="text-rose-500">*</span>
                    </label>
                    <select
                        id="campo-est-estado"
                        name="is_active"
                        class="mt-1.5 w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm text-brand-950 focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                    >
                        <option value="1">Activo (Habilitado)</option>
                        <option value="0">Inactivo (Deshabilitado)</option>
                    </select>
                </div>
            </div>

            {{-- Botones de acción --}}
            <div class="flex flex-col-reverse sm:flex-row justify-end gap-2 sm:gap-3 border-t border-[#E2E8F0] pt-4">
                <button
                    type="button"
                    data-cerrar-modal-establecimiento
                    class="w-full sm:w-auto rounded-lg border border-[#CBD5E1] bg-white px-4 py-2 text-center text-sm font-semibold text-[#64748B] hover:bg-[#F8FAFC] transition-colors"
                >
                    Cancelar
                </button>
                <button
                    type="submit"
                    class="w-full sm:w-auto rounded-lg bg-brand-950 px-5 py-2 text-center text-sm font-semibold text-white shadow-xs hover:bg-brand-800 transition-colors"
                >
                    Guardar establecimiento
                </button>
            </div>
        </form>
    </div>
</div>
