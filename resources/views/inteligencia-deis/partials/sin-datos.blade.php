{{-- Estado vacío de un dataset DEIS sin datos: se cargan automáticamente en segundo plano --}}
<div class="rounded-xl border border-dashed border-[#CBD5E1] bg-white px-6 py-12 text-center shadow-xs" role="status">
    @if ($cargando ?? false)
        <svg class="mx-auto h-10 w-10 animate-spin text-accent-500" fill="none" viewBox="0 0 24 24" aria-hidden="true">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"></path>
        </svg>
        <p class="mt-3 font-semibold text-brand-950">Estamos cargando los datos del DEIS.</p>
        <p class="mx-auto mt-1 max-w-xl text-sm text-[#64748B]">
            Los datos de {{ Str::lower($fuente['titulo']) }} se están obteniendo automáticamente. Vuelva a revisar en unos minutos.
        </p>
    @else
        <svg class="mx-auto h-10 w-10 text-[#CBD5E1]" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
        </svg>
        <p class="mt-3 font-semibold text-brand-950">Aún no hay datos de {{ Str::lower($fuente['titulo']) }}</p>
        <p class="mx-auto mt-1 max-w-xl text-sm text-[#64748B]">
            Los datos se cargarán automáticamente en la próxima actualización. No es necesario hacer nada.
        </p>
    @endif
</div>
