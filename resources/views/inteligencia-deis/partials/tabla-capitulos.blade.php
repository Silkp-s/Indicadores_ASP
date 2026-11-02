{{-- Tabla de los capítulos CIE-10 con más casos: $titulo, $descripcion, $etiquetaCasos y $conEstada (columnas de egresos) --}}
<div class="overflow-hidden rounded-xl border border-[#E2E8F0] bg-white shadow-xs">
    <div class="border-b border-[#F1F5F9] px-5 py-4">
        <h3 class="text-base font-bold text-brand-950">{{ $titulo }}</h3>
        <p class="text-xs text-[#64748B]">{{ $descripcion }}</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-[#E2E8F0] bg-[#F8FAFC] text-xs font-semibold uppercase tracking-wider text-[#64748B]">
                <tr>
                    <th scope="col" class="px-5 py-3">Capítulo CIE-10</th>
                    <th scope="col" class="px-5 py-3 text-right">{{ $etiquetaCasos }}</th>
                    <th scope="col" class="px-5 py-3 text-right">% del total</th>
                    @if ($conEstada)
                        <th scope="col" class="px-5 py-3 text-right">Días de estada promedio</th>
                        <th scope="col" class="px-5 py-3 text-right">Fallecidos</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-[#F1F5F9]">
                @foreach ($datos['capitulos'] as $capitulo)
                    <tr class="transition-colors hover:bg-[#F8FAFC]">
                        <td class="px-5 py-3">
                            <span class="inline-flex rounded-md border border-brand-950/15 bg-brand-100 px-2 py-0.5 font-mono text-[11px] font-bold text-brand-950">{{ $capitulo['codigo'] }}</span>
                            <span class="mt-1 block text-brand-950">{{ $capitulo['glosa'] }}</span>
                        </td>
                        <td class="whitespace-nowrap px-5 py-3 text-right font-mono font-bold text-brand-950">{{ number_format($capitulo['total'], 0, ',', '.') }}</td>
                        <td class="whitespace-nowrap px-5 py-3 text-right font-mono text-[#475569]">{{ $capitulo['porcentaje'] === null ? '—' : number_format($capitulo['porcentaje'], 1, ',', '.').'%' }}</td>
                        @if ($conEstada)
                            <td class="whitespace-nowrap px-5 py-3 text-right font-mono text-[#475569]">{{ $capitulo['promedio_estada'] === null ? '—' : number_format($capitulo['promedio_estada'], 1, ',', '.') }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-right font-mono text-[#475569]">{{ number_format($capitulo['fallecidos'], 0, ',', '.') }}</td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
