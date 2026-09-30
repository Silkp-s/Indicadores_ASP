@extends('layouts.guest')

@section('title', 'Iniciar sesión')

@section('content')
<div class="flex min-h-screen">

    {{-- Panel institucional --}}
    <div class="relative hidden w-1/2 flex-col justify-between bg-brand-950 px-14 py-12 text-[#DCE7E2] lg:flex">

        {{-- Patrón geométrico sutil de fondo --}}
        <svg class="pointer-events-none absolute inset-0 h-full w-full opacity-[0.06]" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <pattern id="grid" width="42" height="42" patternUnits="userSpaceOnUse">
                    <circle cx="1" cy="1" r="1" fill="#DCE7E2" />
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#grid)" />
        </svg>

        <div class="relative flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-md bg-accent-500 text-sm font-semibold text-brand-950">
                DAS
            </div>
            <span class="text-sm font-semibold tracking-wide text-white">Gestión Social</span>
        </div>

        <div class="relative max-w-md">
            <p class="text-2xl font-medium leading-snug text-white">
                Un solo lugar para administrar planillas, beneficiarios y ayudas sociales.
            </p>
            <p class="mt-4 text-sm text-[#9FB5AC]">
                Acceso restringido a personal autorizado del Departamento de Acción Social.
            </p>
        </div>

        <p class="relative text-xs text-[#6F8981]">
            © {{ date('Y') }} Departamento de Acción Social
        </p>
    </div>

    {{-- Formulario --}}
    <div class="flex w-full items-center justify-center px-6 py-12 lg:w-1/2">
        <div class="w-full max-w-sm">

            <div class="mb-8 lg:hidden">
                <div class="flex h-10 w-10 items-center justify-center rounded-md bg-brand-950 text-sm font-semibold text-white">
                    DAS
                </div>
            </div>

            <h1 class="text-xl font-semibold text-brand-950">Inicia sesión</h1>
            <p class="mt-1 text-sm text-[#6B7A75]">Ingresa con tu cuenta institucional.</p>

            @if ($errors->any())
                <div class="mt-6 rounded-md border border-[#E7B9A8] bg-status-bad-bg px-4 py-3 text-sm text-[#8A3B21]">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}" class="mt-6 space-y-5">
                @csrf

                <div>
                    <label for="email" class="block text-sm font-medium text-[#1C2A26]">Correo electrónico</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="nombre@municipalidad.cl"
                        class="mt-1.5 block w-full rounded-md border border-[#D7DEDB] bg-white px-3 py-2.5 text-sm text-[#1C2A26] placeholder:text-[#9AA6A1] focus:border-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-800/20"
                    >
                </div>

                <div>
                    <div class="flex items-center justify-between">
                        <label for="password" class="block text-sm font-medium text-[#1C2A26]">Contraseña</label>
                        <a href="{{ route('password.request') }}" class="text-sm text-brand-800 hover:underline">
                            ¿Olvidaste tu contraseña?
                        </a>
                    </div>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                        class="mt-1.5 block w-full rounded-md border border-[#D7DEDB] bg-white px-3 py-2.5 text-sm text-[#1C2A26] placeholder:text-[#9AA6A1] focus:border-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-800/20"
                    >
                </div>

                <label class="flex items-center gap-2">
                    <input
                        type="checkbox"
                        name="remember"
                        class="h-4 w-4 rounded border-[#D7DEDB] text-brand-800 focus:ring-brand-800/30"
                    >
                    <span class="text-sm text-[#4C5B56]">Mantener sesión iniciada</span>
                </label>

                <button
                    type="submit"
                    class="w-full rounded-md bg-brand-950 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-brand-800"
                >
                    Iniciar sesión
                </button>
            </form>

            <p class="mt-8 text-center text-xs text-[#9AA6A1]">
                ¿Problemas para acceder? Contacta a soporte interno.
            </p>
        </div>
    </div>
</div>
@endsection
