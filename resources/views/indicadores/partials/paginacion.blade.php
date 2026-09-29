{{--
    Enlaces de paginación con el estilo del catálogo. Se usa con $paginator->links('indicadores.partials.paginacion');
    Laravel entrega $paginator y $elements (páginas y los "..." intermedios).
--}}
@if ($paginator->hasPages())
    <nav class="flex items-center gap-1" aria-label="Paginación">
        @if ($paginator->onFirstPage())
            <span class="cursor-not-allowed rounded-lg px-3 py-1.5 opacity-40" aria-disabled="true">Anterior</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="rounded-lg px-3 py-1.5 hover:bg-[#F1F5F9]">Anterior</a>
        @endif

        @foreach ($elements as $elemento)
            @if (is_string($elemento))
                <span class="px-2" aria-hidden="true">{{ $elemento }}</span>
            @endif

            @if (is_array($elemento))
                @foreach ($elemento as $pagina => $url)
                    @if ($pagina === $paginator->currentPage())
                        <span class="rounded-lg bg-accent-500 px-3 py-1.5 font-medium text-white" aria-current="page">{{ $pagina }}</span>
                    @else
                        <a href="{{ $url }}" class="rounded-lg px-3 py-1.5 hover:bg-[#F1F5F9]" aria-label="Ir a la página {{ $pagina }}">{{ $pagina }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="rounded-lg px-3 py-1.5 hover:bg-[#F1F5F9]">Siguiente</a>
        @else
            <span class="cursor-not-allowed rounded-lg px-3 py-1.5 opacity-40" aria-disabled="true">Siguiente</span>
        @endif
    </nav>
@endif
