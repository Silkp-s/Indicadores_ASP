{{--
    Modal de Crear / Editar Establecimiento de Salud.
    Prepara la acción (POST a store o PUT a update) y rellena los campos vía vanilla JS.
    Reabre automáticamente con errores de validación del servidor si falla el request.
--}}
@php
    $editandoId = ctype_digit((string) old('establecimiento_id')) ? (int) old('establecimiento_id') : null;
    $claseCampo = 'mt-1.5 w-full rounded-lg border border-[#D8DEE9] bg-white px-3 py-2 text-sm text-brand-950 focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20 aria-[invalid=true]:border-status-bad-fg';
@endphp

<div id="modal-establecimiento-form" data-modal data-reabrir="{{ $errors->any() && ! $errors->has('establecimiento') ? 'true' : 'false' }}" class="fixed inset-0 z-50 hidden items-center justify-center bg-brand-950/60 backdrop-blur-xs px-4" role="dialog" aria-modal="true" aria-labelledby="form-titulo">
    <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl bg-white shadow-2xl">

        <div class="flex items-center justify-between border-b border-[#E2E8F0] px-6 py-4">
            <div class="flex items-center gap-2.5">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-accent-50 text-accent-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                    </svg>
                </span>
                <h2 id="form-titulo" class="text-base font-bold text-brand-950">{{ $editandoId ? 'Editar establecimiento' : 'Nuevo establecimiento' }}</h2>
            </div>
            <button type="button" data-cerrar class="rounded-lg p-1 text-[#94A3B8] hover:bg-[#F1F5F9] hover:text-brand-950 transition-colors" aria-label="Cerrar modal">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form method="POST"
              action="{{ $editandoId ? route('establecimientos.update', $editandoId) : route('establecimientos.store') }}"
              data-url-crear="{{ route('establecimientos.store') }}"
              class="space-y-4 px-6 py-5">
            @csrf
            <input type="hidden" name="_method" value="PUT" @disabled(! $editandoId)>
            <input type="hidden" name="establecimiento_id" value="{{ $editandoId }}">

            {{-- Nombre --}}
            <div>
                <label for="campo-nombre" class="block text-xs font-semibold uppercase tracking-wider text-[#475569]">
                    Nombre del Establecimiento <span class="text-status-bad-fg">*</span>
                </label>
                <input id="campo-nombre"
                       type="text"
                       name="nombre"
                       value="{{ old('nombre') }}"
                       required
                       maxlength="255"
                       placeholder="Ej: CESFAM San Vicente"
                       class="{{ $claseCampo }}"
                       @error('nombre') aria-invalid="true" aria-describedby="error-nombre" @enderror>
                @error('nombre')
                    <p id="error-nombre" data-error class="mt-1 text-xs text-status-bad-fg">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Código DEIS --}}
                <div>
                    <label for="campo-codigo_deis" class="block text-xs font-semibold uppercase tracking-wider text-[#475569]">
                        Código DEIS (Oficial)
                    </label>
                    <input id="campo-codigo_deis"
                           type="text"
                           name="codigo_deis"
                           value="{{ old('codigo_deis') }}"
                           maxlength="20"
                           placeholder="Ej: 119302"
                           class="{{ $claseCampo }} font-mono"
                           @error('codigo_deis') aria-invalid="true" aria-describedby="error-codigo_deis" @enderror>
                    <p class="mt-1 text-[11px] text-[#94A3B8]">Identificador numérico MINSAL</p>
                    @error('codigo_deis')
                        <p id="error-codigo_deis" data-error class="mt-1 text-xs text-status-bad-fg">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Tipo de Recinto --}}
                <div>
                    <label for="campo-tipo" class="block text-xs font-semibold uppercase tracking-wider text-[#475569]">
                        Tipo de Recinto <span class="text-status-bad-fg">*</span>
                    </label>
                    <select id="campo-tipo"
                            name="tipo"
                            required
                            class="{{ $claseCampo }}"
                            @error('tipo') aria-invalid="true" aria-describedby="error-tipo" @enderror>
                        @foreach (\App\Models\Establecimiento::TIPOS as $tipo)
                            <option value="{{ $tipo }}" @selected(old('tipo') === $tipo)>{{ $tipo }}</option>
                        @endforeach
                    </select>
                    @error('tipo')
                        <p id="error-tipo" data-error class="mt-1 text-xs text-status-bad-fg">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Población Inscrita --}}
                <div>
                    <label for="campo-poblacion_inscrita" class="block text-xs font-semibold uppercase tracking-wider text-[#475569]">
                        Población Inscrita <span class="text-status-bad-fg">*</span>
                    </label>
                    <input id="campo-poblacion_inscrita"
                           type="number"
                           name="poblacion_inscrita"
                           value="{{ old('poblacion_inscrita', 0) }}"
                           min="0"
                           max="10000000"
                           required
                           placeholder="Ej: 28500"
                           class="{{ $claseCampo }}"
                           @error('poblacion_inscrita') aria-invalid="true" aria-describedby="error-poblacion_inscrita" @enderror>
                    <p class="mt-1 text-[11px] text-[#94A3B8]">Validada FONASA</p>
                    @error('poblacion_inscrita')
                        <p id="error-poblacion_inscrita" data-error class="mt-1 text-xs text-status-bad-fg">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Estado --}}
                <div>
                    <label for="campo-is_active" class="block text-xs font-semibold uppercase tracking-wider text-[#475569]">
                        Estado Operativo <span class="text-status-bad-fg">*</span>
                    </label>
                    <select id="campo-is_active"
                            name="is_active"
                            class="{{ $claseCampo }}"
                            @error('is_active') aria-invalid="true" aria-describedby="error-is_active" @enderror>
                        <option value="1" @selected(old('is_active', '1') === '1')>Activo (Habilitado)</option>
                        <option value="0" @selected(old('is_active') === '0')>Inactivo (Deshabilitado)</option>
                    </select>
                    @error('is_active')
                        <p id="error-is_active" data-error class="mt-1 text-xs text-status-bad-fg">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Botones de Acción --}}
            <div class="flex flex-col-reverse sm:flex-row justify-end gap-2 sm:gap-3 border-t border-[#E2E8F0] pt-4">
                <button type="button"
                        data-cerrar
                        class="w-full sm:w-auto rounded-lg px-4 py-2 text-center text-sm font-medium text-[#475569] hover:bg-[#F1F5F9] transition-colors">
                    Cancelar
                </button>
                <button type="submit"
                        class="w-full sm:w-auto rounded-lg bg-accent-500 px-5 py-2 text-center text-sm font-semibold text-white shadow-xs hover:bg-accent-600 transition-colors">
                    Guardar establecimiento
                </button>
            </div>
        </form>
    </div>
</div>
