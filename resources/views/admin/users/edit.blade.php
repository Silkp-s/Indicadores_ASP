@extends('layouts.app')

@section('title', 'Editar Usuario')
@section('page-title', 'Editar Usuario')
@section('page-subtitle', 'Modifica los datos y roles del usuario')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    {{-- Información del usuario --}}
    <div class="rounded-md border border-[#E1E7E4] bg-white p-6">
        <div class="flex items-center gap-4">
            <div class="flex h-14 w-14 items-center justify-center rounded-full bg-brand-100 text-xl font-semibold text-brand-950">
                {{ Str::upper(Str::substr($user->name, 0, 1)) }}
            </div>
            <div>
                <h2 class="text-lg font-semibold text-brand-950">{{ $user->name }}</h2>
                <p class="text-sm text-[#6B7A75]">{{ $user->email }}</p>
            </div>
        </div>
    </div>

    {{-- Formulario de edición --}}
    <div class="rounded-md border border-[#E1E7E4] bg-white p-6">
        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-5">
            @csrf
            @method('PUT')

            {{-- Nombre --}}
            <div>
                <label for="name" class="block text-sm font-medium text-[#1C2A26]">Nombre</label>
                <input
                    id="name"
                    type="text"
                    name="name"
                    value="{{ old('name', $user->name) }}"
                    required
                    class="mt-1.5 block w-full rounded-md border border-[#D7DEDB] bg-white px-3 py-2.5 text-sm text-[#1C2A26] placeholder:text-[#9AA6A1] focus:border-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-800/20"
                >
                @error('name')
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
                    value="{{ old('email', $user->email) }}"
                    required
                    class="mt-1.5 block w-full rounded-md border border-[#D7DEDB] bg-white px-3 py-2.5 text-sm text-[#1C2A26] placeholder:text-[#9AA6A1] focus:border-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-800/20"
                >
                @error('email')
                    <p class="mt-1 text-sm text-status-bad-fg">{{ $message }}</p>
                @enderror
            </div>

            {{-- Roles --}}
            <div>
                <label class="block text-sm font-medium text-[#1C2A26]">Roles</label>
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
                    Guardar cambios
                </button>
            </div>
        </form>
    </div>

    {{-- Restablecer contraseña --}}
    <div class="rounded-md border border-[#E1E7E4] bg-status-warn-bg/30 p-6">
        <h3 class="text-sm font-semibold text-status-warn-fg">Restablecer contraseña</h3>
        <p class="mt-1 text-sm text-[#6B7A75]">Genera una nueva contraseña para este usuario. La anterior dejará de funcionar.</p>

        <form method="POST" action="{{ route('admin.users.reset-password', $user) }}" class="mt-4 space-y-4">
            @csrf

            <div>
                <label for="password" class="block text-sm font-medium text-[#1C2A26]">Nueva contraseña</label>
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

            <button
                type="submit"
                class="rounded-md bg-status-warn-fg px-4 py-2 text-sm font-medium text-white hover:bg-status-warn-fg/90 transition-colors"
            >
                Restablecer contraseña
            </button>
        </form>
    </div>

</div>
@endsection