{{--
    Modal para editar una Regla de Alerta (severidad, descripción y umbrales configurables).
--}}
<div
    id="modal-admin-regla"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-brand-950/50 backdrop-blur-xs p-4 overflow-y-auto"
    role="dialog"
    aria-modal="true"
    aria-labelledby="form-admin-regla-titulo"
>
    <div class="relative w-full max-w-xl rounded-2xl bg-white shadow-2xl border border-[#E2E8F0] my-8 overflow-hidden">

        {{-- Cabecera del modal --}}
        <div class="flex items-center justify-between border-b border-[#E2E8F0] px-6 py-4 bg-[#F8FAFC]">
            <div>
                <h2 id="form-admin-regla-titulo" class="text-base font-bold text-brand-950">Editar regla de alerta</h2>
                <p id="form-admin-regla-subtitulo" class="text-xs text-[#64748B]">Configure la severidad, la descripción y los umbrales de la regla</p>
            </div>
            <button
                type="button"
                data-cerrar-modal-regla
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
            id="form-admin-regla"
            method="POST"
            action=""
            class="space-y-4 px-6 py-5 max-h-[calc(100vh-160px)] overflow-y-auto"
        >
            @csrf
            <input type="hidden" name="_method" value="PUT" disabled>
            <input type="hidden" name="regla_id" value="">

            {{-- Código (solo lectura) --}}
            <div>
                <label for="campo-regla-codigo" class="block text-xs font-semibold uppercase tracking-wider text-brand-950">
                    Código de regla
                </label>
                <input
                    type="text"
                    id="campo-regla-codigo"
                    disabled
                    class="mt-1.5 w-full rounded-lg border border-[#CBD5E1] bg-[#F1F5F9] px-3 py-2 text-sm font-mono font-bold text-brand-950"
                >
            </div>

            {{-- Severidad --}}
            <div>
                <label for="campo-regla-severidad" class="block text-xs font-semibold uppercase tracking-wider text-brand-950">
                    Severidad <span class="text-rose-500">*</span>
                </label>
                <select
                    id="campo-regla-severidad"
                    name="severidad"
                    required
                    class="mt-1.5 w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm text-brand-950 focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                >
                    @foreach (\App\Models\AlertaRegla::SEVERIDADES as $sev)
                        <option value="{{ $sev }}">{{ $sev }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Descripción --}}
            <div>
                <label for="campo-regla-descripcion" class="block text-xs font-semibold uppercase tracking-wider text-brand-950">
                    Descripción
                </label>
                <textarea
                    id="campo-regla-descripcion"
                    name="descripcion"
                    rows="3"
                    maxlength="1000"
                    class="mt-1.5 w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm text-brand-950 focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                ></textarea>
            </div>

            {{-- Umbrales configurables --}}
            <div class="rounded-xl border border-[#E2E8F0] bg-[#F8FAFC] p-4 space-y-3">
                <p class="text-xs font-semibold uppercase tracking-wider text-[#64748B]">Umbrales configurables</p>

                <div data-param-container="umbral_cercano" class="hidden">
                    <label for="campo-regla-umbral-cercano" class="block text-xs font-medium text-brand-950">Umbral de cercanía (%)</label>
                    <input
                        type="number"
                        id="campo-regla-umbral-cercano"
                        name="parametros[umbral_cercano]"
                        step="0.1" min="0" max="100"
                        class="mt-1 w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm text-brand-950 focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                    >
                </div>

                <div data-param-container="meses" class="hidden">
                    <label for="campo-regla-meses" class="block text-xs font-medium text-brand-950">Meses consecutivos a la baja</label>
                    <input
                        type="number"
                        id="campo-regla-meses"
                        name="parametros[meses]"
                        step="1" min="2"
                        class="mt-1 w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm text-brand-950 focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                    >
                </div>

                <div data-param-container="min_puntos" class="hidden">
                    <label for="campo-regla-min-puntos" class="block text-xs font-medium text-brand-950">Meses mínimos de historia</label>
                    <input
                        type="number"
                        id="campo-regla-min-puntos"
                        name="parametros[min_puntos]"
                        step="1" min="1"
                        class="mt-1 w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm text-brand-950 focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                    >
                </div>

                <div data-param-container="caida" class="hidden">
                    <label for="campo-regla-caida" class="block text-xs font-medium text-brand-950">Caída mínima (puntos)</label>
                    <input
                        type="number"
                        id="campo-regla-caida"
                        name="parametros[caida]"
                        step="0.1" min="0"
                        class="mt-1 w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm text-brand-950 focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                    >
                </div>

                <div data-param-container="criticos" class="hidden">
                    <label for="campo-regla-criticos" class="block text-xs font-medium text-brand-950">Cantidad de indicadores críticos</label>
                    <input
                        type="number"
                        id="campo-regla-criticos"
                        name="parametros[criticos]"
                        step="1" min="1"
                        class="mt-1 w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm text-brand-950 focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                    >
                </div>
            </div>

            {{-- Botones de acción --}}
            <div class="flex flex-col-reverse sm:flex-row justify-end gap-2 sm:gap-3 border-t border-[#E2E8F0] pt-4">
                <button
                    type="button"
                    data-cerrar-modal-regla
                    class="w-full sm:w-auto rounded-lg border border-[#CBD5E1] bg-white px-4 py-2 text-center text-sm font-semibold text-[#64748B] hover:bg-[#F8FAFC] transition-colors"
                >
                    Cancelar
                </button>
                <button
                    type="submit"
                    class="w-full sm:w-auto rounded-lg bg-brand-950 px-5 py-2 text-center text-sm font-semibold text-white shadow-xs hover:bg-brand-800 transition-colors"
                >
                    Guardar cambios
                </button>
            </div>
        </form>
    </div>
</div>
