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
        <input type="hidden" name="tab" value="defunciones">

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

        @if ($datos['preliminar'])
            <span class="inline-flex items-center gap-1.5 rounded-lg border border-[#FED7AA] bg-status-warn-bg px-3 py-2 text-xs font-semibold text-[#B45309]">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                Cifras preliminares {{ $anioSeleccionado }}: el DEIS las actualiza cada semana y pueden cambiar.
            </span>
        @endif
    </form>

    @include('inteligencia-deis.partials.fuente')

    {{-- KPIs --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @include('inteligencia-deis.partials.kpi', [
            'etiqueta' => 'Total de defunciones',
            'valor' => number_format($kpis['total'], 0, ',', '.'),
            'detalle' => "Residentes de la comuna · año {$anioSeleccionado}".($datos['ultimoMes'] < 12 ? " (enero a {$datos['ultimoMesNombre']})" : ''),
            'icono' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
        ])
        @include('inteligencia-deis.partials.kpi', [
            'etiqueta' => 'Mujeres / Hombres',
            'valor' => number_format($kpis['mujeres'], 0, ',', '.').' / '.number_format($kpis['hombres'], 0, ',', '.'),
            'detalle' => 'Defunciones según sexo registrado',
            'icono' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
            'tono' => 'accent',
        ])
        @include('inteligencia-deis.partials.kpi', [
            'etiqueta' => '65 años y más',
            'valor' => $kpis['porcentaje_mayores_65'] === null ? '—' : number_format($kpis['porcentaje_mayores_65'], 1, ',', '.').'%',
            'detalle' => number_format($kpis['mayores_65'], 0, ',', '.').' defunciones de personas de 65 años o más',
            'icono' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
            'tono' => 'warn',
        ])
        @include('inteligencia-deis.partials.kpi', [
            'etiqueta' => 'Tasa por 1.000 inscritos',
            'valor' => $kpis['tasa'] === null ? '—' : number_format($kpis['tasa'], 1, ',', '.'),
            'detalle' => 'Aprox., sobre población inscrita local',
            'icono' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6',
            'tono' => 'bad',
        ])
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- Principales causas por capítulo CIE-10 --}}
        <div class="rounded-xl border border-[#E2E8F0] bg-white p-5 shadow-xs lg:col-span-2">
            <div class="mb-4">
                <h3 class="text-base font-bold text-brand-950">Principales causas de muerte por capítulo CIE-10</h3>
                <p class="text-xs text-[#64748B]">Los 10 capítulos con más defunciones en {{ $anioSeleccionado }} (causa básica de muerte)</p>
            </div>
            <div id="grafico-defunciones-capitulos" class="min-h-[360px]"></div>
        </div>

        @include('inteligencia-deis.partials.contraste-red', [
            'casos' => $kpis['total'],
            'etiquetaCasos' => "Defunciones DEIS {$anioSeleccionado}",
            'etiquetaTasa' => 'Defunciones por 1.000 inscritos',
            'tasa' => $kpis['tasa'],
            'red' => $datos['red'],
        ])

        {{-- Defunciones por mes --}}
        <div class="rounded-xl border border-[#E2E8F0] bg-white p-5 shadow-xs lg:col-span-2">
            <div class="mb-4">
                <h3 class="text-base font-bold text-brand-950">Defunciones por mes</h3>
                <p class="text-xs text-[#64748B]">Mes de la fecha de defunción · año {{ $anioSeleccionado }}</p>
            </div>
            <div id="grafico-defunciones-meses" class="min-h-[300px]"></div>
        </div>

        {{-- Defunciones por grupo de edad --}}
        <div class="rounded-xl border border-[#E2E8F0] bg-white p-5 shadow-xs">
            <div class="mb-4">
                <h3 class="text-base font-bold text-brand-950">Por grupo de edad</h3>
                <p class="text-xs text-[#64748B]">Edad al fallecer · año {{ $anioSeleccionado }}</p>
            </div>
            <div id="grafico-defunciones-edad" class="min-h-[300px]"></div>
        </div>

    </div>

    @include('inteligencia-deis.partials.tabla-capitulos', [
        'titulo' => 'Detalle de las principales causas',
        'descripcion' => 'Capítulos CIE-10 tal como los publica la base de defunciones del DEIS.',
        'etiquetaCasos' => 'Defunciones',
        'conEstada' => false,
    ])

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof ApexCharts === 'undefined') return;

        const formato = new Intl.NumberFormat('es-CL');
        const capitulos = @json($datos['graficoCapitulos']);
        const meses = @json($datos['graficoMeses']);
        const edad = @json($datos['graficoEdad']);

        new ApexCharts(document.querySelector('#grafico-defunciones-capitulos'), {
            series: [{ name: 'Defunciones', data: capitulos.datos }],
            chart: { type: 'bar', height: 360, toolbar: { show: false }, fontFamily: 'inherit' },
            plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '70%' } },
            colors: ['#0f1424'],
            dataLabels: { enabled: true, formatter: (valor) => formato.format(valor), style: { fontSize: '11px' } },
            xaxis: { categories: capitulos.categorias, labels: { formatter: (valor) => formato.format(valor) } },
            yaxis: { labels: { maxWidth: 260, style: { colors: '#475569' } } },
            tooltip: { y: { formatter: (valor) => formato.format(valor) + ' defunciones' } },
            grid: { borderColor: '#F1F5F9' },
            responsive: [{ breakpoint: 640, options: { yaxis: { labels: { maxWidth: 130 } } } }],
        }).render();

        new ApexCharts(document.querySelector('#grafico-defunciones-meses'), {
            series: [{ name: 'Defunciones', data: meses.datos }],
            chart: { type: 'line', height: 300, toolbar: { show: false }, zoom: { enabled: false }, fontFamily: 'inherit' },
            colors: ['#E11D2E'],
            stroke: { width: 3, curve: 'smooth' },
            markers: { size: 4 },
            dataLabels: { enabled: false },
            xaxis: { categories: meses.categorias, labels: { style: { colors: '#64748B' } } },
            yaxis: { min: 0, labels: { formatter: (valor) => formato.format(Math.round(valor)) } },
            tooltip: { y: { formatter: (valor) => valor === null || valor === undefined ? 'Sin datos aún' : formato.format(valor) + ' defunciones' } },
            grid: { borderColor: '#F1F5F9' },
        }).render();

        new ApexCharts(document.querySelector('#grafico-defunciones-edad'), {
            series: [{ name: 'Defunciones', data: edad.datos }],
            chart: { type: 'bar', height: 300, toolbar: { show: false }, fontFamily: 'inherit' },
            plotOptions: { bar: { borderRadius: 4, columnWidth: '60%' } },
            colors: ['#2f6fed'],
            dataLabels: { enabled: false },
            xaxis: { categories: edad.categorias, labels: { rotate: -45, style: { colors: '#64748B', fontSize: '11px' } } },
            yaxis: { labels: { formatter: (valor) => formato.format(Math.round(valor)) } },
            tooltip: { y: { formatter: (valor) => formato.format(valor) + ' defunciones' } },
            grid: { borderColor: '#F1F5F9' },
        }).render();
    });
    </script>
    @endpush
@endif
