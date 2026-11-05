{{--
    Modal para Crear Usuarios desde el Panel de Administración.
    Un único rol por usuario (Admin o Usuario).
--}}
<div
    id="modal-admin-usuario"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-brand-950/50 backdrop-blur-xs p-4 overflow-y-auto"
    role="dialog"
    aria-modal="true"
    aria-labelledby="form-admin-usuario-titulo"
>
    <div class="relative w-full max-w-2xl rounded-2xl bg-white shadow-2xl border border-[#E2E8F0] my-8 overflow-hidden">

        {{-- Cabecera del modal --}}
        <div class="flex items-center justify-between border-b border-[#E2E8F0] px-6 py-4 bg-[#F8FAFC]">
            <div>
                <h2 id="form-admin-usuario-titulo" class="text-base font-bold text-brand-950">Nuevo Usuario</h2>
                <p id="form-admin-usuario-subtitulo" class="text-xs text-[#64748B]">Registre una cuenta y asígnele un único rol</p>
            </div>
            <button
                type="button"
                data-cerrar-modal-usuario
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
            id="form-admin-usuario"
            method="POST"
            action="{{ route('admin.users.store') }}"
            data-url-crear="{{ route('admin.users.store') }}"
            class="space-y-4 px-6 py-5 max-h-[calc(100vh-160px)] overflow-y-auto"
        >
            @csrf
            <input type="hidden" name="_method" value="PUT" disabled>
            <input type="hidden" name="usuario_id" value="">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Nombre --}}
                <div>
                    <label for="campo-usuario-nombre" class="block text-xs font-semibold uppercase tracking-wider text-brand-950">
                        Nombre <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="text"
                        id="campo-usuario-nombre"
                        name="name"
                        required
                        maxlength="255"
                        placeholder="Ej: Carolina"
                        class="mt-1.5 w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm text-brand-950 placeholder:text-[#94A3B8] focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                    >
                </div>

                {{-- Apellido --}}
                <div>
                    <label for="campo-usuario-apellido" class="block text-xs font-semibold uppercase tracking-wider text-brand-950">
                        Apellido <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="text"
                        id="campo-usuario-apellido"
                        name="apellido"
                        required
                        maxlength="255"
                        placeholder="Ej: Silva"
                        class="mt-1.5 w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm text-brand-950 placeholder:text-[#94A3B8] focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                    >
                </div>
            </div>

            {{-- Email --}}
            <div>
                <label for="campo-usuario-email" class="block text-xs font-semibold uppercase tracking-wider text-brand-950">
                    Correo electrónico institucional <span class="text-rose-500">*</span>
                </label>
                <input
                    type="email"
                    id="campo-usuario-email"
                    name="email"
                    required
                    maxlength="255"
                    placeholder="usuario@talcahuano.cl"
                    class="mt-1.5 w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm text-brand-950 placeholder:text-[#94A3B8] focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                >
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Contraseña --}}
                <div>
                    <label for="campo-usuario-password" class="block text-xs font-semibold uppercase tracking-wider text-brand-950">
                        Contraseña <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="password"
                        id="campo-usuario-password"
                        name="password"
                        required
                        autocomplete="new-password"
                        class="mt-1.5 w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm text-brand-950 focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                    >
                </div>

                {{-- Confirmar contraseña --}}
                <div>
                    <label for="campo-usuario-password-confirmation" class="block text-xs font-semibold uppercase tracking-wider text-brand-950">
                        Confirmar contraseña <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="password"
                        id="campo-usuario-password-confirmation"
                        name="password_confirmation"
                        required
                        autocomplete="new-password"
                        class="mt-1.5 w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm text-brand-950 focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                    >
                </div>
            </div>

            <p id="campo-usuario-password-hint" hidden class="text-[11px] text-[#64748B]">
                Deje la contraseña en blanco para mantener la actual.
            </p>

            {{-- Rol único --}}
            <div>
                <label for="campo-usuario-rol" class="block text-xs font-semibold uppercase tracking-wider text-brand-950">
                    Rol asignado <span class="text-rose-500">*</span>
                </label>
                <select
                    id="campo-usuario-rol"
                    name="role"
                    required
                    class="mt-1.5 w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm text-brand-950 focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                >
                    <option value="Usuario">Usuario — acceso operativo y de consulta</option>
                    <option value="Admin">Admin — acceso completo de administración</option>
                </select>
                <p class="mt-1 text-[11px] text-[#64748B]">Cada cuenta tiene un único rol.</p>
            </div>

            {{-- Botones de acción --}}
            <div class="flex flex-col-reverse sm:flex-row justify-end gap-2 sm:gap-3 border-t border-[#E2E8F0] pt-4">
                <button
                    type="button"
                    data-cerrar-modal-usuario
                    class="w-full sm:w-auto rounded-lg border border-[#CBD5E1] bg-white px-4 py-2 text-center text-sm font-semibold text-[#64748B] hover:bg-[#F8FAFC] transition-colors"
                >
                    Cancelar
                </button>
                <button
                    type="submit"
                    id="form-admin-usuario-submit"
                    class="w-full sm:w-auto rounded-lg bg-brand-950 px-5 py-2 text-center text-sm font-semibold text-white shadow-xs hover:bg-brand-800 transition-colors"
                >
                    Crear usuario
                </button>
            </div>
        </form>
    </div>
</div>
