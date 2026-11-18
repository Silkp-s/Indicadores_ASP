@if ($paginator->hasPages())
    @php
        $currentPage = $paginator->currentPage();
        $lastPage = $paginator->lastPage();
    @endphp
    <nav role="navigation" aria-label="Paginación" class="flex items-center gap-1">
        {{-- Anterior (flecha) --}}
        @if ($paginator->onFirstPage())
            <span class="pointer-events-none inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#94A3B8]" aria-hidden="true" aria-disabled="true">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-[#E2E8F0] text-brand-950 transition-colors hover:bg-[#F1F5F9]" aria-label="Página anterior">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
            </a>
        @endif

        {{-- Página anterior --}}
        @if ($currentPage > 1)
            <a href="{{ $paginator->url($currentPage - 1) }}" class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg border border-[#E2E8F0] px-2 text-sm text-brand-950 transition-colors hover:bg-[#F1F5F9]">{{ $currentPage - 1 }}</a>
        @endif

        {{-- Página actual (editable: escriba el número y presione Enter) --}}
        <form method="GET" action="{{ request()->url() }}" class="inline-flex items-center" aria-label="Ir a una página">
            @foreach (request()->query() as $clave => $valor)
                @if ($clave !== $paginator->getPageName() && is_scalar($valor))
                    <input type="hidden" name="{{ $clave }}" value="{{ $valor }}">
                @endif
            @endforeach
            <input
                type="number"
                name="{{ $paginator->getPageName() }}"
                value="{{ $currentPage }}"
                min="1"
                max="{{ $lastPage }}"
                inputmode="numeric"
                aria-label="Ir al número de página"
                title="Escriba el número de página y presione Enter"
                class="h-8 w-12 appearance-none rounded-lg bg-brand-950 px-2 text-center text-sm font-semibold text-white outline-none [appearance:textfield] focus:ring-2 focus:ring-accent-500 [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
            >
        </form>

        {{-- Página siguiente --}}
        @if ($currentPage < $lastPage)
            <a href="{{ $paginator->url($currentPage + 1) }}" class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg border border-[#E2E8F0] px-2 text-sm text-brand-950 transition-colors hover:bg-[#F1F5F9]">{{ $currentPage + 1 }}</a>
        @endif

        {{-- Siguiente (flecha) --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-[#E2E8F0] text-brand-950 transition-colors hover:bg-[#F1F5F9]" aria-label="Página siguiente">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
            </a>
        @else
            <span class="pointer-events-none inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#94A3B8]" aria-hidden="true" aria-disabled="true">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
            </span>
        @endif
    </nav>
@endif
