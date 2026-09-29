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
            ['route' => 'indicadores.index', 'activo' => 'indicadores.*', 'label' => 'Catálogo de Indicadores', 'icono' => [
                'M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z',
            ]],
            ['route' => 'iaaps-metas.index', 'activo' => 'iaaps-metas.*', 'label' => 'IAAPS y Metas', 'icono' => [
                'M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z',
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
        'Sistema' => [
            ['route' => 'configuracion', 'activo' => 'configuracion', 'label' => 'Configuración', 'icono' => [
                'M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28z',
                'M15 12a3 3 0 11-6 0 3 3 0 016 0z',
            ]],
        ],
    ];
@endphp

<aside class="flex h-screen w-64 shrink-0 flex-col bg-brand-950 text-[#CBD5E1]">

    {{-- Marca --}}
    <div class="flex items-center gap-2.5 px-5 py-6">
        <svg class="h-9 w-9 shrink-0" viewBox="0 0 32 32" aria-hidden="true">
            <circle cx="8.5" cy="12" r="3" class="fill-accent-500" />
            <path d="M3.5 24c0-3.3 2.2-6 5-6s5 2.7 5 6z" class="fill-accent-500" />
            <circle cx="23.5" cy="12" r="3" class="fill-[#93B4F8]" />
            <path d="M18.5 24c0-3.3 2.2-6 5-6s5 2.7 5 6z" class="fill-[#93B4F8]" />
            <circle cx="16" cy="9" r="3.6" class="fill-white" />
            <path d="M9.5 27c0-4 2.9-7.2 6.5-7.2s6.5 3.2 6.5 7.2z" class="fill-white" />
        </svg>
        <div class="flex items-center gap-2">
            <span class="text-[26px] font-bold leading-none tracking-tight text-white">DAS</span>
            <span class="whitespace-nowrap text-[10.5px] leading-tight text-[#94A3B8]">Inteligencia Sanitaria<br>Talcahuano</span>
        </div>
    </div>

    <div class="mx-6 h-px bg-[#1E293B]"></div>

    {{-- Navegación --}}
    <nav class="flex-1 overflow-y-auto px-3 py-5" aria-label="Navegación principal">
        @foreach ($secciones as $titulo => $items)
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
