@if (! $datos['hayDatos'])
    @include('inteligencia-deis.partials.fuente')
    @include('inteligencia-deis.partials.sin-datos')
@else
    @php
        $kpis = $datos['kpis'];
        $anioSeleccionado = $datos['filtros']['anio'];
    @endphp

    {{-- Filtros --}}
    <form method="GET" action="{{ route('inteligencia-deis.index') }}" class="flex flex-wrap items-end gap-3 rounded-xl border border-[#E2E8F0] bg-white p-4 shadow-xs">
        <input type="hidden" name="tab" value="egresos">

        <label class="block w-full sm:w-auto sm:min-w-[120px]">
            <span class="mb-1 block text-[11px] font-semibold uppercase tracking-wider text-[#64748B]">Año</span>
            <select name="anio" onchange="this.form.submit()" class="w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm font-medium text-brand-950 focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20">
                @foreach ($datos['anios'] as $anio)
                    <option value="{{ $anio }}" @selected($anio === $anioSeleccionado)>{{ $anio }}</option>
                @endforeach
            </select>
        </label>

        <button type="submit" class="w-full rounded-lg bg-brand-950 px-4 py-2 text-sm font-semibold text-white shadow-xs transition-colors hover:bg-brand-800 sm:w-auto">
            Filtrar
        </button>
    </form>

    @include('inteligencia-deis.partials.fuente')

    {{-- KPIs --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @include('inteligencia-deis.partials.kpi', [
            'etiqueta' => 'Total de egresos',
            'valor' => number_format($kpis['total'], 0, ',', '.'),
            'detalle' => 'Mujeres '.number_format($kpis['mujeres'], 0, ',', '.').' · Hombres '.number_format($kpis['hombres'], 0, ',', '.')." · año {$anioSeleccionado}",
            'icono' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
        ])
        @include('inteligencia-deis.partials.kpi', [
            'etiqueta' => 'Promedio de días de estada',
            'valor' => $kpis['promedio_estada'] === null ? '—' : number_format($kpis['promedio_estada'], 1, ',', '.'),
            'detalle' => number_format($kpis['dias_estada'], 0, ',', '.').' días de estada en total',
            'icono' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
            'tono' => 'accent',
        ])
        @include('inteligencia-deis.partials.kpi', [
            'etiqueta' => '% fallecidos',
            'valor' => $kpis['porcentaje_fallecidos'] === null ? '—' : number_format($kpis['porcentaje_fallecidos'], 1, ',', '.').'%',
            'detalle' => number_format($kpis['fallecidos'], 0, ',', '.').' egresos con condición «Fallecido»',
            'icono' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
            'tono' => 'bad',
        ])
        @include('inteligencia-deis.partials.kpi', [
            'etiqueta' => '% sector público (SNSS)',
            'valor' => $kpis['porcentaje_publico'] === null ? '—' : number_format($kpis['porcentaje_publico'], 1, ',', '.').'%',
            'detalle' => 'Público '.number_format($kpis['publico'], 0, ',', '.').' · Privado '.number_format($kpis['privado'], 0, ',', '.'),
            'icono' => 'M11 3.055A9.001 9.001 0 1020.945 13H11V3.055zM20.488 9H15V3.512A9.025 9.025 0 0120.488 9z',
            'tono' => 'ok',
        ])
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- Egresos por capítulo CIE-10 --}}
        <div class="rounded-xl border border-[#E2E8F0] bg-white p-5 shadow-xs lg:col-span-2">
            <div class="mb-4">
                <h3 class="text-base font-bold text-brand-950">Egresos por capítulo CIE-10</h3>
                <p class="text-xs text-[#64748B]">Los 10 capítulos del diagnóstico principal con más egresos en {{ $anioSeleccionado }}</p>
            </div>
            <div id="grafico-egresos-capitulos" class="min-h-[360px]"></div>
        </div>

        @include('inteligencia-deis.partials.contraste-red', [
            'casos' => $kpis['total'],
            'etiquetaCasos' => "Egresos DEIS {$anioSeleccionado}",
            'etiquetaTasa' => 'Egresos por 1.000 inscritos',
            'tasa' => $kpis['tasa'],
            'red' => $datos['red'],
        ])

        {{-- Egresos por grupo de edad --}}
        <div class="rounded-xl border border-[#E2E8F0] bg-white p-5 shadow-xs lg:col-span-2">
            <div class="mb-4">
                <h3 class="text-base font-bold text-brand-950">Egresos por grupo de edad</h3>
                <p class="text-xs text-[#64748B]">Grupos de edad DEIS · año {{ $anioSeleccionado }}</p>
            </div>
            <div id="grafico-egresos-edad" class="min-h-[300px]"></div>
        </div>

        {{-- Público vs privado --}}
        <div class="rounded-xl border border-[#E2E8F0] bg-white p-5 shadow-xs">
            <div class="mb-4">
                <h3 class="text-base font-bold text-brand-950">Sector público vs privado</h3>
                <p class="text-xs text-[#64748B]">Pertenencia del establecimiento al SNSS</p>
            </div>
            <div id="grafico-egresos-sector" class="flex min-h-[300px] items-center justify-center"></div>
        </div>

    </div>

    @include('inteligencia-deis.partials.tabla-capitulos', [
        'titulo' => 'Detalle de los principales diagnósticos',
        'descripcion' => 'Capítulo CIE-10 derivado del diagnóstico principal (DIAG1) de cada egreso.',
        'etiquetaCasos' => 'Egresos',
        'conEstada' => true,
    ])

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof ApexCharts === 'undefined') return;

        const formato = new Intl.NumberFormat('es-CL');
        const capitulos = @json($datos['graficoCapitulos']);
        const edad = @json($datos['graficoEdad']);
        const sector = @json($datos['graficoSector']);

        new ApexCharts(document.querySelector('#grafico-egresos-capitulos'), {
            series: [{ name: 'Egresos', data: capitulos.datos }],
            chart: { type: 'bar', height: 360, toolbar: { show: false }, fontFamily: 'inherit' },
            plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '70%' } },
            colors: ['#2f6fed'],
            dataLabels: { enabled: true, formatter: (valor) => formato.format(valor), style: { fontSize: '11px' } },
            xaxis: { categories: capitulos.categorias, labels: { formatter: (valor) => formato.format(valor) } },
            yaxis: { labels: { maxWidth: 260, style: { colors: '#475569' } } },
            tooltip: { y: { formatter: (valor) => formato.format(valor) + ' egresos' } },
            grid: { borderColor: '#F1F5F9' },
            responsive: [{ breakpoint: 640, options: { yaxis: { labels: { maxWidth: 130 } } } }],
        }).render();

        new ApexCharts(document.querySelector('#grafico-egresos-edad'), {
            series: [{ name: 'Egresos', data: edad.datos }],
            chart: { type: 'bar', height: 300, toolbar: { show: false }, fontFamily: 'inherit' },
            plotOptions: { bar: { borderRadius: 4, columnWidth: '60%' } },
            colors: ['#8B5CF6'],
            dataLabels: { enabled: false },
            xaxis: { categories: edad.categorias, labels: { rotate: -45, style: { colors: '#64748B', fontSize: '11px' } } },
            yaxis: { labels: { formatter: (valor) => formato.format(Math.round(valor)) } },
            tooltip: { y: { formatter: (valor) => formato.format(valor) + ' egresos' } },
            grid: { borderColor: '#F1F5F9' },
        }).render();

        const totalSector = sector.datos.reduce((suma, valor) => suma + valor, 0);

        new ApexCharts(document.querySelector('#grafico-egresos-sector'), {
            series: sector.datos,
            labels: sector.categorias,
            chart: { type: 'donut', height: 300, fontFamily: 'inherit' },
            colors: ['#2f6fed', '#0f1424', '#94A3B8'],
            legend: { position: 'bottom' },
            dataLabels: { enabled: true, formatter: (valor) => valor.toFixed(1).replace('.', ',') + '%' },
            tooltip: { y: { formatter: (valor) => formato.format(valor) + ' egresos' } },
            plotOptions: {
                pie: {
                    donut: {
                        size: '65%',
                        labels: { show: true, total: { show: true, label: 'Egresos', formatter: () => formato.format(totalSector) } },
                    },
                },
            },
        }).render();
    });
    </script>
    @endpush
@endif
