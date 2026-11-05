@if (! $datos['hayDatos'])
    @include('inteligencia-deis.partials.fuente')
    @include('inteligencia-deis.partials.sin-datos')
@else
    @php
        $kpis = $datos['kpis'];
        $filtros = $datos['filtros'];
        $nombreEstablecimiento = collect($datos['establecimientos'])->firstWhere('codigo', $filtros['establecimiento'])['nombre'] ?? 'Todos los establecimientos';
    @endphp

    {{-- Filtros --}}
    <form method="GET" action="{{ route('inteligencia-deis.index') }}" class="flex flex-wrap items-end gap-3 rounded-xl border border-[#E2E8F0] bg-white p-4 shadow-xs">
        <input type="hidden" name="tab" value="urgencias">

        <label class="block w-full sm:w-auto sm:min-w-[120px]">
            <span class="mb-1 block text-[11px] font-semibold uppercase tracking-wider text-[#64748B]">Año</span>
            <select name="anio" onchange="this.form.submit()" class="w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm font-medium text-brand-950 focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20">
                @foreach ($datos['anios'] as $anio)
                    <option value="{{ $anio }}" @selected($anio === $filtros['anio'])>{{ $anio }}</option>
                @endforeach
            </select>
        </label>

        <label class="block w-full min-w-0 sm:min-w-[260px] sm:flex-1">
            <span class="mb-1 block text-[11px] font-semibold uppercase tracking-wider text-[#64748B]">Causa DEIS</span>
            <select name="causa" onchange="this.form.submit()" class="w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm font-medium text-brand-950 focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20">
                @foreach ($datos['causas'] as $idCausa => $glosa)
                    <option value="{{ $idCausa }}" @selected($idCausa === $filtros['causa']) title="{{ $glosa }}">{{ $idCausa }} · {{ Str::limit($glosa, 90) }}</option>
                @endforeach
            </select>
        </label>

        <label class="block w-full sm:w-auto sm:min-w-[220px]">
            <span class="mb-1 block text-[11px] font-semibold uppercase tracking-wider text-[#64748B]">Establecimiento</span>
            <select name="establecimiento" onchange="this.form.submit()" class="w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm font-medium text-brand-950 focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20">
                <option value="todos" @selected($filtros['establecimiento'] === 'todos')>Todos los establecimientos</option>
                @foreach ($datos['establecimientos'] as $establecimiento)
                    <option value="{{ $establecimiento['codigo'] }}" @selected($establecimiento['codigo'] === $filtros['establecimiento'])>{{ $establecimiento['nombre'] }}</option>
                @endforeach
            </select>
        </label>

        <div class="flex w-full items-center gap-2 sm:w-auto">
            <button type="submit" class="flex-1 rounded-lg bg-brand-950 px-4 py-2 text-sm font-semibold text-white shadow-xs transition-colors hover:bg-brand-800 sm:flex-initial">
                Filtrar
            </button>
            @if ($filtros['establecimiento'] !== 'todos' || $filtros['causa'] !== App\Models\DeisAtencionUrgencia::CAUSA_TOTAL_ATENCIONES)
                <a href="{{ route('inteligencia-deis.index', ['tab' => 'urgencias', 'anio' => $filtros['anio']]) }}" class="rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-center text-sm font-medium text-[#64748B] transition-colors hover:bg-[#F8FAFC]">
                    Limpiar
                </a>
            @endif
        </div>
    </form>

    @include('inteligencia-deis.partials.fuente')

    {{-- KPIs --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @include('inteligencia-deis.partials.kpi', [
            'etiqueta' => 'Total de atenciones',
            'valor' => number_format($kpis['total'], 0, ',', '.'),
            'detalle' => "Causa {$filtros['causa']} · año {$filtros['anio']} · {$nombreEstablecimiento}",
            'icono' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
        ])
        @include('inteligencia-deis.partials.kpi', [
            'etiqueta' => 'Promedio semanal',
            'valor' => $kpis['promedio_semanal'] === null ? '—' : number_format($kpis['promedio_semanal'], 0, ',', '.'),
            'detalle' => "Atenciones por semana estadística ({$kpis['semanas']} semanas con datos)",
            'icono' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
            'tono' => 'accent',
        ])
        @include('inteligencia-deis.partials.kpi', [
            'etiqueta' => 'Semana con más atenciones',
            'valor' => $kpis['semana_maxima'] === null ? '—' : "Semana {$kpis['semana_maxima']['semana']}",
            'detalle' => $kpis['semana_maxima'] === null ? 'Sin atenciones registradas' : number_format($kpis['semana_maxima']['total'], 0, ',', '.').' atenciones en la semana',
            'icono' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6',
            'tono' => 'warn',
        ])
        @include('inteligencia-deis.partials.kpi', [
            'etiqueta' => '% respiratorio',
            'valor' => $kpis['porcentaje_respiratorio'] === null ? '—' : number_format($kpis['porcentaje_respiratorio'], 1, ',', '.').'%',
            'detalle' => 'Causas respiratorias (causa 2) sobre el total de atenciones (causa 1)',
            'icono' => 'M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z',
            'tono' => 'ok',
        ])
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- Gráfico semanal por establecimiento --}}
        <div class="rounded-xl border border-[#E2E8F0] bg-white p-5 shadow-xs lg:col-span-2">
            <div class="mb-4">
                <h3 class="text-base font-bold text-brand-950">Atenciones semanales por establecimiento</h3>
                <p class="text-xs text-[#64748B]">{{ $datos['glosaCausa'] }} · año {{ $filtros['anio'] }} · semanas estadísticas DEIS</p>
            </div>
            <div id="grafico-urgencias-semanal" class="min-h-[340px]"></div>
        </div>

        {{-- Distribución por grupo de edad --}}
        <div class="rounded-xl border border-[#E2E8F0] bg-white p-5 shadow-xs">
            <h3 class="text-base font-bold text-brand-950">Distribución por grupo de edad</h3>
            <p class="text-xs text-[#64748B]">Causa seleccionada · {{ $nombreEstablecimiento }}</p>

            <table class="mt-4 w-full text-left text-sm">
                <thead class="text-[11px] font-bold uppercase tracking-wider text-[#64748B]">
                    <tr class="border-b border-[#E2E8F0]">
                        <th scope="col" class="py-2">Grupo de edad</th>
                        <th scope="col" class="py-2 text-right">Atenciones</th>
                        <th scope="col" class="py-2 text-right">%</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#F1F5F9]">
                    @foreach ($datos['gruposEdad'] as $grupo)
                        <tr>
                            <td class="py-2.5 pr-2">
                                <span class="block text-brand-950">{{ $grupo['etiqueta'] }}</span>
                                <span class="mt-1 block h-1.5 w-full overflow-hidden rounded-full bg-[#E2E8F0]">
                                    <span class="block h-full rounded-full bg-accent-500" style="width: {{ round($grupo['porcentaje'] ?? 0, 1) }}%"></span>
                                </span>
                            </td>
                            <td class="whitespace-nowrap py-2.5 text-right font-mono text-brand-950">{{ number_format($grupo['total'], 0, ',', '.') }}</td>
                            <td class="whitespace-nowrap py-2.5 pl-2 text-right font-mono text-[#475569]">{{ $grupo['porcentaje'] === null ? '—' : number_format($grupo['porcentaje'], 1, ',', '.').'%' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    </div>

    {{-- Contraste con la red municipal --}}
    <div class="overflow-hidden rounded-xl border border-[#E2E8F0] bg-white shadow-xs">
        <div class="border-b border-[#F1F5F9] px-5 py-4">
            <h3 class="text-base font-bold text-brand-950">Contraste con la red municipal</h3>
            <p class="text-xs text-[#64748B]">Atenciones DEIS de la causa seleccionada en {{ $filtros['anio'] }} frente a la población inscrita del CESFAM municipal asociado a cada dispositivo de urgencia.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-[#E2E8F0] bg-[#F8FAFC] text-xs font-semibold uppercase tracking-wider text-[#64748B]">
                    <tr>
                        <th scope="col" class="px-5 py-3">Establecimiento DEIS</th>
                        <th scope="col" class="px-5 py-3">Tipo</th>
                        <th scope="col" class="px-5 py-3">CESFAM local</th>
                        <th scope="col" class="px-5 py-3 text-right">Población inscrita</th>
                        <th scope="col" class="px-5 py-3 text-right">Atenciones</th>
                        <th scope="col" class="px-5 py-3 text-right">Por 1.000 inscritos (aprox.)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#F1F5F9]">
                    @foreach ($datos['contraste'] as $fila)
                        <tr class="transition-colors hover:bg-[#F8FAFC]">
                            <td class="px-5 py-3">
                                <span class="block font-semibold text-brand-950">{{ $fila['nombre'] }}</span>
                                <span class="font-mono text-[11px] text-[#94A3B8]">{{ $fila['codigo'] }}</span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3">
                                <span class="inline-flex rounded-full border border-[#E2E8F0] bg-[#F8FAFC] px-2.5 py-0.5 text-xs font-semibold text-[#475569]">{{ $fila['tipo'] }}</span>
                            </td>
                            <td class="px-5 py-3">
                                @if ($fila['red_sst'])
                                    <span class="inline-flex rounded-full border border-brand-950/15 bg-brand-100 px-2.5 py-0.5 text-xs font-semibold text-brand-950">Red SST</span>
                                    <span class="mt-0.5 block text-[11px] text-[#94A3B8]">Sin par municipal</span>
                                @elseif ($fila['local'])
                                    <span class="block font-medium text-brand-950">{{ $fila['local']['nombre'] }}</span>
                                    <span class="font-mono text-[11px] text-[#94A3B8]">{{ $fila['local']['codigo_deis'] }}</span>
                                @else
                                    <span class="block text-xs font-medium text-status-warn-fg">No registrado en Establecimientos</span>
                                    <span class="font-mono text-[11px] text-[#94A3B8]">{{ $fila['codigo_local'] }}</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 text-right font-mono text-[#475569]">
                                {{ ($fila['local']['poblacion'] ?? 0) > 0 ? number_format($fila['local']['poblacion'], 0, ',', '.') : '—' }}
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 text-right font-mono font-bold text-brand-950">
                                {{ number_format($fila['total'], 0, ',', '.') }}
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 text-right font-mono font-semibold text-brand-950">
                                {{ $fila['tasa'] === null ? '—' : number_format($fila['tasa'], 1, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="border-t border-[#F1F5F9] px-5 py-3 text-[11px] text-[#94A3B8]">
            Tasa aprox., sobre población inscrita local: la población inscrita del CESFAM no es la población comunal ni toda la demanda que recibe el dispositivo de urgencia.
            «—» indica que el establecimiento no tiene par municipal o que su población inscrita no está registrada.
        </p>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const grafico = @json($datos['grafico']);
        const contenedor = document.querySelector('#grafico-urgencias-semanal');
        const formato = new Intl.NumberFormat('es-CL');

        if (!contenedor || typeof ApexCharts === 'undefined') return;

        if (grafico.series.length === 0) {
            contenedor.innerHTML = '<div class="flex h-64 items-center justify-center text-sm text-[#94A3B8]">Sin atenciones registradas para el filtro seleccionado.</div>';
            return;
        }

        new ApexCharts(contenedor, {
            series: grafico.series,
            chart: { type: 'line', height: 340, toolbar: { show: false }, zoom: { enabled: false }, fontFamily: 'inherit' },
            colors: ['#2f6fed', '#8B5CF6', '#06B6D4', '#F97316', '#0f1424', '#10B981', '#E11D2E'],
            stroke: { width: 2.5, curve: 'smooth' },
            markers: { size: 0, hover: { size: 5 } },
            dataLabels: { enabled: false },
            xaxis: {
                categories: grafico.categorias,
                tickAmount: Math.min(grafico.categorias.length, 13),
                labels: { rotate: 0, style: { colors: '#64748B' } },
            },
            yaxis: {
                title: { text: 'Atenciones' },
                labels: { formatter: (valor) => formato.format(Math.round(valor)) },
            },
            tooltip: {
                shared: true,
                intersect: false,
                y: { formatter: (valor) => valor === null || valor === undefined ? 'Sin dato' : formato.format(valor) },
            },
            grid: { borderColor: '#F1F5F9' },
            legend: { position: 'top', horizontalAlign: 'left' },
        }).render();
    });
    </script>
    @endpush
@endif
