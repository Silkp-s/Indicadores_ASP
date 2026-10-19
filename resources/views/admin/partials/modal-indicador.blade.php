{{--
    Modal para Crear y Editar Metas Sanitarias e IAAPS desde el Panel de Administración.
--}}
<div
    id="modal-admin-indicador"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-brand-950/50 backdrop-blur-xs p-4 overflow-y-auto"
    role="dialog"
    aria-modal="true"
    aria-labelledby="form-admin-indicador-titulo"
>
    <div class="relative w-full max-w-2xl rounded-2xl bg-white shadow-2xl border border-[#E2E8F0] my-8 overflow-hidden">

        {{-- Cabecera del modal --}}
        <div class="flex items-center justify-between border-b border-[#E2E8F0] px-6 py-4 bg-[#F8FAFC]">
            <div>
                <h2 id="form-admin-indicador-titulo" class="text-base font-bold text-brand-950">Nueva Meta / Indicador IAAPS</h2>
                <p class="text-xs text-[#64748B]">Complete los datos de la Meta Sanitaria o Indicador IAAPS</p>
            </div>
            <button
                type="button"
                data-cerrar-modal-indicador
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
            id="form-admin-indicador"
            method="POST"
            action="{{ route('admin.indicadores.store') }}"
            data-url-crear="{{ route('admin.indicadores.store') }}"
            class="space-y-4 px-6 py-5 max-h-[calc(100vh-160px)] overflow-y-auto"
        >
            @csrf
            <input type="hidden" name="_method" value="PUT" disabled>
            <input type="hidden" name="indicador_id" value="">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Código --}}
                <div>
                    <label for="campo-codigo-admin" class="block text-xs font-semibold uppercase tracking-wider text-brand-950">
                        Código <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="text"
                        id="campo-codigo-admin"
                        name="codigo"
                        required
                        maxlength="20"
                        placeholder="Ej: MS-09 o IAAPS-06"
                        class="mt-1.5 w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm text-brand-950 placeholder:text-[#94A3B8] focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                    >
                </div>

                {{-- Tipo --}}
                <div>
                    <label for="campo-tipo-admin" class="block text-xs font-semibold uppercase tracking-wider text-brand-950">
                        Tipo de Indicador <span class="text-rose-500">*</span>
                    </label>
                    <select
                        id="campo-tipo-admin"
                        name="tipo"
                        required
                        class="mt-1.5 w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm text-brand-950 focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                    >
                        <option value="Meta Sanitaria">Meta Sanitaria</option>
                        <option value="IAAPS">IAAPS</option>
                    </select>
                </div>
            </div>

            {{-- Nombre --}}
            <div>
                <label for="campo-nombre-admin" class="block text-xs font-semibold uppercase tracking-wider text-brand-950">
                    Nombre del Indicador <span class="text-rose-500">*</span>
                </label>
                <input
                    type="text"
                    id="campo-nombre-admin"
                    name="nombre"
                    required
                    maxlength="255"
                    placeholder="Ej: Cobertura Efectiva de HTA en personas de 15 años y más"
                    class="mt-1.5 w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm text-brand-950 placeholder:text-[#94A3B8] focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                >
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                {{-- Meta (%) --}}
                <div>
                    <label for="campo-meta-admin" class="block text-xs font-semibold uppercase tracking-wider text-brand-950">
                        Meta (%) <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="number"
                        id="campo-meta-admin"
                        name="target_value"
                        required
                        step="0.01"
                        min="0"
                        max="100"
                        placeholder="85"
                        class="mt-1.5 w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm text-brand-950 placeholder:text-[#94A3B8] focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                    >
                </div>

                {{-- Periodicidad --}}
                <div>
                    <label for="campo-periodicidad-admin" class="block text-xs font-semibold uppercase tracking-wider text-brand-950">
                        Periodicidad <span class="text-rose-500">*</span>
                    </label>
                    <select
                        id="campo-periodicidad-admin"
                        name="periodicidad"
                        required
                        class="mt-1.5 w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm text-brand-950 focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                    >
                        @foreach (['Mensual', 'Trimestral', 'Semestral', 'Anual'] as $per)
                            <option value="{{ $per }}">{{ $per }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Fuente --}}
                <div>
                    <label for="campo-fuente-admin" class="block text-xs font-semibold uppercase tracking-wider text-brand-950">
                        Fuente <span class="text-rose-500">*</span>
                    </label>
                    <select
                        id="campo-fuente-admin"
                        name="fu"
                        required
                        class="mt-1.5 w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm text-brand-950 focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                    >
                        @foreach (['REM-A', 'REM-P', 'DEIS', 'SIGGES'] as $fuente)
                            <option value="{{ $fuente }}">{{ $fuente }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Estado --}}
            <div>
                <label for="campo-estado-admin" class="block text-xs font-semibold uppercase tracking-wider text-brand-950">
                    Estado <span class="text-rose-500">*</span>
                </label>
                <select
                    id="campo-estado-admin"
                    name="is_active"
                    class="mt-1.5 w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm text-brand-950 focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                >
                    <option value="1">Activo</option>
                    <option value="0">Inactivo</option>
                </select>
            </div>

            {{-- Definición del Numerador --}}
            <div>
                <label for="campo-numerador-admin" class="block text-xs font-semibold uppercase tracking-wider text-brand-950">
                    Definición / Fórmula del Numerador (N)
                </label>
                <textarea
                    id="campo-numerador-admin"
                    name="numerador_description"
                    rows="2"
                    placeholder="Ej: N° de personas de 15 años y más con HTA compensada"
                    class="mt-1.5 w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm text-brand-950 placeholder:text-[#94A3B8] focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                ></textarea>
            </div>

            {{-- Definición del Denominador --}}
            <div>
                <label for="campo-denominador-admin" class="block text-xs font-semibold uppercase tracking-wider text-brand-950">
                    Definición / Fórmula del Denominador (D)
                </label>
                <textarea
                    id="campo-denominador-admin"
                    name="denominador_description"
                    rows="2"
                    placeholder="Ej: Población estimada de 15 años y más con HTA según prevalencia"
                    class="mt-1.5 w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm text-brand-950 placeholder:text-[#94A3B8] focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                ></textarea>
            </div>

            {{-- Botones de acción --}}
            <div class="flex flex-col-reverse sm:flex-row justify-end gap-2 sm:gap-3 border-t border-[#E2E8F0] pt-4">
                <button
                    type="button"
                    data-cerrar-modal-indicador
                    class="w-full sm:w-auto rounded-lg border border-[#CBD5E1] bg-white px-4 py-2 text-center text-sm font-semibold text-[#64748B] hover:bg-[#F8FAFC] transition-colors"
                >
                    Cancelar
                </button>
                <button
                    type="submit"
                    class="w-full sm:w-auto rounded-lg bg-brand-950 px-5 py-2 text-center text-sm font-semibold text-white shadow-xs hover:bg-brand-800 transition-colors"
                >
                    Guardar indicador
                </button>
            </div>
        </form>
    </div>
</div>
