@extends('layouts.app')

@section('title', 'Inteligencia DEIS')
@section('page-title', 'Inteligencia DEIS')
@section('page-subtitle', 'Información epidemiológica oficial DEIS-MINSAL de la comuna de '.config('deis.comuna.nombre').' y tableros públicos del Servicio de Salud Talcahuano')

@section('content')
<div class="space-y-6">

    {{-- Pestañas del módulo y actualización de datos (solo Admin) --}}
    <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-[#E2E8F0] bg-white p-3 shadow-xs">
        <nav aria-label="Secciones de Inteligencia DEIS" class="w-full overflow-x-auto sm:w-auto">
            <div class="flex w-max items-center gap-1.5 rounded-lg bg-[#F1F5F9] p-1 text-sm font-semibold">
                @foreach ($pestanas as $clave => $etiqueta)
                    <a
                        href="{{ route('inteligencia-deis.index', ['tab' => $clave]) }}"
                        @if ($clave === $pestana) aria-current="page" @endif
                        @class([
                            'whitespace-nowrap rounded-md px-3.5 py-1.5 transition-colors',
                            'bg-white text-brand-950 shadow-2xs' => $clave === $pestana,
                            'text-[#64748B] hover:bg-white/80 hover:text-brand-950' => $clave !== $pestana,
                        ])
                    >
                        {{ $etiqueta }}
                    </a>
                @endforeach
            </div>
        </nav>

        @if ($esAdmin)
            <form
                method="POST"
                action="{{ route('inteligencia-deis.actualizar') }}"
                data-confirmar="Se descargarán nuevamente desde el repositorio del MINSAL los datasets de urgencias, defunciones y egresos hospitalarios, y se reemplazarán los datos de los años que incluyan. El proceso corre en segundo plano y puede tardar varios minutos."
                class="w-full sm:w-auto"
            >
                @csrf
                <input type="hidden" name="tab" value="{{ $pestana }}">
                <button
                    type="submit"
                    class="flex w-full items-center justify-center gap-2 rounded-lg bg-accent-500 px-4 py-2.5 text-sm font-semibold text-white shadow-xs transition-colors hover:bg-accent-600 sm:w-auto"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span>Actualizar datos DEIS</span>
                </button>
            </form>
        @endif
    </div>

    @if ($esAdmin)
        @include('inteligencia-deis.partials.estado-importaciones')
    @endif

    @include('inteligencia-deis.partials.'.$pestana)

</div>
@endsection
