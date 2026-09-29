{{--
    Gráfico de líneas en SVG puro (sin librería de gráficos): cumplimiento anual por tipo.
    Lo que viene después de $ultimoReal se dibuja punteado sobre la zona de proyección.

    Recibe:
      $titulo      string
      $anios       array<int, string>   etiquetas del eje X
      $series      array<int, array{nombre: string, color: string, valores: array<int, int|float>}>  valores en %
      $ultimoReal  int                  índice del último año con datos reales
--}}
@php
    [$izq, $der, $arriba, $abajo, $margen] = [44, 548, 12, 164, 18];
    $pasoX = ($der - $izq - 2 * $margen) / max(count($anios) - 1, 1);
    $posX = fn (int $i): float => round($izq + $margen + $i * $pasoX, 1);
    $posY = fn (float $valor): float => round($abajo - ($valor / 100) * ($abajo - $arriba), 1);
@endphp

<figure>
    <svg viewBox="0 0 560 196" class="mx-auto h-auto w-full max-w-[620px]" role="img" aria-labelledby="grafico-cumplimiento-titulo" aria-describedby="grafico-cumplimiento-desc">
        <title id="grafico-cumplimiento-titulo">{{ $titulo }}: {{ collect($series)->pluck('nombre')->implode(' y ') }}, {{ head($anios) }} a {{ last($anios) }} ({{ last($anios) }} proyectado)</title>
        <desc id="grafico-cumplimiento-desc">
            @foreach ($series as $serie)
                {{ $serie['nombre'] }}:
                @foreach ($serie['valores'] as $i => $valor)
                    {{ $anios[$i] }} {{ $valor }}%{{ $i > $ultimoReal ? ' (proyección)' : '' }}{{ $loop->last ? '.' : ',' }}
                @endforeach
            @endforeach
        </desc>

        {{-- Zona de proyección --}}
        <rect x="{{ $posX($ultimoReal) }}" y="{{ $arriba }}" width="{{ $der - $posX($ultimoReal) }}" height="{{ $abajo - $arriba }}" class="fill-accent-500/10" />
        <text x="{{ $der - 8 }}" y="{{ $arriba + 18 }}" text-anchor="end" class="fill-accent-600 text-[15px] font-semibold">Proyección</text>

        {{-- Grilla y eje Y --}}
        @foreach ([0, 20, 40, 60, 80, 100] as $marca)
            <line x1="{{ $izq }}" x2="{{ $der }}" y1="{{ $posY($marca) }}" y2="{{ $posY($marca) }}" class="stroke-[#E2E8F0]" />
            <text x="{{ $izq - 10 }}" y="{{ $posY($marca) + 5 }}" text-anchor="end" class="fill-[#475569] text-[16px]">{{ $marca }}</text>
        @endforeach

        {{-- Eje X --}}
        @foreach ($anios as $i => $anio)
            <text x="{{ $posX($i) }}" y="{{ $abajo + 22 }}" text-anchor="middle" class="fill-[#475569] text-[16px]">{{ $anio }}</text>
        @endforeach

        {{-- Series: tramo real continuo + tramo proyectado punteado --}}
        @foreach ($series as $serie)
            @php
                $puntos = collect($serie['valores'])->map(fn ($valor, $i) => $posX($i).','.$posY($valor));
            @endphp
            <polyline points="{{ $puntos->take($ultimoReal + 1)->implode(' ') }}" fill="none" stroke="{{ $serie['color'] }}" stroke-width="3" stroke-linejoin="round" stroke-linecap="round" />
            <polyline points="{{ $puntos->slice($ultimoReal)->implode(' ') }}" fill="none" stroke="{{ $serie['color'] }}" stroke-width="3" stroke-dasharray="6 5" stroke-linecap="round" />

            @foreach ($serie['valores'] as $i => $valor)
                <circle cx="{{ $posX($i) }}" cy="{{ $posY($valor) }}" r="5.5" fill="{{ $serie['color'] }}" stroke="#fff" stroke-width="2">
                    <title>{{ $serie['nombre'] }} {{ $anios[$i] }}: {{ $valor }}%{{ $i > $ultimoReal ? ' (proyección)' : '' }}</title>
                </circle>
            @endforeach
        @endforeach
    </svg>

    <figcaption class="flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-sm text-[#334155]">
        @foreach ($series as $serie)
            <span class="flex items-center gap-2">
                <span class="relative flex h-3 w-6 items-center" aria-hidden="true">
                    <span class="h-0.5 w-6 rounded-full" style="background: {{ $serie['color'] }}"></span>
                    <span class="absolute left-1/2 h-2.5 w-2.5 -translate-x-1/2 rounded-full" style="background: {{ $serie['color'] }}"></span>
                </span>
                {{ $serie['nombre'] }}
            </span>
        @endforeach
    </figcaption>
</figure>
