{{--
    Sidebar principal.
    Resalta el ítem actual con request()->routeIs() según el patrón de cada ítem.
    Los íconos son SVG inline (heroicons, outline) para no depender de un paquete extra.
--}}
@php
    $secciones = [
        'General' => [
            ['route' => 'centro-mando', 'activo' => 'centro-mando', 'label' => 'Centro de Mando', 'icono' => [
                'M3 12l2-2m0 0l7-7 7 7m-14 0v8a1 1 0 001 1h3m10-9l2 2m-2-2v8a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
            ]],
        ],
        'Módulos' => [

            ['route' => 'iaaps-metas.index', 'activo' => 'iaaps-metas.*', 'label' => 'IAAPS y Metas', 'icono' => [
                'M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z',
            ]],
            ['route' => 'establecimientos.index', 'activo' => 'establecimientos.*', 'label' => 'Establecimientos', 'icono' => [
                'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21',
            ]],
            ['route' => 'importacion', 'activo' => 'importacion*', 'label' => 'Importación REM', 'icono' => [
                'M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5',
            ]],
            ['route' => 'compromisos.index', 'activo' => 'compromisos.*', 'label' => 'Compromisos', 'icono' => [
                'M9 12.75L11.25 15 15 9.75M4.5 6.75A2.25 2.25 0 016.75 4.5h10.5a2.25 2.25 0 012.25 2.25v10.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 17.25V6.75z',
            ]],
            ['route' => 'inteligencia-deis.index', 'activo' => 'inteligencia-deis.*', 'label' => 'Inteligencia DEIS', 'icono' => [
                'M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.517 0c.85.493 1.509 1.333 1.509 2.316V18',
            ]],
            ['route' => 'alertas.index', 'activo' => 'alertas.*', 'label' => 'Alertas', 'icono' => [
                'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z',
            ]],
        ],

        'Administración' => [
            ['route' => 'admin.dashboard', 'activo' => 'admin.*', 'label' => 'Panel de Administración', 'icono' => [
                'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
                'M15 12a3 3 0 11-6 0 3 3 0 016 0z',
            ]],
        ],
    ];
@endphp

<aside id="app-sidebar" class="fixed inset-y-0 left-0 z-50 flex h-full w-64 shrink-0 -translate-x-full flex-col bg-brand-950 text-[#CBD5E1] transition-transform duration-300 ease-in-out lg:static lg:h-screen lg:translate-x-0 shadow-xl lg:shadow-none">

    {{-- Marca y Botón Cerrar (móvil) --}}
    <div class="flex items-center justify-between px-5 py-6">
        <div class="flex items-center gap-2.5">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-md bg-white p-1">
                <img
                    src="{{ asset('images/das-logo.png') }}"
                    alt="DAS — Dirección de Administración de Salud"
                    class="h-full w-full object-contain"
                >
            </div>
            <span class="whitespace-nowrap text-[10.5px] leading-tight text-[#94A3B8]">Inteligencia Sanitaria<br>Talcahuano</span>
        </div>

        {{-- Botón cerrar (móvil) --}}
        <button
            type="button"
            id="sidebar-close-btn"
            class="inline-flex items-center justify-center rounded-lg p-1.5 text-[#94A3B8] hover:bg-brand-900 hover:text-white focus:outline-none focus:ring-2 focus:ring-accent-500 lg:hidden"
            aria-label="Cerrar menú de navegación"
        >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <div class="mx-6 h-px bg-[#1E293B]"></div>

    {{-- Navegación --}}
    <nav class="flex-1 overflow-y-auto px-3 py-5" aria-label="Navegación principal">
        @foreach ($secciones as $titulo => $items)
            @php
                // Skip "Administración" section for non-admin users
                $esAdminSection = $titulo === 'Administración';
                $mostrarSeccion = ! $esAdminSection || (auth()->check() && auth()->user()->hasRole('Admin'));
            @endphp
            @if ($mostrarSeccion)
                <p @class(['px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-[#8394AE]', 'pt-5' => ! $loop->first])>{{ $titulo }}</p>

                <div class="space-y-1">
                    @foreach ($items as $item)
                        @php($esActivo = request()->routeIs($item['activo']))
                        <a href="{{ route($item['route']) }}"
                           @if ($esActivo) aria-current="page" @endif
                           @class([
                               'relative flex items-center gap-3 rounded-lg px-3 py-2.5 text-[15px] font-medium transition-colors',
                               'bg-brand-800 text-white before:absolute before:inset-y-2 before:left-0 before:w-1 before:rounded-full before:bg-accent-500' => $esActivo,
                               'text-[#B6C2D6] hover:bg-brand-800/60 hover:text-white' => ! $esActivo,
                           ])>
                            <svg @class(['h-5 w-5 shrink-0', 'text-accent-500' => $esActivo]) fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true">
                                @foreach ($item['icono'] as $trazo)
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $trazo }}" />
                                @endforeach
                            </svg>
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </div>
            @endif
        @endforeach
    </nav>

    {{-- Usuario / cerrar sesión --}}
    <div class="mx-6 h-px bg-[#1E293B]"></div>
    <div class="px-4 py-4">
        <div class="flex items-center gap-3 rounded-md px-2 py-2">
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-accent-500 text-xs font-semibold text-white">
                {{ Str::of(auth()->user()->name ?? 'U')->substr(0, 1)->upper() }}
            </div>
            <div class="min-w-0 flex-1 leading-tight">
                <p class="truncate text-sm font-medium text-white">{{ auth()->user()->name ?? 'Usuario' }}</p>
                <p class="truncate text-xs text-[#94A3B8]">{{ auth()->user()->email ?? '' }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-[#94A3B8] hover:text-status-bad-fg" title="Cerrar sesión" aria-label="Cerrar sesión">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0110.5 3h6a2.25 2.25 0 012.25 2.25v13.5A2.25 2.25 0 0116.5 21h-6a2.25 2.25 0 01-2.25-2.25V15m-3 0l-3-3m0 0l3-3m-3 3H15" />
                    </svg>
                </button>
            </form>
        </div>
    </div>
</aside>
