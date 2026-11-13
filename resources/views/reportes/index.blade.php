@extends('layouts.app')

@section('title', 'Reportes')
@section('page-title', 'Reportes')
@section('page-subtitle', 'Exportación de reportes mensuales, trimestrales y de alertas en Excel y PDF')

@section('content')
<div class="space-y-6">

    {{-- Formulario de generación --}}
    <form method="GET" action="{{ route('reportes.exportar') }}" class="rounded-xl border border-[#E2E8F0] bg-white p-5 shadow-xs">

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">

            {{-- Tipo de reporte --}}
            <div>
                <label for="tipo" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-[#64748B]">Tipo de reporte</label>
                <select id="tipo" name="tipo" class="w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm font-medium text-brand-950 focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20">
                    <option value="mensual">Mensual de cumplimiento</option>
                    <option value="trimestral">Trimestral con proyección</option>
                    <option value="alertas">Bandeja de alertas</option>
                </select>
            </div>

            {{-- Año --}}
            <div>
                <label for="ano" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-[#64748B]">Año</label>
                <select id="ano" name="ano" class="w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm font-medium text-brand-950 focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20">
                    @for ($y = 2024; $y <= (int) now()->year + 1; $y++)
                        <option value="{{ $y }}" @selected($y === $ano)>{{ $y }}</option>
                    @endfor
                </select>
            </div>

            {{-- Mes (mensual / alertas) --}}
            <div id="campo-mes">
                <label for="mes" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-[#64748B]">Mes</label>
                <select id="mes" name="mes" class="w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm font-medium text-brand-950 focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20">
                    @foreach (['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'] as $i => $nombre)
                        <option value="{{ $i + 1 }}" @selected($i + 1 === $mes)>{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Trimestre (trimestral) --}}
            <div id="campo-trimestre" class="hidden">
                <label for="trimestre" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-[#64748B]">Trimestre</label>
                <select id="trimestre" name="trimestre" class="w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm font-medium text-brand-950 focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20">
                    <option value="1">T1 (Ene–Mar)</option>
                    <option value="2">T2 (Abr–Jun)</option>
                    <option value="3">T3 (Jul–Sep)</option>
                    <option value="4">T4 (Oct–Dic)</option>
                </select>
            </div>

            {{-- Establecimiento --}}
            <div>
                <label for="establecimiento_id" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-[#64748B]">Establecimiento</label>
                <select id="establecimiento_id" name="establecimiento_id" class="w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm font-medium text-brand-950 focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20">
                    <option value="">Todos los establecimientos</option>
                    @foreach ($establecimientos as $est)
                        <option value="{{ $est->id }}">{{ $est->nombre }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Tipo de indicador (mensual / trimestral) --}}
            <div id="campo-tipo-indicador">
                <label for="tipo_indicador" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-[#64748B]">Tipo de indicador</label>
                <select id="tipo_indicador" name="tipo_indicador" class="w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm font-medium text-brand-950 focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20">
                    <option value="">Todos</option>
                    @foreach ($tipos as $tipo)
                        <option value="{{ $tipo }}">{{ $tipo }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Severidad (alertas) --}}
            <div id="campo-severidad" class="hidden">
                <label for="severidad" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-[#64748B]">Severidad</label>
                <select id="severidad" name="severidad" class="w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm font-medium text-brand-950 focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20">
                    <option value="">Todas</option>
                    @foreach ($severidades as $severidad)
                        <option value="{{ $severidad }}">{{ $severidad }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Botones de descarga --}}
        <div class="mt-5 flex flex-wrap items-center gap-3 border-t border-[#F1F5F9] pt-4">
            <button type="submit" name="formato" value="xlsx" class="inline-flex items-center gap-2 rounded-lg bg-brand-950 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-brand-800 transition-colors">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12l-3 3m0 0l-3-3m3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
                Exportar Excel
            </button>
            <button type="submit" name="formato" value="pdf" class="inline-flex items-center gap-2 rounded-lg border border-[#CBD5E1] bg-white px-4 py-2 text-sm font-semibold text-brand-950 shadow-xs hover:bg-[#F8FAFC] transition-colors">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0v6m0 0L9 9m1.5 0l1.5-1.5m-5.25 12h12.75" />
                </svg>
                Exportar PDF
            </button>
        </div>
    </form>

    {{-- Descripción de los reportes disponibles --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        @php
            $tarjetas = [
                ['titulo' => 'Mensual', 'descripcion' => 'Cumplimiento por establecimiento e indicador: numerador, denominador, porcentaje, meta y estado (semáforo).'],
                ['titulo' => 'Trimestral', 'descripcion' => 'Tendencia del trimestre y proyección estadística a diciembre (regresión lineal y media móvil).'],
                ['titulo' => 'Alertas', 'descripcion' => 'Bandeja del período con regla, severidad, mensaje, brecha detectada y estado de gestión.'],
            ];
        @endphp

        @foreach ($tarjetas as $tarjeta)
            <div class="rounded-xl border border-[#E2E8F0] bg-white p-5 shadow-xs">
                <p class="text-xs font-semibold uppercase tracking-wider text-accent-600">{{ $tarjeta['titulo'] }}</p>
                <p class="mt-2 text-sm leading-relaxed text-[#475569]">{{ $tarjeta['descripcion'] }}</p>
            </div>
        @endforeach
    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const tipo = document.getElementById('tipo');
    const campoMes = document.getElementById('campo-mes');
    const campoTrimestre = document.getElementById('campo-trimestre');
    const campoTipoIndicador = document.getElementById('campo-tipo-indicador');
    const campoSeveridad = document.getElementById('campo-severidad');

    function alternarCampos() {
        const valor = tipo.value;
        campoMes.classList.toggle('hidden', valor === 'trimestral');
        campoTrimestre.classList.toggle('hidden', valor !== 'trimestral');
        campoTipoIndicador.classList.toggle('hidden', valor === 'alertas');
        campoSeveridad.classList.toggle('hidden', valor !== 'alertas');
    }

    tipo.addEventListener('change', alternarCampos);
    alternarCampos();
});
</script>
@endpush
