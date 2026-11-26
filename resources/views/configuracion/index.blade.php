@extends('layouts.app')

@section('title', 'Configuración')
@section('page-title', 'Configuración')
@section('page-subtitle', 'Gestiona los datos de tu cuenta y los parámetros del sistema')

@php
    $inputClases = 'w-full rounded-lg border border-[#CBD5E1] bg-[#F8FAFC] px-3 py-2 text-sm text-brand-950 placeholder:text-[#94A3B8] focus:border-accent-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-500/20';
@endphp

@section('content')
<div class="space-y-6">

    {{-- Errores de validación --}}
    @if ($errors->any())
        <div class="flex items-start gap-3 rounded-xl border border-status-bad-fg/30 bg-status-bad-bg p-4 text-sm text-status-bad-fg shadow-xs">
            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div>
                <p class="font-bold">Revisa los siguientes inconvenientes:</p>
                <ul class="mt-1 list-inside list-disc space-y-0.5 text-xs">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- Cabecera del usuario --}}
    <div class="flex items-center gap-4 rounded-xl border border-[#E2E8F0] bg-white p-5 shadow-xs">
        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-accent-500 text-xl font-bold text-white">
            {{ Str::of(auth()->user()->name)->substr(0, 1)->upper() }}
        </div>
        <div class="min-w-0 flex-1">
            <p class="text-lg font-bold leading-tight text-brand-950">{{ auth()->user()->name }} {{ auth()->user()->apellido }}</p>
            <p class="truncate text-sm text-[#64748B]">{{ auth()->user()->email }}</p>
        </div>
        <span @class([
            'shrink-0 inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold',
            'bg-brand-100 text-brand-950' => auth()->user()->hasRole('Admin'),
            'bg-emerald-50 text-emerald-800 border border-emerald-200' => ! auth()->user()->hasRole('Admin'),
        ])>
            {{ auth()->user()->hasRole('Admin') ? 'Administrador' : 'Usuario' }}
        </span>
    </div>

    {{-- Cuenta personal --}}
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">

        {{-- Mi perfil --}}
        <div class="overflow-hidden rounded-xl border border-[#E2E8F0] bg-white shadow-xs">
            <div class="flex items-center gap-3 border-b border-[#E2E8F0] bg-[#F8FAFC] px-5 py-4">
                <span class="inline-flex rounded-lg bg-accent-500/10 p-1.5 text-accent-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                    </svg>
                </span>
                <div>
                    <h2 class="text-base font-bold text-brand-950">Mi perfil</h2>
                    <p class="text-xs text-[#64748B]">Tus datos personales.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('configuracion.perfil') }}" class="p-5">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="perfil-name" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-[#64748B]">Nombre</label>
                        <input id="perfil-name" name="name" type="text" value="{{ old('name', auth()->user()->name) }}" required maxlength="255" class="{{ $inputClases }}">
                    </div>
                    <div>
                        <label for="perfil-apellido" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-[#64748B]">Apellido</label>
                        <input id="perfil-apellido" name="apellido" type="text" value="{{ old('apellido', auth()->user()->apellido) }}" required maxlength="255" class="{{ $inputClases }}">
                    </div>
                </div>

                <div class="mt-4">
                    <label for="perfil-email" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-[#64748B]">Correo electrónico</label>
                    <input id="perfil-email" name="email" type="email" value="{{ old('email', auth()->user()->email) }}" required maxlength="255" class="{{ $inputClases }}">
                </div>

                <div class="mt-5 flex justify-end">
                    <button type="submit" class="rounded-lg bg-brand-950 px-4 py-2 text-sm font-semibold text-white shadow-xs transition-colors hover:bg-brand-800">
                        Guardar perfil
                    </button>
                </div>
            </form>
        </div>

        {{-- Cambiar contraseña --}}
        <div class="overflow-hidden rounded-xl border border-[#E2E8F0] bg-white shadow-xs">
            <div class="flex items-center gap-3 border-b border-[#E2E8F0] bg-[#F8FAFC] px-5 py-4">
                <span class="inline-flex rounded-lg bg-accent-500/10 p-1.5 text-accent-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                    </svg>
                </span>
                <div>
                    <h2 class="text-base font-bold text-brand-950">Cambiar contraseña</h2>
                    <p class="text-xs text-[#64748B]">Debes confirmar tu contraseña actual.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('configuracion.password') }}" class="p-5">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label for="pwd-actual" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-[#64748B]">Contraseña actual</label>
                        <input id="pwd-actual" name="current_password" type="password" required autocomplete="current-password" class="{{ $inputClases }}">
                    </div>
                    <div>
                        <label for="pwd-nueva" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-[#64748B]">Nueva contraseña</label>
                        <input id="pwd-nueva" name="password" type="password" required autocomplete="new-password" class="{{ $inputClases }}">
                    </div>
                    <div>
                        <label for="pwd-confirmar" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-[#64748B]">Confirmar nueva contraseña</label>
                        <input id="pwd-confirmar" name="password_confirmation" type="password" required autocomplete="new-password" class="{{ $inputClases }}">
                    </div>
                </div>

                <div class="mt-5 flex justify-end">
                    <button type="submit" class="rounded-lg bg-brand-950 px-4 py-2 text-sm font-semibold text-white shadow-xs transition-colors hover:bg-brand-800">
                        Actualizar contraseña
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Parámetros del sistema (solo administradores) --}}
    @if (auth()->user()->hasRole('Admin'))
        <div class="overflow-hidden rounded-xl border border-[#E2E8F0] bg-white shadow-xs">
            <div class="flex items-center gap-3 border-b border-[#E2E8F0] bg-[#F8FAFC] px-5 py-4">
                <span class="inline-flex rounded-lg bg-accent-500/10 p-1.5 text-accent-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </span>
                <div>
                    <h2 class="text-base font-bold text-brand-950">Parámetros del sistema</h2>
                    <p class="text-xs text-[#64748B]">Configuración global del semáforo y del período de trabajo.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('configuracion.sistema') }}" class="p-5">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="sistema-umbral" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-[#64748B]">Umbral crítico (%)</label>
                        <input id="sistema-umbral" name="umbral_critico" type="number" step="0.1" min="50" max="100" value="{{ old('umbral_critico', $umbralCritico) }}" required class="{{ $inputClases }}">
                        <p class="mt-1 text-[11px] text-[#64748B]">Porcentaje de la meta bajo el cual un indicador se considera Crítico (por defecto 85 %).</p>
                    </div>
                    <div>
                        <label for="sistema-ano" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-[#64748B]">Año de trabajo</label>
                        <input id="sistema-ano" name="ano_trabajo" type="number" min="2000" max="2100" value="{{ old('ano_trabajo', $anoTrabajo) }}" required class="{{ $inputClases }}">
                        <p class="mt-1 text-[11px] text-[#64748B]">Año por defecto que usa el sistema cuando no hay datos del período.</p>
                    </div>
                </div>

                <div class="mt-5 flex justify-end">
                    <button type="submit" class="rounded-lg bg-brand-950 px-4 py-2 text-sm font-semibold text-white shadow-xs transition-colors hover:bg-brand-800">
                        Guardar parámetros
                    </button>
                </div>
            </form>
        </div>
    @endif

</div>
@endsection
