{{--
    Gráfico de barras agrupadas en SVG puro (sin librería de gráficos): indicadores por fuente de información.

    Recibe:
      $titulo  string
      $grupos  array<int, string>  etiquetas del eje X (fuentes)
      $series  array<int, array{nombre: string, color: string, valores: array<int, int>}>  un valor por grupo
      $maximo  int                 tope del eje Y
      $paso    int                 separación entre marcas del eje Y
--}}
@php
    [$izq, $der, $arriba, $abajo] = [44, 548, 28, 164]; // margen arriba para la etiqueta de la barra más alta
    [$anchoBarra, $separacion] = [30, 4];
    $anchoGrupo = ($der - $izq) / max(count($grupos), 1);
    $anchoBloque = count($series) * $anchoBarra + (count($series) - 1) * $separacion;
    $posY = fn (float $valor): float => round($abajo - ($valor / $maximo) * ($abajo - $arriba), 1);
    $posXBarra = fn (int $grupo, int $serie): float => round($izq + $grupo * $anchoGrupo + ($anchoGrupo - $anchoBloque) / 2 + $serie * ($anchoBarra + $separacion), 1);
@endphp

<figure>
    <svg viewBox="0 0 560 196" class="mx-auto h-auto w-full max-w-[620px]" role="img" aria-labelledby="grafico-fuentes-titulo" aria-describedby="grafico-fuentes-desc">
        <title id="grafico-fuentes-titulo">{{ $titulo }}: {{ collect($series)->pluck('nombre')->implode(', ') }} por {{ implode(', ', $grupos) }}</title>
        <desc id="grafico-fuentes-desc">
            @foreach ($grupos as $g => $grupo)
                {{ $grupo }}:
                @foreach ($series as $serie)
                    {{ $serie['nombre'] }} {{ $serie['valores'][$g] }}{{ $loop->last ? '.' : ',' }}
                @endforeach
            @endforeach
        </desc>

        {{-- Grilla y eje Y --}}
        @for ($marca = 0; $marca <= $maximo; $marca += $paso)
            <line x1="{{ $izq }}" x2="{{ $der }}" y1="{{ $posY($marca) }}" y2="{{ $posY($marca) }}" class="stroke-[#E2E8F0]" />
            <text x="{{ $izq - 10 }}" y="{{ $posY($marca) + 5 }}" text-anchor="end" class="fill-[#475569] text-[16px]">{{ $marca }}</text>
        @endfor

        {{-- Barras --}}
        @foreach ($grupos as $g => $grupo)
            @foreach ($series as $s => $serie)
                @php($valor = $serie['valores'][$g])
                <rect x="{{ $posXBarra($g, $s) }}" y="{{ $posY($valor) }}" width="{{ $anchoBarra }}" height="{{ $abajo - $posY($valor) }}" rx="2" fill="{{ $serie['color'] }}">
                    <title>{{ $serie['nombre'] }} · {{ $grupo }}: {{ $valor }} indicadores</title>
                </rect>
                <text x="{{ $posXBarra($g, $s) + $anchoBarra / 2 }}" y="{{ $posY($valor) - 6 }}" text-anchor="middle" class="fill-[#475569] text-[15px] font-semibold">{{ $valor }}</text>
            @endforeach

            <text x="{{ $izq + ($g + 0.5) * $anchoGrupo }}" y="{{ $abajo + 22 }}" text-anchor="middle" class="fill-[#1E293B] text-[16px] font-medium">{{ $grupo }}</text>
        @endforeach
    </svg>

    <figcaption class="flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-sm text-[#334155]">
        @foreach ($series as $serie)
            <span class="flex items-center gap-2">
                <span class="h-3 w-3 rounded-sm" style="background: {{ $serie['color'] }}" aria-hidden="true"></span>
                {{ $serie['nombre'] }}
            </span>
        @endforeach
    </figcaption>
</figure>
