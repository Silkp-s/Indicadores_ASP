<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Panel') · Gestión Social</title>

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#F5F7FB] text-[#1E293B] antialiased">

    <div class="flex h-screen overflow-hidden">

        @include('layouts.partials.sidebar')

        <div class="flex flex-1 flex-col overflow-hidden">

            {{-- Barra superior: búsqueda global (catálogo de indicadores), periodo, mensajes, notificaciones y perfil --}}
            <header class="flex h-[72px] shrink-0 items-center gap-6 border-b border-[#E2E8F0] bg-white px-8">
                <form role="search" method="GET" action="{{ route('indicadores.index') }}" class="relative w-full max-w-xl">
                    <input type="hidden" name="estado" value="todos">
                    <svg class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-[#94A3B8]" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z" />
                    </svg>
                    <input
                        type="search"
                        name="q"
                        value="{{ request()->routeIs('indicadores.index') && is_string(request()->query('q')) ? request()->query('q') : '' }}"
                        maxlength="100"
                        placeholder="Buscar indicador por código o nombre..."
                        aria-label="Buscar indicador por código o nombre"
                        class="w-full rounded-lg border border-[#D8DEE9] bg-[#F8FAFC] py-2.5 pl-11 pr-4 text-sm placeholder:text-[#94A3B8] focus:border-accent-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                    >
                </form>

                <div class="ml-auto flex items-center gap-5">
                    <div class="relative hidden md:block">
                        <select aria-label="Periodo global" class="appearance-none rounded-lg border border-[#D8DEE9] bg-white py-2 pl-3.5 pr-10 text-sm font-medium text-[#1E293B] focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20">
                            <option>Periodo: 2026-Q3</option>
                            <option>Periodo: 2026-Q2</option>
                            <option>Periodo: 2026-Q1</option>
                        </select>
                        <svg class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#64748B]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                        </svg>
                    </div>

                    <button type="button" class="text-[#64748B] hover:text-brand-950" title="Mensajes" aria-label="Mensajes">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 01.778-.332 48.294 48.294 0 005.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />
                        </svg>
                    </button>

                    <button type="button" class="relative text-[#64748B] hover:text-brand-950" title="Notificaciones" aria-label="Notificaciones">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                        </svg>
                        <span class="absolute right-0 top-0 h-2.5 w-2.5 rounded-full border-2 border-white bg-status-bad-fg"></span>
                    </button>

                    <button type="button" class="flex items-center gap-2 text-[#64748B] hover:text-brand-950" title="Mi perfil" aria-label="Mi perfil">
                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-accent-500 text-sm font-semibold text-white">
                            {{ Str::of(auth()->user()->name ?? 'U')->substr(0, 1)->upper() }}
                        </span>
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto px-8 py-8">
                <div class="mb-6">
                    <h1 class="text-[32px] font-bold uppercase leading-tight text-brand-950">@yield('page-title', 'Inicio')</h1>
                    @hasSection('page-subtitle')
                        <p class="mt-1 text-sm text-[#64748B]">@yield('page-subtitle')</p>
                    @endif
                </div>

                @if (session('status'))
                    <div class="mb-6 rounded-md border border-[#BFE0D2] bg-status-ok-bg px-4 py-3 text-sm text-brand-950">
                        {{ session('status') }}
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
