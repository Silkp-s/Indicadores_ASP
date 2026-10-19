<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Panel') · Gestión Social</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#F5F7FB] text-[#1E293B] antialiased">

    {{-- Backdrop para móvil (cierra el menú al hacer click afuera) --}}
    <div
        id="sidebar-backdrop"
        class="fixed inset-0 z-40 bg-brand-950/60 backdrop-blur-xs opacity-0 pointer-events-none transition-opacity duration-300 lg:hidden"
        aria-hidden="true"
    ></div>

    <div class="flex h-screen overflow-hidden">

        {{-- Sidebar --}}
        @include('layouts.partials.sidebar')

        {{-- Contenido --}}
        <div class="flex flex-1 flex-col overflow-hidden min-w-0">

            {{-- Barra superior: botón móvil, búsqueda global, periodo, notificaciones y perfil --}}
            <header class="flex h-[72px] shrink-0 items-center gap-3 sm:gap-6 border-b border-[#E2E8F0] bg-white px-4 sm:px-6 lg:px-8">
                
                {{-- Botón hamburguesa móvil --}}
                <button
                    type="button"
                    id="mobile-menu-btn"
                    class="inline-flex items-center justify-center rounded-lg p-2 text-[#64748B] hover:bg-[#F1F5F9] hover:text-brand-950 focus:outline-none focus:ring-2 focus:ring-accent-500 lg:hidden"
                    aria-label="Abrir menú de navegación"
                >
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>


                <div class="ml-auto flex items-center gap-2 sm:gap-4 lg:gap-5 shrink-0">

                    <button type="button" class="hidden sm:inline-flex text-[#64748B] hover:text-brand-950" title="Mensajes" aria-label="Mensajes">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 01.778-.332 48.294 48.294 0 005.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />
                        </svg>
                    </button>

                    <button type="button" class="relative text-[#64748B] hover:text-brand-950 p-1" title="Notificaciones" aria-label="Notificaciones">
                        <svg class="h-5 w-5 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                        </svg>
                        <span class="absolute right-1 top-1 h-2 w-2 rounded-full border-2 border-white bg-status-bad-fg"></span>
                    </button>

                    <button type="button" class="flex items-center gap-2 text-[#64748B] hover:text-brand-950" title="Mi perfil" aria-label="Mi perfil">
                        <span class="flex h-8 w-8 sm:h-9 sm:w-9 items-center justify-center rounded-full bg-accent-500 text-xs sm:text-sm font-semibold text-white">
                            {{ Str::of(auth()->user()->name ?? 'U')->substr(0, 1)->upper() }}
                        </span>
                        <svg class="hidden sm:block h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>
                </div>
            </header>

            {{-- Área de contenido con scroll propio --}}
            <main class="flex-1 overflow-y-auto px-4 py-5 sm:px-6 sm:py-6 lg:px-8 lg:py-8">
                <div class="mb-6">
                    <h1 class="text-2xl sm:text-3xl lg:text-[32px] font-bold uppercase leading-tight text-brand-950">@yield('page-title', 'Inicio')</h1>
                    @hasSection('page-subtitle')
                        <p class="mt-1 text-xs sm:text-sm text-[#64748B]">@yield('page-subtitle')</p>
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

    {{-- Script de control de Sidebar Móvil --}}
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const sidebar = document.getElementById('app-sidebar');
        const backdrop = document.getElementById('sidebar-backdrop');
        const openBtn = document.getElementById('mobile-menu-btn');
        const closeBtn = document.getElementById('sidebar-close-btn');

        function openSidebar() {
            if (!sidebar || !backdrop) return;
            sidebar.classList.remove('-translate-x-full');
            sidebar.classList.add('translate-x-0');
            backdrop.classList.remove('opacity-0', 'pointer-events-none');
            backdrop.classList.add('opacity-100', 'pointer-events-auto');
            document.body.classList.add('overflow-hidden');
        }

        function closeSidebar() {
            if (!sidebar || !backdrop) return;
            sidebar.classList.add('-translate-x-full');
            sidebar.classList.remove('translate-x-0');
            backdrop.classList.add('opacity-0', 'pointer-events-none');
            backdrop.classList.remove('opacity-100', 'pointer-events-auto');
            document.body.classList.remove('overflow-hidden');
        }

        if (openBtn) openBtn.addEventListener('click', openSidebar);
        if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
        if (backdrop) backdrop.addEventListener('click', closeSidebar);

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && window.innerWidth < 1024) {
                closeSidebar();
            }
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth >= 1024) {
                closeSidebar();
            }
        });
    });
    </script>

    @stack('scripts')
</body>
</html>
