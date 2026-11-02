@use('App\Models\DeisImportacion')

{{-- Estado de la última importación de cada dataset (solo Admin) --}}
<section class="rounded-xl border border-[#E2E8F0] bg-white p-5 shadow-xs" aria-labelledby="titulo-estado-importaciones">
    <div>
        <h2 id="titulo-estado-importaciones" class="text-sm font-bold text-brand-950">Estado de la última importación</h2>
        <p class="mt-0.5 text-xs text-[#64748B]">
            La actualización corre en segundo plano y requiere que el worker de colas esté activo
            (<code class="rounded bg-[#F1F5F9] px-1 py-0.5 font-mono text-[11px] text-brand-950">composer run dev</code>
            o <code class="rounded bg-[#F1F5F9] px-1 py-0.5 font-mono text-[11px] text-brand-950">php artisan queue:work</code>).
        </p>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-3">
        @foreach (DeisImportacion::DATASETS as $dataset)
            @php
                $importacion = $ultimasImportaciones->get($dataset);
                $fecha = match ($importacion?->estado) {
                    DeisImportacion::ESTADO_PENDIENTE => ['En cola desde', $importacion->created_at],
                    DeisImportacion::ESTADO_PROCESANDO => ['Iniciada el', $importacion->iniciado_en ?? $importacion->created_at],
                    null => null,
                    default => ['Finalizada el', $importacion->finalizado_en ?? $importacion->updated_at],
                };
            @endphp
            <div class="rounded-lg border border-[#E2E8F0] bg-[#F8FAFC] p-4">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-semibold uppercase tracking-wider text-[#64748B]">{{ config("deis.datasets.{$dataset}.titulo") }}</span>
                    @if ($importacion)
                        <span @class([
                            'inline-flex shrink-0 rounded-full px-2.5 py-0.5 text-[11px] font-bold',
                            'bg-[#F1F5F9] text-[#475569]' => $importacion->estado === DeisImportacion::ESTADO_PENDIENTE,
                            'bg-[#EEF5FF] text-accent-600' => $importacion->estado === DeisImportacion::ESTADO_PROCESANDO,
                            'bg-status-ok-bg text-status-ok-fg' => $importacion->estado === DeisImportacion::ESTADO_COMPLETADO,
                            'bg-status-bad-bg text-status-bad-fg' => $importacion->estado === DeisImportacion::ESTADO_FALLIDO,
                        ])>
                            {{ Str::ucfirst($importacion->estado) }}
                        </span>
                    @endif
                </div>

                @if ($importacion)
                    <p class="mt-2 text-xs text-[#475569]">
                        {{ $fecha[0] }} <strong class="text-brand-950">{{ $fecha[1]?->format('d/m/Y H:i') ?? 'sin fecha' }}</strong>
                        @if ($importacion->user)
                            · por {{ $importacion->user->name }}
                        @endif
                    </p>
                    @if ($importacion->mensaje)
                        <p @class([
                            'mt-1 text-xs line-clamp-3',
                            'text-status-bad-fg' => $importacion->estado === DeisImportacion::ESTADO_FALLIDO,
                            'text-[#64748B]' => $importacion->estado !== DeisImportacion::ESTADO_FALLIDO,
                        ]) title="{{ $importacion->mensaje }}">
                            {{ $importacion->mensaje }}
                        </p>
                    @endif
                @else
                    <p class="mt-2 text-xs text-[#94A3B8]">Sin importaciones registradas.</p>
                @endif
            </div>
        @endforeach
    </div>
</section>
