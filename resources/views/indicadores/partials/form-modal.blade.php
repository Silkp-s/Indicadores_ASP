{{--
    Modal de Crear/Editar indicador del catálogo. Los name de los campos siguen las columnas de la tabla `indicadores`.
    El script de indicadores.index lo abre vacío (Nuevo indicador) o precargado (Editar), y cambia la acción:
    POST indicadores.store para crear, PUT indicadores.update para editar.
    Si la validación falla, el servidor vuelve con old() y $errors y el modal se reabre tal como se envió.
--}}
@php
    $editandoId = ctype_digit((string) old('indicador_id')) ? (int) old('indicador_id') : null;
    $claseCampo = 'mt-1.5 w-full rounded-lg border border-[#D8DEE9] px-3 py-2 text-sm focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20 aria-[invalid=true]:border-status-bad-fg';
@endphp

<div id="modal-catalogo-form" data-modal data-reabrir="{{ $errors->any() ? 'true' : 'false' }}" class="fixed inset-0 z-50 hidden items-center justify-center bg-brand-950/50 px-4" role="dialog" aria-modal="true" aria-labelledby="form-titulo">
    <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-xl bg-white shadow-xl">

        <div class="flex items-center justify-between border-b border-[#E2E8F0] px-6 py-4">
            <h2 id="form-titulo" class="text-base font-semibold text-brand-950">{{ $editandoId ? 'Editar indicador' : 'Nuevo indicador' }}</h2>
            <button type="button" data-cerrar class="text-[#94A3B8] hover:text-brand-950" aria-label="Cerrar">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form method="POST"
              action="{{ $editandoId ? route('indicadores.update', $editandoId) : route('indicadores.store') }}"
              data-url-crear="{{ route('indicadores.store') }}"
              class="space-y-4 px-6 py-5">
            @csrf
            <input type="hidden" name="_method" value="PUT" @disabled(! $editandoId)>
            <input type="hidden" name="indicador_id" value="{{ $editandoId }}">

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="campo-codigo" class="block text-sm font-medium text-[#1E293B]">Código</label>
                    <input id="campo-codigo" type="text" name="codigo" value="{{ old('codigo') }}" required maxlength="20" placeholder="IAAPS-05" class="{{ $claseCampo }}" @error('codigo') aria-invalid="true" aria-describedby="error-codigo" @enderror>
                    @error('codigo') <p id="error-codigo" data-error class="mt-1 text-xs text-status-bad-fg">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="campo-tipo" class="block text-sm font-medium text-[#1E293B]">Tipo</label>
                    <select id="campo-tipo" name="tipo" class="{{ $claseCampo }}" @error('tipo') aria-invalid="true" aria-describedby="error-tipo" @enderror>
                        @foreach (\App\Models\Indicador::TIPOS as $tipo)
                            <option @selected(old('tipo') === $tipo)>{{ $tipo }}</option>
                        @endforeach
                    </select>
                    @error('tipo') <p id="error-tipo" data-error class="mt-1 text-xs text-status-bad-fg">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="campo-nombre" class="block text-sm font-medium text-[#1E293B]">Nombre del indicador</label>
                <input id="campo-nombre" type="text" name="nombre" value="{{ old('nombre') }}" required maxlength="255" placeholder="Ej: Cobertura efectiva de DM2 en personas de 15 años y más" class="{{ $claseCampo }}" @error('nombre') aria-invalid="true" aria-describedby="error-nombre" @enderror>
                @error('nombre') <p id="error-nombre" data-error class="mt-1 text-xs text-status-bad-fg">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="campo-numerador" class="block text-sm font-medium text-[#1E293B]">Numerador</label>
                    <textarea id="campo-numerador" name="numerador_description" rows="3" maxlength="1000" placeholder="Ej: N° de niños evaluados" class="{{ $claseCampo }}" @error('numerador_description') aria-invalid="true" aria-describedby="error-numerador_description" @enderror>{{ old('numerador_description') }}</textarea>
                    @error('numerador_description') <p id="error-numerador_description" data-error class="mt-1 text-xs text-status-bad-fg">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="campo-denominador" class="block text-sm font-medium text-[#1E293B]">Denominador</label>
                    <textarea id="campo-denominador" name="denominador_description" rows="3" maxlength="1000" placeholder="Ej: Total de inscritos" class="{{ $claseCampo }}" @error('denominador_description') aria-invalid="true" aria-describedby="error-denominador_description" @enderror>{{ old('denominador_description') }}</textarea>
                    @error('denominador_description') <p id="error-denominador_description" data-error class="mt-1 text-xs text-status-bad-fg">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div>
                    <label for="campo-meta" class="block text-sm font-medium text-[#1E293B]">Meta (%)</label>
                    <input id="campo-meta" type="number" name="target_value" value="{{ old('target_value') }}" min="0" max="100" step="0.01" required placeholder="85" class="{{ $claseCampo }}" @error('target_value') aria-invalid="true" aria-describedby="error-target_value" @enderror>
                    @error('target_value') <p id="error-target_value" data-error class="mt-1 text-xs text-status-bad-fg">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="campo-periodicidad" class="block text-sm font-medium text-[#1E293B]">Periodicidad</label>
                    <select id="campo-periodicidad" name="periodicidad" class="{{ $claseCampo }}" @error('periodicidad') aria-invalid="true" aria-describedby="error-periodicidad" @enderror>
                        @foreach (\App\Models\Indicador::PERIODICIDADES as $periodicidad)
                            <option @selected(old('periodicidad') === $periodicidad)>{{ $periodicidad }}</option>
                        @endforeach
                    </select>
                    @error('periodicidad') <p id="error-periodicidad" data-error class="mt-1 text-xs text-status-bad-fg">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="campo-fuente" class="block text-sm font-medium text-[#1E293B]">Fuente</label>
                    <select id="campo-fuente" name="fu" class="{{ $claseCampo }}" @error('fu') aria-invalid="true" aria-describedby="error-fu" @enderror>
                        @foreach (\App\Models\Indicador::FUENTES as $fuente)
                            <option @selected(old('fu') === $fuente)>{{ $fuente }}</option>
                        @endforeach
                    </select>
                    @error('fu') <p id="error-fu" data-error class="mt-1 text-xs text-status-bad-fg">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="campo-estado" class="block text-sm font-medium text-[#1E293B]">Estado</label>
                    <select id="campo-estado" name="is_active" class="{{ $claseCampo }}" @error('is_active') aria-invalid="true" aria-describedby="error-is_active" @enderror>
                        <option value="1" @selected(old('is_active', '1') === '1')>Activo</option>
                        <option value="0" @selected(old('is_active') === '0')>Inactivo</option>
                    </select>
                    @error('is_active') <p id="error-is_active" data-error class="mt-1 text-xs text-status-bad-fg">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex justify-end gap-3 border-t border-[#E2E8F0] pt-4">
                <button type="button" data-cerrar class="rounded-lg px-4 py-2 text-sm font-medium text-[#475569] hover:bg-[#F1F5F9]">
                    Cancelar
                </button>
                <button type="submit" class="rounded-lg bg-accent-500 px-4 py-2 text-sm font-semibold text-white hover:bg-accent-600">
                    Guardar indicador
                </button>
            </div>
        </form>
    </div>
</div>
