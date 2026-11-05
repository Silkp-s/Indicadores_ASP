{{-- Tarjeta KPI: $etiqueta, $valor, $detalle, $icono (trazo SVG) y $tono (brand|accent|ok|warn|bad) --}}
@php
    $tono ??= 'brand';
@endphp
<div class="rounded-xl border border-[#E2E8F0] bg-white p-5 shadow-xs">
    <div class="flex items-center justify-between gap-2">
        <span class="text-xs font-semibold uppercase tracking-wider text-[#64748B]">{{ $etiqueta }}</span>
        <span @class([
            'flex h-9 w-9 shrink-0 items-center justify-center rounded-lg shadow-xs',
            'bg-brand-100 text-brand-950' => $tono === 'brand',
            'bg-[#EEF5FF] text-accent-600' => $tono === 'accent',
            'bg-status-ok-bg text-status-ok-fg' => $tono === 'ok',
            'bg-status-warn-bg text-status-warn-fg' => $tono === 'warn',
            'bg-status-bad-bg text-status-bad-fg' => $tono === 'bad',
        ])>
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icono }}" />
            </svg>
        </span>
    </div>
    <p class="mt-3 text-3xl font-extrabold text-brand-950">{{ $valor }}</p>
    <p class="mt-1 text-xs text-[#64748B]">{{ $detalle }}</p>
</div>
