@php
    $activo = $datos['activo'];
@endphp

{{-- Tablero Power BI activo --}}
<section id="tablero-activo" class="scroll-mt-6 rounded-xl border border-[#E2E8F0] bg-white p-4 shadow-xs sm:p-5" aria-labelledby="titulo-tablero-activo">
    <div class="flex flex-wrap items-start justify-between gap-3 border-b border-[#F1F5F9] pb-4">
        <div class="min-w-0">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-[#64748B]">Servicio de Salud Talcahuano · Power BI</span>
            <h2 id="titulo-tablero-activo" class="text-lg font-bold text-brand-950">{{ $activo['titulo'] }}</h2>
            <p class="text-xs text-[#64748B]">{{ $activo['descripcion'] }}</p>
        </div>
        <a
            href="{{ $activo['url'] }}"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg border border-accent-600/30 bg-[#EEF5FF] px-3.5 py-2 text-sm font-semibold text-accent-600 transition-colors hover:bg-[#DCE8FD] sm:w-auto"
        >
            <span>Abrir en Power BI</span>
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
            </svg>
        </a>
    </div>

    <div class="mt-4 overflow-hidden rounded-lg border border-[#E2E8F0] bg-[#F8FAFC]">
        <iframe
            src="{{ $activo['url'] }}"
            title="Tablero Power BI: {{ $activo['titulo'] }}"
            loading="lazy"
            allowfullscreen
            class="block h-[70vh] min-h-[420px] w-full border-0"
        ></iframe>
    </div>

    <p class="mt-3 text-[11px] text-[#94A3B8]">
        Tablero público publicado por el Servicio de Salud Talcahuano en sstalcahuano.cl/datos-de-salud. Si no carga, use «Abrir en Power BI».
    </p>
</section>

{{-- Tableros disponibles --}}
<section aria-labelledby="titulo-tableros-disponibles">
    <h2 id="titulo-tableros-disponibles" class="mb-3 text-base font-bold text-brand-950">Tableros disponibles del SST</h2>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($datos['tableros'] as $tablero)
            @php
                $esActivo = $tablero['slug'] === $activo['slug'];
            @endphp
            <a
                href="{{ route('inteligencia-deis.index', ['tab' => 'tableros', 'tablero' => $tablero['slug']]) }}#tablero-activo"
                @if ($esActivo) aria-current="true" @endif
                @class([
                    'group flex flex-col rounded-xl border p-4 shadow-xs transition-all',
                    'border-accent-500 bg-[#EEF5FF] ring-2 ring-accent-500/20' => $esActivo,
                    'border-[#E2E8F0] bg-white hover:border-accent-500/60 hover:bg-[#F8FAFC]' => ! $esActivo,
                ])
            >
                <div class="flex items-center justify-between gap-2">
                    <span @class([
                        'flex h-8 w-8 shrink-0 items-center justify-center rounded-lg',
                        'bg-accent-500 text-white' => $esActivo,
                        'bg-brand-100 text-brand-950' => ! $esActivo,
                    ])>
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </span>
                    @if ($esActivo)
                        <span class="rounded-full bg-accent-500 px-2 py-0.5 text-[10px] font-bold uppercase text-white">En pantalla</span>
                    @endif
                </div>
                <h3 class="mt-3 text-sm font-bold text-brand-950">{{ $tablero['titulo'] }}</h3>
                <p class="mt-1 text-xs text-[#64748B]">{{ $tablero['descripcion'] }}</p>
            </a>
        @endforeach
    </div>
</section>
