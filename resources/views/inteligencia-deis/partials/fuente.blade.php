{{-- Tarjeta "Fuente": dataset DEIS, última importación completada y tablero SST relacionado --}}
<div class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-[#E2E8F0] bg-white px-5 py-4 shadow-xs">
    <div class="flex min-w-0 items-start gap-3">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-100 text-brand-950 shadow-xs">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
            </svg>
        </span>
        <div class="min-w-0">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-[#64748B]">Fuente</p>
            <p class="text-sm font-bold text-brand-950">DEIS-MINSAL · {{ $fuente['titulo'] }} · Comuna de {{ config('deis.comuna.nombre') }}</p>
            @if ($fuente['descripcion'])
                <p class="text-xs text-[#64748B]">{{ $fuente['descripcion'] }}</p>
            @endif

            @if ($fuente['importacion'])
                <p class="mt-1 break-words text-xs text-[#475569]">
                    Última importación: <strong class="text-brand-950">{{ $fuente['importacion']->finalizado_en?->format('d/m/Y H:i') ?? 'sin fecha' }}</strong>
                    · Origen:
                    @if ($fuente['origen']['url'])
                        <a href="{{ $fuente['origen']['url'] }}" target="_blank" rel="noopener noreferrer" class="font-medium text-accent-600 hover:underline">{{ $fuente['origen']['archivo'] }}</a>
                    @else
                        {{ $fuente['origen']['archivo'] }} (archivo local)
                    @endif
                </p>
            @else
                <p class="mt-1 text-xs font-semibold text-status-warn-fg">Sin importar: aún no hay una importación completada de este dataset.</p>
            @endif
        </div>
    </div>

    @if ($fuente['tablero'])
        <a
            href="{{ route('inteligencia-deis.index', ['tab' => 'tableros', 'tablero' => $fuente['tablero']['slug']]) }}#tablero-activo"
            class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg border border-[#CBD5E1] bg-white px-3.5 py-2 text-xs font-semibold text-brand-950 shadow-xs transition-colors hover:bg-[#F8FAFC] sm:w-auto"
            title="{{ $fuente['tablero']['titulo'] }}"
        >
            <span>Ver tablero del SST relacionado</span>
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
            </svg>
        </a>
    @endif
</div>
