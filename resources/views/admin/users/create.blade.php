@extends('layouts.app')

@section('title', 'Crear Usuario')
@section('page-title', 'Crear Usuario')
@section('page-subtitle', 'Registra un nuevo usuario en el sistema')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    {{-- Formulario de creación --}}
    <div class="rounded-md border border-[#E1E7E4] bg-white p-6">
        <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-5">
            @csrf

            {{-- Nombre --}}
            <div>
                <label for="name" class="block text-sm font-medium text-[#1C2A26]">Nombre</label>
                <input
                    id="name"
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    required
                    autofocus
                    class="mt-1.5 block w-full rounded-md border border-[#D7DEDB] bg-white px-3 py-2.5 text-sm text-[#1C2A26] placeholder:text-[#9AA6A1] focus:border-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-800/20"
                >
                @error('name')
                    <p class="mt-1 text-sm text-status-bad-fg">{{ $message }}</p>
                @enderror
            </div>

            {{-- Apellido --}}
            <div>
                <label for="apellido" class="block text-sm font-medium text-[#1C2A26]">Apellido</label>
                <input
                    id="apellido"
                    type="text"
                    name="apellido"
                    value="{{ old('apellido') }}"
                    required
                    class="mt-1.5 block w-full rounded-md border border-[#D7DEDB] bg-white px-3 py-2.5 text-sm text-[#1C2A26] placeholder:text-[#9AA6A1] focus:border-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-800/20"
                >
                @error('apellido')
                    <p class="mt-1 text-sm text-status-bad-fg">{{ $message }}</p>
                @enderror
            </div>

            {{-- Email --}}
            <div>
                <label for="email" class="block text-sm font-medium text-[#1C2A26]">Correo electrónico</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    class="mt-1.5 block w-full rounded-md border border-[#D7DEDB] bg-white px-3 py-2.5 text-sm text-[#1C2A26] placeholder:text-[#9AA6A1] focus:border-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-800/20"
                >
                @error('email')
                    <p class="mt-1 text-sm text-status-bad-fg">{{ $message }}</p>
                @enderror
            </div>

            {{-- Contraseña --}}
            <div>
                <label for="password" class="block text-sm font-medium text-[#1C2A26]">Contraseña</label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    required
                    autocomplete="new-password"
                    class="mt-1.5 block w-full rounded-md border border-[#D7DEDB] bg-white px-3 py-2.5 text-sm text-[#1C2A26] placeholder:text-[#9AA6A1] focus:border-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-800/20"
                >
                @error('password')
                    <p class="mt-1 text-sm text-status-bad-fg">{{ $message }}</p>
                @enderror
            </div>

            {{-- Confirmar contraseña --}}
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-[#1C2A26]">Confirmar contraseña</label>
                <input
                    id="password_confirmation"
                    type="password"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                    class="mt-1.5 block w-full rounded-md border border-[#D7DEDB] bg-white px-3 py-2.5 text-sm text-[#1C2A26] placeholder:text-[#9AA6A1] focus:border-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-800/20"
                >
                @error('password_confirmation')
                    <p class="mt-1 text-sm text-status-bad-fg">{{ $message }}</p>
                @enderror
            </div>

            {{-- Roles --}}
            <div>
                <label class="block text-sm font-medium text-[#1C2A26]">Roles <span class="text-status-bad-fg">*</span></label>
                <div class="mt-2 space-y-2">
                    @foreach (['Admin', 'Usuario'] as $roleName)
                        <label class="flex items-center gap-3 rounded-md border border-[#E1E7E4] bg-white px-3 py-2.5 cursor-pointer hover:bg-[#F9FBFA] transition-colors">
                            <input
                                type="checkbox"
                                name="roles[]"
                                value="{{ $roleName }}"
                                class="h-4 w-4 rounded border-[#D7DEDB] text-brand-800 focus:ring-brand-800/30"
                            >
                            <span class="text-sm text-[#4C5B56]">{{ $roleName }}</span>
                            @if ($roleName === 'Admin')
                                <span class="text-xs text-[#9AA6A1]">Acceso total al sistema</span>
                            @else
                                <span class="text-xs text-[#9AA6A1]">Acceso a módulos operativos</span>
                            @endif
                        </label>
                    @endforeach
                </div>
                @error('roles')
                    <p class="mt-1 text-sm text-status-bad-fg">{{ $message }}</p>
                @enderror
            </div>

            {{-- Botones --}}
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-[#E1E7E4]">
                <a href="{{ route('admin.dashboard') }}"
                    class="rounded-md border border-[#D7DEDB] bg-white px-4 py-2 text-sm font-medium text-[#4C5B56] hover:bg-[#F4F6F5] transition-colors"
                >
                    Cancelar
                </a>
                <button
                    type="submit"
                    class="rounded-md bg-brand-950 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-brand-800"
                >
                    Crear usuario
                </button>
            </div>
        </form>
    </div>

</div>
@endsection