{{--
    Modal para Crear y Editar Indicadores (Metas Sanitarias e IAAPS).
--}}
<div
    id="modal-indicador-form"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-brand-950/50 backdrop-blur-xs p-4 overflow-y-auto"
    role="dialog"
    aria-modal="true"
    aria-labelledby="form-indicador-titulo"
    data-reabrir="{{ $errors->any() ? 'true' : 'false' }}"
>
    <div class="relative w-full max-w-2xl rounded-2xl bg-white shadow-2xl border border-[#E2E8F0] my-8 overflow-hidden">

        {{-- Cabecera del modal --}}
        <div class="flex items-center justify-between border-b border-[#E2E8F0] px-6 py-4 bg-[#F8FAFC]">
            <div>
                <h2 id="form-indicador-titulo" class="text-base font-bold text-brand-950">Nuevo indicador</h2>
                <p class="text-xs text-[#64748B]">Complete los datos de la Meta Sanitaria o Indicador IAAPS</p>
            </div>
            <button
                type="button"
                data-cerrar-modal
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
            id="form-indicador"
            method="POST"
            action="{{ route('iaaps-metas.store') }}"
            data-url-crear="{{ route('iaaps-metas.store') }}"
            class="space-y-4 px-6 py-5 max-h-[calc(100vh-160px)] overflow-y-auto"
        >
            @csrf
            <input type="hidden" name="_method" value="PUT" disabled>
            <input type="hidden" name="indicador_id" value="">

            {{-- Errores generales si existieran --}}
            @if ($errors->any())
                <div class="rounded-xl border border-status-bad-fg/30 bg-status-bad-bg p-3.5 text-xs text-status-bad-fg" data-error>
                    <p class="font-bold">Por favor corrige los siguientes errores:</p>
                    <ul class="list-disc list-inside mt-1 space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Código --}}
                <div>
                    <label for="campo-codigo" class="block text-xs font-semibold uppercase tracking-wider text-brand-950">
                        Código <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="text"
                        id="campo-codigo"
                        name="codigo"
                        value="{{ old('codigo') }}"
                        required
                        maxlength="20"
                        placeholder="Ej: MS-XX o IAAPS-XX"
                        class="mt-1.5 w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm text-brand-950 placeholder:text-[#94A3B8] focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                    >
                </div>

                {{-- Tipo --}}
                <div>
                    <label for="campo-tipo" class="block text-xs font-semibold uppercase tracking-wider text-brand-950">
                        Tipo de Indicador <span class="text-rose-500">*</span>
                    </label>
                    <select
                        id="campo-tipo"
                        name="tipo"
                        required
                        class="mt-1.5 w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm text-brand-950 focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                    >
                        <option value="Meta Sanitaria" @selected(old('tipo') === 'Meta Sanitaria')>Meta Sanitaria</option>
                        <option value="IAAPS" @selected(old('tipo') === 'IAAPS')>IAAPS</option>
                    </select>
                </div>
            </div>

            {{-- Nombre --}}
            <div>
                <label for="campo-nombre" class="block text-xs font-semibold uppercase tracking-wider text-brand-950">
                    Nombre del Indicador <span class="text-rose-500">*</span>
                </label>
                <input
                    type="text"
                    id="campo-nombre"
                    name="nombre"
                    value="{{ old('nombre') }}"
                    required
                    maxlength="255"
                    placeholder="Ej: Recuperación del desarrollo psicomotor en niños y niñas de 12 a 23 meses"
                    class="mt-1.5 w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm text-brand-950 placeholder:text-[#94A3B8] focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                >
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                {{-- Meta (%) --}}
                <div>
                    <label for="campo-meta" class="block text-xs font-semibold uppercase tracking-wider text-brand-950">
                        Meta (%) <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="number"
                        id="campo-meta"
                        name="target_value"
                        value="{{ old('target_value', old('meta', '85')) }}"
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
                    <label for="campo-periodicidad" class="block text-xs font-semibold uppercase tracking-wider text-brand-950">
                        Periodicidad <span class="text-rose-500">*</span>
                    </label>
                    <select
                        id="campo-periodicidad"
                        name="periodicidad"
                        required
                        class="mt-1.5 w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm text-brand-950 focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                    >
                        @foreach (['Mensual', 'Trimestral', 'Semestral', 'Anual'] as $per)
                            <option value="{{ $per }}" @selected(old('periodicidad', 'Mensual') === $per)>{{ $per }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Fuente --}}
                <div>
                    <label for="campo-fuente" class="block text-xs font-semibold uppercase tracking-wider text-brand-950">
                        Fuente <span class="text-rose-500">*</span>
                    </label>
                    <select
                        id="campo-fuente"
                        name="fu"
                        required
                        class="mt-1.5 w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm text-brand-950 focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                    >
                        @foreach (['REM-A', 'REM-P', 'DEIS', 'SIGGES'] as $fuente)
                            <option value="{{ $fuente }}" @selected(old('fu', 'REM-A') === $fuente)>{{ $fuente }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Estado --}}
            <div>
                <label for="campo-estado" class="block text-xs font-semibold uppercase tracking-wider text-brand-950">
                    Estado <span class="text-rose-500">*</span>
                </label>
                <select
                    id="campo-estado"
                    name="is_active"
                    class="mt-1.5 w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm text-brand-950 focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                >
                    <option value="1" @selected(old('is_active', '1') == '1')>Activo</option>
                    <option value="0" @selected(old('is_active') === '0')>Inactivo</option>
                </select>
            </div>

            {{-- Definición del Numerador --}}
            <div>
                <label for="campo-numerador" class="block text-xs font-semibold uppercase tracking-wider text-brand-950">
                    Definición / Fórmula del Numerador (N)
                </label>
                <textarea
                    id="campo-numerador"
                    name="numerador_description"
                    rows="2"
                    placeholder="Ej: N° de niños y niñas de 12 a 23 meses con riesgo o retraso del DSM recuperados"
                    class="mt-1.5 w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm text-brand-950 placeholder:text-[#94A3B8] focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                >{{ old('numerador_description') }}</textarea>
            </div>

            {{-- Definición del Denominador --}}
            <div>
                <label for="campo-denominador" class="block text-xs font-semibold uppercase tracking-wider text-brand-950">
                    Definición / Fórmula del Denominador (D)
                </label>
                <textarea
                    id="campo-denominador"
                    name="denominador_description"
                    rows="2"
                    placeholder="Ej: N° de niños y niñas de 12 a 23 meses con riesgo o retraso del DSM detectado"
                    class="mt-1.5 w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm text-brand-950 placeholder:text-[#94A3B8] focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                >{{ old('denominador_description') }}</textarea>
            </div>

            {{-- Botones de acción --}}
            <div class="flex flex-col-reverse sm:flex-row justify-end gap-2 sm:gap-3 border-t border-[#E2E8F0] pt-4">
                <button
                    type="button"
                    data-cerrar-modal
                    class="w-full sm:w-auto rounded-lg border border-[#CBD5E1] bg-white px-4 py-2 text-center text-sm font-semibold text-[#64748B] hover:bg-[#F8FAFC] transition-colors"
                >
                    Cancelar
                </button>
                <button
                    type="submit"
                    id="btn-guardar-indicador"
                    class="w-full sm:w-auto rounded-lg bg-brand-950 px-5 py-2 text-center text-sm font-semibold text-white shadow-xs hover:bg-brand-800 transition-colors"
                >
                    Guardar indicador
                </button>
            </div>
        </form>
    </div>
</div>
