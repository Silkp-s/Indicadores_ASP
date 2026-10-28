{{-- Contraste de una cifra comunal DEIS con la población inscrita de la red municipal: $casos, $etiquetaCasos, $etiquetaTasa, $tasa, $red --}}
<div class="flex flex-col rounded-xl border border-[#E2E8F0] bg-white p-5 shadow-xs">
    <h3 class="text-base font-bold text-brand-950">Contraste con la red municipal</h3>
    <p class="text-xs text-[#64748B]">Cifra comunal DEIS frente a la población inscrita en los establecimientos municipales activos.</p>

    <dl class="mt-4 space-y-3 text-sm">
        <div class="flex items-center justify-between gap-3 rounded-lg bg-[#F8FAFC] px-3 py-2.5">
            <dt class="text-[#475569]">{{ $etiquetaCasos }}</dt>
            <dd class="font-mono font-bold text-brand-950">{{ number_format($casos, 0, ',', '.') }}</dd>
        </div>
        <div class="flex items-center justify-between gap-3 rounded-lg bg-[#F8FAFC] px-3 py-2.5">
            <dt class="text-[#475569]">
                Población inscrita local
                <span class="block text-[11px] text-[#94A3B8]">{{ $red['establecimientos'] }} {{ $red['establecimientos'] === 1 ? 'establecimiento activo' : 'establecimientos activos' }}</span>
            </dt>
            <dd class="font-mono font-bold text-brand-950">{{ $red['poblacion'] > 0 ? number_format($red['poblacion'], 0, ',', '.') : '—' }}</dd>
        </div>
        <div class="flex items-center justify-between gap-3 rounded-lg border border-accent-500/30 bg-[#EEF5FF] px-3 py-2.5">
            <dt class="font-semibold text-brand-950">
                {{ $etiquetaTasa }}
                <span class="block text-[11px] font-normal text-[#64748B]">aprox., sobre población inscrita local</span>
            </dt>
            <dd class="font-mono text-lg font-extrabold text-accent-600">{{ $tasa === null ? '—' : number_format($tasa, 1, ',', '.') }}</dd>
        </div>
    </dl>

    <p class="mt-auto pt-4 text-[11px] text-[#94A3B8]">
        La población inscrita en la red municipal no es la población total de la comuna: use la tasa solo como referencia aproximada.
    </p>
</div>
