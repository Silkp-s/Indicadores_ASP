@extends('layouts.app')

@section('title', 'Editar Usuario')
@section('page-title', 'Panel de Administración · Editar Usuario')
@section('page-subtitle', 'Modifica los datos, credenciales y roles del usuario')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    {{-- Botón Volver --}}
    <div>
        <a
            href="{{ route('admin.dashboard', ['tab' => 'usuarios']) }}"
            class="inline-flex items-center gap-2 text-xs font-semibold text-[#64748B] hover:text-brand-950 transition-colors"
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            <span>Volver a la lista de usuarios</span>
        </a>
    </div>

    {{-- Tarjeta de cabecera del usuario --}}
    <div class="rounded-xl border border-[#E2E8F0] bg-white p-6 shadow-xs">
        <div class="flex items-center gap-4">
            <div class="flex h-14 w-14 items-center justify-center rounded-full bg-brand-100 text-lg font-bold text-brand-950">
                {{ Str::upper(Str::substr($user->name, 0, 1)) }}{{ Str::upper(Str::substr($user->apellido, 0, 1)) }}
            </div>
            <div>
                <h2 class="text-base font-bold text-brand-950">{{ $user->name }} {{ $user->apellido }}</h2>
                <p class="text-xs text-[#64748B] font-mono">{{ $user->email }}</p>
                <div class="flex items-center gap-2 mt-1.5">
                    @foreach ($user->roles as $role)
                        <span @class([
                            'inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold',
                            'bg-brand-100 text-brand-950' => $role->name === 'Admin',
                            'bg-emerald-50 text-emerald-800 border border-emerald-200' => $role->name === 'Usuario',
                            'bg-slate-100 text-slate-700' => !in_array($role->name, ['Admin', 'Usuario']),
                        ])>
                            {{ $role->name }}
                        </span>
                    @endforeach
                    <span class="text-[11px] text-[#94A3B8]">Registrado el {{ $user->created_at?->format('d/m/Y') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Formulario de edición de datos y roles --}}
    <div class="rounded-xl border border-[#E2E8F0] bg-white p-6 sm:p-8 shadow-xs">
        <h3 class="text-sm font-bold text-brand-950 mb-4 border-b border-[#E2E8F0] pb-2">Datos personales y roles</h3>
        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Nombre --}}
                <div>
                    <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-brand-950">
                        Nombre <span class="text-rose-500">*</span>
                    </label>
                    <input
                        id="name"
                        type="text"
                        name="name"
                        value="{{ old('name', $user->name) }}"
                        required
                        class="mt-1.5 block w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm text-brand-950 placeholder:text-[#94A3B8] focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                    >
                    @error('name')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Apellido --}}
                <div>
                    <label for="apellido" class="block text-xs font-semibold uppercase tracking-wider text-brand-950">
                        Apellido <span class="text-rose-500">*</span>
                    </label>
                    <input
                        id="apellido"
                        type="text"
                        name="apellido"
                        value="{{ old('apellido', $user->apellido) }}"
                        required
                        class="mt-1.5 block w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm text-brand-950 placeholder:text-[#94A3B8] focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                    >
                    @error('apellido')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Email --}}
            <div>
                <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-brand-950">
                    Correo electrónico institucional <span class="text-rose-500">*</span>
                </label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email', $user->email) }}"
                    required
                    class="mt-1.5 block w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm text-brand-950 placeholder:text-[#94A3B8] focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                >
                @error('email')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Roles --}}
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-brand-950">
                    Roles asignados
                </label>
                <div class="mt-2 space-y-2">
                    @foreach (['Admin', 'Usuario'] as $roleName)
                        <label class="flex items-center gap-3 rounded-lg border border-[#CBD5E1] bg-[#F8FAFC] px-4 py-3 cursor-pointer hover:bg-white transition-colors">
                            <input
                                type="checkbox"
                                name="roles[]"
                                value="{{ $roleName }}"
                                @checked(in_array($roleName, old('roles', $user->roles->pluck('name')->toArray())))
                                class="h-4 w-4 rounded border-[#CBD5E1] text-brand-950 focus:ring-brand-800"
                            >
                            <div>
                                <span class="text-sm font-semibold text-brand-950">{{ $roleName }}</span>
                                <span class="text-xs text-[#64748B] block">
                                    {{ $roleName === 'Admin' ? 'Acceso completo a configuración, celdas REM, indicadores y administración' : 'Acceso a paneles ejecutivos, importación e información de centros' }}
                                </span>
                            </div>
                        </label>
                    @endforeach
                </div>
                @error('roles')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Botones --}}
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-[#E2E8F0]">
                <a
                    href="{{ route('admin.dashboard', ['tab' => 'usuarios']) }}"
                    class="rounded-lg border border-[#CBD5E1] bg-white px-4 py-2 text-sm font-semibold text-[#64748B] hover:bg-[#F8FAFC] transition-colors"
                >
                    Cancelar
                </a>
                <button
                    type="submit"
                    class="rounded-lg bg-brand-950 px-5 py-2 text-sm font-semibold text-white shadow-xs hover:bg-brand-800 transition-colors"
                >
                    Guardar cambios
                </button>
            </div>
        </form>
    </div>

    {{-- Restablecer contraseña --}}
    <div class="rounded-xl border border-amber-200 bg-amber-50/50 p-6 shadow-xs">
        <h3 class="text-sm font-bold text-amber-900">Restablecer contraseña</h3>
        <p class="mt-1 text-xs text-amber-800">Genera una nueva contraseña para este usuario. La clave anterior dejará de ser válida.</p>

        <form method="POST" action="{{ route('admin.users.reset-password', $user) }}" class="mt-4 space-y-4">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="new_password" class="block text-xs font-semibold uppercase tracking-wider text-brand-950">Nueva contraseña</label>
                    <input
                        id="new_password"
                        type="password"
                        name="password"
                        required
                        autocomplete="new-password"
                        class="mt-1.5 block w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm text-brand-950 placeholder:text-[#94A3B8] focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                    >
                    @error('password')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="new_password_confirmation" class="block text-xs font-semibold uppercase tracking-wider text-brand-950">Confirmar contraseña</label>
                    <input
                        id="new_password_confirmation"
                        type="password"
                        name="password_confirmation"
                        required
                        autocomplete="new-password"
                        class="mt-1.5 block w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm text-brand-950 placeholder:text-[#94A3B8] focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                    >
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <button
                    type="submit"
                    class="rounded-lg bg-amber-700 px-4 py-2 text-xs font-semibold text-white shadow-xs hover:bg-amber-800 transition-colors"
                >
                    Actualizar contraseña
                </button>
            </div>
        </form>
    </div>

</div>
@endsection