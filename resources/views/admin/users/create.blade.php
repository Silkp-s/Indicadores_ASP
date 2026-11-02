@extends('layouts.app')

@section('title', 'Crear Usuario')
@section('page-title', 'Panel de Administración · Crear Usuario')
@section('page-subtitle', 'Registra un nuevo usuario y asigna sus roles en el sistema')

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

    {{-- Formulario de creación --}}
    <div class="rounded-xl border border-[#E2E8F0] bg-white p-6 sm:p-8 shadow-xs">
        <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-5">
            @csrf

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
                        value="{{ old('name') }}"
                        required
                        autofocus
                        class="mt-1.5 block w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm text-brand-950 placeholder:text-[#94A3B8] focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                        placeholder="Ej: Carolina"
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
                        value="{{ old('apellido') }}"
                        required
                        class="mt-1.5 block w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm text-brand-950 placeholder:text-[#94A3B8] focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                        placeholder="Ej: Silva"
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
                    value="{{ old('email') }}"
                    required
                    class="mt-1.5 block w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm text-brand-950 placeholder:text-[#94A3B8] focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                    placeholder="usuario@talcahuano.cl"
                >
                @error('email')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Contraseña --}}
                <div>
                    <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-brand-950">
                        Contraseña <span class="text-rose-500">*</span>
                    </label>
                    <input
                        id="password"
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

                {{-- Confirmar contraseña --}}
                <div>
                    <label for="password_confirmation" class="block text-xs font-semibold uppercase tracking-wider text-brand-950">
                        Confirmar contraseña <span class="text-rose-500">*</span>
                    </label>
                    <input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        required
                        autocomplete="new-password"
                        class="mt-1.5 block w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm text-brand-950 placeholder:text-[#94A3B8] focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                    >
                    @error('password_confirmation')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Roles --}}
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-brand-950">
                    Roles asignados <span class="text-rose-500">*</span>
                </label>
                <div class="mt-2 space-y-2">
                    @foreach (['Admin', 'Usuario'] as $roleName)
                        <label class="flex items-center gap-3 rounded-lg border border-[#CBD5E1] bg-[#F8FAFC] px-4 py-3 cursor-pointer hover:bg-white transition-colors">
                            <input
                                type="checkbox"
                                name="roles[]"
                                value="{{ $roleName }}"
                                @checked(is_array(old('roles')) && in_array($roleName, old('roles')))
                                class="h-4 w-4 rounded border-[#CBD5E1] text-brand-950 focus:ring-brand-800"
                            >
                            <div>
                                <span class="text-sm font-semibold text-brand-950">{{ $roleName }}</span>
                                <span class="text-xs text-[#64748B] block">
                                    {{ $roleName === 'Admin' ? 'Acceso completo a configuración, celdas REM, indicadores y administración' : 'Acceso operativo a dashboards, catálogo e importaciones' }}
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
                    Crear usuario
                </button>
            </div>
        </form>
    </div>

</div>
@endsection