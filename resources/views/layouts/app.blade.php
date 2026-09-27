<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Panel') · Gestión Social</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#F4F6F5] text-[#1C2A26] antialiased">

    <div class="flex h-screen overflow-hidden">

        {{-- Sidebar --}}
        @include('layouts.partials.sidebar')

        {{-- Contenido --}}
        <div class="flex flex-1 flex-col overflow-hidden">

            {{-- Barra superior --}}
            <header class="flex h-16 shrink-0 items-center justify-between border-b border-[#E1E7E4] bg-white px-8">
                <div>
                    <h1 class="text-lg font-semibold text-brand-950">@yield('page-title', 'Inicio')</h1>
                    @hasSection('page-subtitle')
                        <p class="text-sm text-[#6B7A75]">@yield('page-subtitle')</p>
                    @endif
                </div>

                <div class="flex items-center gap-4">
                    <button class="relative text-[#6B7A75] hover:text-brand-950" title="Notificaciones">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                        </svg>
                        <span class="absolute -right-0.5 -top-0.5 h-2 w-2 rounded-full bg-accent-500"></span>
                    </button>
                </div>
            </header>

            {{-- Área de contenido con scroll propio --}}
            <main class="flex-1 overflow-y-auto px-8 py-8">
                @if (session('status'))
                    <div class="mb-6 rounded-md border border-[#BFE0D2] bg-status-ok-bg px-4 py-3 text-sm text-brand-950">
                        {{ session('status') }}
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

</body>
</html>
