{{--
    Modal para Crear y Editar Mapeos de Celdas REM (Extracción Automatizada).
--}}
<div
    id="modal-admin-rem-mapping"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-brand-950/50 backdrop-blur-xs p-4 overflow-y-auto"
    role="dialog"
    aria-modal="true"
    aria-labelledby="form-admin-rem-titulo"
>
    <div class="relative w-full max-w-2xl rounded-2xl bg-white shadow-2xl border border-[#E2E8F0] my-8 overflow-hidden">

        {{-- Cabecera del modal --}}
        <div class="flex items-center justify-between border-b border-[#E2E8F0] px-6 py-4 bg-[#F8FAFC]">
            <div>
                <h2 id="form-admin-rem-titulo" class="text-base font-bold text-brand-950">Nuevo Mapeo de Celda REM</h2>
                <p class="text-xs text-[#64748B]">Indica al motor de importación en qué hoja y celda(s) extraer los datos</p>
            </div>
            <button
                type="button"
                data-cerrar-modal-rem
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
            id="form-admin-rem"
            method="POST"
            action="{{ route('admin.rem-mappings.store') }}"
            data-url-crear="{{ route('admin.rem-mappings.store') }}"
            class="space-y-4 px-6 py-5 max-h-[calc(100vh-160px)] overflow-y-auto"
        >
            @csrf
            <input type="hidden" name="_method" value="PUT" disabled>
            <input type="hidden" name="mapping_id" value="">

            {{-- Indicador asociado --}}
            <div>
                <label for="campo-indicador-rem" class="block text-xs font-semibold uppercase tracking-wider text-brand-950">
                    Indicador / Meta Sanitaria <span class="text-rose-500">*</span>
                </label>
                <select
                    id="campo-indicador-rem"
                    name="indicadores_id"
                    required
                    class="mt-1.5 w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm text-brand-950 focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                >
                    <option value="">Seleccione el indicador a vincular...</option>
                    @foreach ($indicadoresDisponibles as $ind)
                        <option value="{{ $ind->id }}">{{ $ind->codigo }} · {{ $ind->nombre }} ({{ $ind->tipo }})</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Hoja REM --}}
                <div>
                    <label for="campo-hoja-rem" class="block text-xs font-semibold uppercase tracking-wider text-brand-950">
                        Hoja REM <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="text"
                        id="campo-hoja-rem"
                        name="hoja"
                        required
                        maxlength="50"
                        placeholder="Ej: A03, P06, A01, A26"
                        class="mt-1.5 w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm text-brand-950 placeholder:text-[#94A3B8] focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20 font-mono"
                    >
                    <p class="mt-1 text-[11px] text-[#64748B]">Nombre de pestaña en Excel (ej: A03 o P06)</p>
                </div>

                {{-- Sección opcional --}}
                <div>
                    <label for="campo-seccion-rem" class="block text-xs font-semibold uppercase tracking-wider text-brand-950">
                        Sección / Subtítulo (Opcional)
                    </label>
                    <input
                        type="text"
                        id="campo-seccion-rem"
                        name="seccion"
                        maxlength="100"
                        placeholder="Ej: Sección A.2 o Sección B.1"
                        class="mt-1.5 w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm text-brand-950 placeholder:text-[#94A3B8] focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                    >
                </div>
            </div>

            {{-- Celda Numerador --}}
            <div>
                <label for="campo-celda-num-rem" class="block text-xs font-semibold uppercase tracking-wider text-brand-950">
                    Celda(s) del Numerador (N) <span class="text-rose-500">*</span>
                </label>
                <input
                    type="text"
                    id="campo-celda-num-rem"
                    name="celda_numerador"
                    required
                    maxlength="255"
                    placeholder="Ej: G21 ó J26:M26, J28:M28 ó L34, M34"
                    class="mt-1.5 w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm text-brand-950 placeholder:text-[#94A3B8] focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20 font-mono"
                >
                <p class="mt-1 text-[11px] text-[#64748B]">
                    Permite celda única (<code class="bg-[#F1F5F9] px-1 py-0.5 rounded text-brand-950">G21</code>), lista (<code class="bg-[#F1F5F9] px-1 py-0.5 rounded text-brand-950">L34, M34</code>) o rangos (<code class="bg-[#F1F5F9] px-1 py-0.5 rounded text-brand-950">J26:M26</code>).
                </p>
            </div>

            {{-- Celda Denominador --}}
            <div>
                <label for="campo-celda-den-rem" class="block text-xs font-semibold uppercase tracking-wider text-brand-950">
                    Celda(s) del Denominador (D) <span class="text-[11px] font-normal text-[#64748B] lowercase">(opcional si se calcula por población)</span>
                </label>
                <input
                    type="text"
                    id="campo-celda-den-rem"
                    name="celda_denominador"
                    maxlength="255"
                    placeholder="Ej: J23:M23 ó L141, P141 (dejar vacío si proviene de población inscrita)"
                    class="mt-1.5 w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm text-brand-950 placeholder:text-[#94A3B8] focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20 font-mono"
                >
            </div>

            {{-- Descripción de extracción --}}
            <div>
                <label for="campo-desc-rem" class="block text-xs font-semibold uppercase tracking-wider text-brand-950">
                    Descripción / Notas Técnicas
                </label>
                <textarea
                    id="campo-desc-rem"
                    name="descripcion"
                    rows="2"
                    placeholder="Ej: Niños de 12 a 23 meses con desarrollo psicomotor recuperado en reevaluación."
                    class="mt-1.5 w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm text-brand-950 placeholder:text-[#94A3B8] focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                ></textarea>
            </div>

            {{-- Estado --}}
            <div>
                <label for="campo-estado-rem" class="block text-xs font-semibold uppercase tracking-wider text-brand-950">
                    Estado del Mapeo <span class="text-rose-500">*</span>
                </label>
                <select
                    id="campo-estado-rem"
                    name="is_active"
                    class="mt-1.5 w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm text-brand-950 focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                >
                    <option value="1">Activo (se aplicará en importaciones)</option>
                    <option value="0">Inactivo (ignorar en importaciones)</option>
                </select>
            </div>

            {{-- Botones de acción --}}
            <div class="flex flex-col-reverse sm:flex-row justify-end gap-2 sm:gap-3 border-t border-[#E2E8F0] pt-4">
                <button
                    type="button"
                    data-cerrar-modal-rem
                    class="w-full sm:w-auto rounded-lg border border-[#CBD5E1] bg-white px-4 py-2 text-center text-sm font-semibold text-[#64748B] hover:bg-[#F8FAFC] transition-colors"
                >
                    Cancelar
                </button>
                <button
                    type="submit"
                    class="w-full sm:w-auto rounded-lg bg-brand-950 px-5 py-2 text-center text-sm font-semibold text-white shadow-xs hover:bg-brand-800 transition-colors"
                >
                    Guardar Mapeo REM
                </button>
            </div>
        </form>
    </div>
</div>
