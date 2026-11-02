{{-- Estado vacío de un dataset DEIS sin datos importados --}}
<div class="rounded-xl border border-dashed border-[#CBD5E1] bg-white px-6 py-12 text-center shadow-xs">
    <svg class="mx-auto h-10 w-10 text-[#CBD5E1]" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
    </svg>
    <p class="mt-3 font-semibold text-brand-950">Aún no hay datos de {{ Str::lower($fuente['titulo']) }} importados</p>
    <p class="mx-auto mt-1 max-w-xl text-sm text-[#64748B]">
        Para cargarlos, ejecute
        <code class="rounded bg-[#F1F5F9] px-1.5 py-0.5 font-mono text-xs text-brand-950">php artisan deis:importar {{ $fuente['dataset'] }}</code>
        en el servidor o pida a un administrador que presione «Actualizar datos DEIS» en esta página.
    </p>
</div>
