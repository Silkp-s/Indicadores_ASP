<?php

namespace App\Services;

use App\Models\DeisAtencionUrgencia;
use App\Models\DeisDefuncion;
use App\Models\DeisEgreso;
use App\Models\DeisImportacion;
use App\Models\Establecimiento;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Calcula los KPIs, las series de los gráficos y las tablas de contraste del módulo Inteligencia DEIS
 * a partir de los datos DEIS importados de la comuna y de la población inscrita de la red municipal.
 */
class DeisIndicadoresService
{
    /** Primer año que la base de defunciones del DEIS publica como cifra preliminar (además del año en curso). */
    public const ANIO_PRELIMINAR_DEFUNCIONES = 2026;

    /** Meses abreviados para los ejes de los gráficos. */
    public const MESES = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

    /** Nombres completos de los meses (1 = enero). */
    private const NOMBRES_MESES = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril', 5 => 'mayo', 6 => 'junio',
        7 => 'julio', 8 => 'agosto', 9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
    ];

    /** Capítulos CIE-10 que se muestran en los rankings. */
    private const TOP_CAPITULOS = 10;

    /**
     * Atenciones de urgencia del año, la causa y el establecimiento pedidos (los valores no válidos usan el predeterminado).
     *
     * @return array<string, mixed>
     */
    public function urgencias(?string $anio, ?string $causa, ?string $establecimiento): array
    {
        $anios = $this->anios(DeisAtencionUrgencia::query());

        if ($anios === []) {
            return ['hayDatos' => false];
        }

        $anioSeleccionado = $this->opcion($this->entero($anio), $anios, $anios[0]);

        /** @var array<int, string> $causas */
        $causas = DeisAtencionUrgencia::query()
            ->where('anio', $anioSeleccionado)
            ->selectRaw('id_causa, min(glosa_causa) as glosa_causa')
            ->groupBy('id_causa')
            ->orderBy('id_causa')
            ->get()
            ->mapWithKeys(fn (DeisAtencionUrgencia $fila) => [(int) $fila->id_causa => Str::squish((string) $fila->glosa_causa)])
            ->all();

        $idsCausa = array_keys($causas);
        $causaSeleccionada = $this->opcion(
            $this->entero($causa),
            $idsCausa,
            in_array(DeisAtencionUrgencia::CAUSA_TOTAL_ATENCIONES, $idsCausa, true) ? DeisAtencionUrgencia::CAUSA_TOTAL_ATENCIONES : $idsCausa[0],
        );

        /** @var list<array{codigo: string, nombre: string, tipo: string}> $establecimientos */
        $establecimientos = DeisAtencionUrgencia::query()
            ->where('anio', $anioSeleccionado)
            ->selectRaw('codigo_establecimiento, min(nombre_establecimiento) as nombre_establecimiento, min(tipo_establecimiento) as tipo_establecimiento')
            ->groupBy('codigo_establecimiento')
            ->get()
            ->map(fn (DeisAtencionUrgencia $fila) => [
                'codigo' => (string) $fila->codigo_establecimiento,
                'nombre' => Str::squish((string) $fila->nombre_establecimiento),
                'tipo' => Str::squish((string) $fila->tipo_establecimiento),
            ])
            ->sortBy('nombre', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();

        $establecimientoSeleccionado = in_array($establecimiento, array_column($establecimientos, 'codigo'), true) ? $establecimiento : 'todos';
        $visibles = array_values(array_filter(
            $establecimientos,
            fn (array $fila) => $establecimientoSeleccionado === 'todos' || $fila['codigo'] === $establecimientoSeleccionado,
        ));

        $filtrar = fn (): Builder => DeisAtencionUrgencia::query()
            ->where('anio', $anioSeleccionado)
            ->when($establecimientoSeleccionado !== 'todos', fn (Builder $query) => $query->where('codigo_establecimiento', $establecimientoSeleccionado));

        $filas = $filtrar()
            ->where('id_causa', $causaSeleccionada)
            ->orderBy('semana')
            ->get(['codigo_establecimiento', 'semana', 'total', ...array_keys(DeisAtencionUrgencia::GRUPOS_EDAD)]);

        $totalesCausasBase = $filtrar()
            ->whereIn('id_causa', [DeisAtencionUrgencia::CAUSA_TOTAL_ATENCIONES, DeisAtencionUrgencia::CAUSA_RESPIRATORIA])
            ->selectRaw('id_causa, sum(total) as total_causa')
            ->groupBy('id_causa')
            ->get()
            ->mapWithKeys(fn (DeisAtencionUrgencia $fila) => [(int) $fila->id_causa => (int) $fila->total_causa]);

        $total = (int) $filas->sum('total');
        $porSemana = $filas->groupBy('semana')->map(fn (Collection $grupo) => (int) $grupo->sum('total'))->sortKeys();

        $semanaMaxima = null;

        foreach ($porSemana as $semana => $totalSemana) {
            if ($semanaMaxima === null || $totalSemana > $semanaMaxima['total']) {
                $semanaMaxima = ['semana' => (int) $semana, 'total' => $totalSemana];
            }
        }

        $semanas = $porSemana->isEmpty() ? [] : range((int) $porSemana->keys()->min(), (int) $porSemana->keys()->max());
        $porEstablecimiento = $filas->groupBy('codigo_establecimiento');

        return [
            'hayDatos' => true,
            'anios' => $anios,
            'causas' => $causas,
            'establecimientos' => $establecimientos,
            'filtros' => [
                'anio' => $anioSeleccionado,
                'causa' => $causaSeleccionada,
                'establecimiento' => $establecimientoSeleccionado,
            ],
            'glosaCausa' => $causas[$causaSeleccionada],
            'kpis' => [
                'total' => $total,
                'semanas' => $porSemana->count(),
                'promedio_semanal' => $porSemana->isEmpty() ? null : $total / $porSemana->count(),
                'semana_maxima' => $semanaMaxima,
                'porcentaje_respiratorio' => $this->porcentaje(
                    $totalesCausasBase[DeisAtencionUrgencia::CAUSA_RESPIRATORIA] ?? 0,
                    $totalesCausasBase[DeisAtencionUrgencia::CAUSA_TOTAL_ATENCIONES] ?? 0,
                ),
            ],
            'grafico' => [
                'categorias' => array_map(fn (int $semana) => "S{$semana}", $semanas),
                'series' => collect($visibles)
                    ->filter(fn (array $fila) => $porEstablecimiento->has($fila['codigo']))
                    ->map(function (array $fila) use ($porEstablecimiento, $semanas): array {
                        $valores = $porEstablecimiento->get($fila['codigo'])
                            ->mapWithKeys(fn (DeisAtencionUrgencia $registro) => [(int) $registro->semana => (int) $registro->total]);

                        return [
                            'name' => $this->etiquetaGrafico($fila['nombre']),
                            'data' => array_map(fn (int $semana) => $valores->get($semana), $semanas),
                        ];
                    })
                    ->values()
                    ->all(),
            ],
            'contraste' => $this->contrasteUrgencias($visibles, $filas),
            'gruposEdad' => $this->gruposEdadUrgencias($filas),
        ];
    }

    /**
     * Defunciones del año pedido (o del más reciente con datos).
     *
     * @return array<string, mixed>
     */
    public function defunciones(?string $anio): array
    {
        $anios = $this->anios(DeisDefuncion::query());

        if ($anios === []) {
            return ['hayDatos' => false];
        }

        $anioSeleccionado = $this->opcion($this->entero($anio), $anios, $anios[0]);

        $filas = DeisDefuncion::query()
            ->where('anio', $anioSeleccionado)
            ->get(['mes', 'sexo', 'grupo_edad', 'capitulo_codigo', 'capitulo_glosa', 'total']);

        $total = (int) $filas->sum('total');
        $mayores = (int) $filas->whereIn('grupo_edad', DeisDefuncion::GRUPOS_65_Y_MAS)->sum('total');
        $red = $this->redMunicipal();

        $porMes = $filas->groupBy('mes')->map(fn (Collection $grupo) => (int) $grupo->sum('total'));
        $ultimoMes = (int) $porMes->keys()->max();
        $capitulos = $this->capitulos($filas, 'total', $total);
        $gruposEdad = $this->gruposEdad($filas, 'total', DeisDefuncion::GRUPOS_EDAD, $total);

        return [
            'hayDatos' => true,
            'anios' => $anios,
            'filtros' => ['anio' => $anioSeleccionado],
            'preliminar' => $anioSeleccionado >= self::ANIO_PRELIMINAR_DEFUNCIONES || $anioSeleccionado >= now()->year,
            'ultimoMes' => $ultimoMes,
            'ultimoMesNombre' => self::NOMBRES_MESES[$ultimoMes] ?? (string) $ultimoMes,
            'kpis' => [
                'total' => $total,
                'mujeres' => (int) $filas->where('sexo', 'Mujer')->sum('total'),
                'hombres' => (int) $filas->where('sexo', 'Hombre')->sum('total'),
                'mayores_65' => $mayores,
                'porcentaje_mayores_65' => $this->porcentaje($mayores, $total),
                'tasa' => $this->tasaPorMil($total, $red['poblacion']),
            ],
            'red' => $red,
            'capitulos' => $capitulos,
            'graficoCapitulos' => $this->graficoCapitulos($capitulos),
            'graficoMeses' => [
                'categorias' => self::MESES,
                'datos' => array_map(fn (int $mes) => $mes > $ultimoMes ? null : $porMes->get($mes, 0), range(1, 12)),
            ],
            'graficoEdad' => [
                'categorias' => array_map(fn (array $grupo) => $this->etiquetaGrafico($grupo['etiqueta']), $gruposEdad),
                'datos' => array_column($gruposEdad, 'total'),
            ],
        ];
    }

    /**
     * Egresos hospitalarios del año pedido (o del más reciente con datos).
     *
     * @return array<string, mixed>
     */
    public function egresos(?string $anio): array
    {
        $anios = $this->anios(DeisEgreso::query());

        if ($anios === []) {
            return ['hayDatos' => false];
        }

        $anioSeleccionado = $this->opcion($this->entero($anio), $anios, $anios[0]);

        $filas = DeisEgreso::query()
            ->where('anio', $anioSeleccionado)
            ->get(['pertenencia', 'sexo', 'grupo_edad', 'capitulo_codigo', 'capitulo_glosa', 'condicion', 'egresos', 'dias_estada']);

        $total = (int) $filas->sum('egresos');
        $dias = (int) $filas->sum('dias_estada');
        $fallecidos = (int) $filas->where('condicion', DeisEgreso::CONDICION_FALLECIDO)->sum('egresos');
        $publico = (int) $filas->where('pertenencia', DeisEgreso::PERTENENCIA_PUBLICO)->sum('egresos');
        $privado = (int) $filas->where('pertenencia', DeisEgreso::PERTENENCIA_PRIVADO)->sum('egresos');
        $red = $this->redMunicipal();

        $capitulos = $this->capitulos($filas, 'egresos', $total, fn (Collection $grupo, int $egresos): array => [
            'promedio_estada' => $egresos > 0 ? $grupo->sum('dias_estada') / $egresos : null,
            'fallecidos' => (int) $grupo->where('condicion', DeisEgreso::CONDICION_FALLECIDO)->sum('egresos'),
        ]);
        $gruposEdad = $this->gruposEdad($filas, 'egresos', DeisEgreso::GRUPOS_EDAD, $total);

        $sector = [DeisEgreso::PERTENENCIA_PUBLICO => $publico, DeisEgreso::PERTENENCIA_PRIVADO => $privado];

        if ($total - $publico - $privado > 0) {
            $sector[DeisImportService::SIN_INFORMACION] = $total - $publico - $privado;
        }

        return [
            'hayDatos' => true,
            'anios' => $anios,
            'filtros' => ['anio' => $anioSeleccionado],
            'kpis' => [
                'total' => $total,
                'mujeres' => (int) $filas->where('sexo', 'Mujer')->sum('egresos'),
                'hombres' => (int) $filas->where('sexo', 'Hombre')->sum('egresos'),
                'dias_estada' => $dias,
                'promedio_estada' => $total > 0 ? $dias / $total : null,
                'fallecidos' => $fallecidos,
                'porcentaje_fallecidos' => $this->porcentaje($fallecidos, $total),
                'publico' => $publico,
                'privado' => $privado,
                'porcentaje_publico' => $this->porcentaje($publico, $total),
                'tasa' => $this->tasaPorMil($total, $red['poblacion']),
            ],
            'red' => $red,
            'capitulos' => $capitulos,
            'graficoCapitulos' => $this->graficoCapitulos($capitulos),
            'graficoEdad' => [
                'categorias' => array_map(fn (array $grupo) => $this->etiquetaGrafico($grupo['etiqueta']), $gruposEdad),
                'datos' => array_column($gruposEdad, 'total'),
            ],
            'graficoSector' => [
                'categorias' => array_keys($sector),
                'datos' => array_values($sector),
            ],
        ];
    }

    /**
     * Fuente de un dataset: su descripción, la última importación completada y el tablero SST relacionado.
     *
     * @return array{dataset: string, titulo: string, descripcion: ?string, importacion: ?DeisImportacion, origen: ?array{archivo: string, url: ?string}, tablero: ?array{slug: string, titulo: string, descripcion: string, url: string}}
     */
    public function fuente(string $dataset): array
    {
        $importacion = DeisImportacion::ultimaCompletada($dataset);
        $slugTablero = config("deis.tablero_relacionado.{$dataset}");

        return [
            'dataset' => $dataset,
            'titulo' => (string) config("deis.datasets.{$dataset}.titulo"),
            'descripcion' => config("deis.datasets.{$dataset}.descripcion"),
            'importacion' => $importacion,
            'origen' => $importacion === null ? null : $this->origenLegible((string) $importacion->origen),
            'tablero' => Arr::first(config('deis.tableros', []), fn (array $tablero) => $tablero['slug'] === $slugTablero),
        ];
    }

    /**
     * Última importación registrada de cada dataset, cualquiera sea su estado.
     *
     * @return Collection<string, DeisImportacion>
     */
    public function ultimasImportaciones(): Collection
    {
        return DeisImportacion::query()
            ->with('user:id,name')
            ->whereIn('id', DeisImportacion::query()->selectRaw('max(id)')->groupBy('dataset'))
            ->get()
            ->keyBy('dataset');
    }

    /**
     * Tabla "Contraste con la red municipal": cada establecimiento DEIS con su CESFAM local y la tasa aprox. por 1.000 inscritos.
     *
     * @param  list<array{codigo: string, nombre: string, tipo: string}>  $establecimientos
     * @param  Collection<int, DeisAtencionUrgencia>  $filas
     * @return list<array<string, mixed>>
     */
    private function contrasteUrgencias(array $establecimientos, Collection $filas): array
    {
        $mapa = config('deis.urgencias.establecimientos_locales', []);
        $locales = Establecimiento::query()
            ->whereIn('codigo_deis', array_map('strval', array_values($mapa)))
            ->get()
            ->keyBy('codigo_deis');
        $totales = $filas->groupBy('codigo_establecimiento')->map(fn (Collection $grupo) => (int) $grupo->sum('total'));

        return array_map(function (array $establecimiento) use ($mapa, $locales, $totales): array {
            $codigoLocal = isset($mapa[$establecimiento['codigo']]) ? (string) $mapa[$establecimiento['codigo']] : null;
            $local = $codigoLocal === null ? null : $locales->get($codigoLocal);
            $total = (int) $totales->get($establecimiento['codigo'], 0);

            return [
                ...$establecimiento,
                'total' => $total,
                'red_sst' => $codigoLocal === null,
                'codigo_local' => $codigoLocal,
                'local' => $local === null ? null : [
                    'nombre' => $local->nombre,
                    'codigo_deis' => (string) $local->codigo_deis,
                    'poblacion' => (int) $local->poblacion_inscrita,
                ],
                'tasa' => $this->tasaPorMil($total, (int) ($local?->poblacion_inscrita ?? 0)),
            ];
        }, $establecimientos);
    }

    /**
     * Distribución de las atenciones por grupo de edad.
     *
     * @param  Collection<int, DeisAtencionUrgencia>  $filas
     * @return list<array{etiqueta: string, total: int, porcentaje: ?float}>
     */
    private function gruposEdadUrgencias(Collection $filas): array
    {
        $sumas = array_map(fn (string $columna) => (int) $filas->sum($columna), array_keys(DeisAtencionUrgencia::GRUPOS_EDAD));
        $total = array_sum($sumas);

        return array_map(fn (string $etiqueta, int $suma) => [
            'etiqueta' => $etiqueta,
            'total' => $suma,
            'porcentaje' => $this->porcentaje($suma, $total),
        ], array_values(DeisAtencionUrgencia::GRUPOS_EDAD), $sumas);
    }

    /**
     * Capítulos CIE-10 con más casos, de mayor a menor. Los capítulos se agrupan por código, nunca por nombre.
     *
     * @param  Collection<int, Model>  $filas
     * @param  (Closure(Collection<int, Model>, int): array<string, mixed>)|null  $extras
     * @return list<array<string, mixed>>
     */
    private function capitulos(Collection $filas, string $columna, int $total, ?Closure $extras = null): array
    {
        return $filas
            ->groupBy('capitulo_codigo')
            ->map(function (Collection $grupo, string|int $codigo) use ($columna, $total, $extras): array {
                $casos = (int) $grupo->sum($columna);

                return [
                    'codigo' => (string) $codigo,
                    'glosa' => Str::squish((string) $grupo->first()->capitulo_glosa),
                    'total' => $casos,
                    'porcentaje' => $this->porcentaje($casos, $total),
                    ...($extras === null ? [] : $extras($grupo, $casos)),
                ];
            })
            ->sortBy([['total', 'desc'], ['codigo', 'asc']])
            ->take(self::TOP_CAPITULOS)
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $capitulos
     * @return array{categorias: list<string>, codigos: list<string>, datos: list<int>}
     */
    private function graficoCapitulos(array $capitulos): array
    {
        return [
            'categorias' => array_map(fn (array $capitulo) => $this->etiquetaGrafico($capitulo['glosa']), $capitulos),
            'codigos' => array_column($capitulos, 'codigo'),
            'datos' => array_column($capitulos, 'total'),
        ];
    }

    /**
     * Casos por grupo de edad en su orden natural; los grupos no previstos (p. ej. «Sin información») van al final.
     *
     * @param  Collection<int, Model>  $filas
     * @param  list<string>  $orden
     * @return list<array{etiqueta: string, total: int, porcentaje: ?float}>
     */
    private function gruposEdad(Collection $filas, string $columna, array $orden, int $total): array
    {
        $sumas = $filas->groupBy('grupo_edad')->map(fn (Collection $grupo) => (int) $grupo->sum($columna));
        $grupos = [...$orden, ...array_values(array_diff($sumas->keys()->map(fn ($grupo) => (string) $grupo)->all(), $orden))];

        return array_map(fn (string $grupo) => [
            'etiqueta' => Str::ucfirst($grupo),
            'total' => (int) $sumas->get($grupo, 0),
            'porcentaje' => $this->porcentaje((int) $sumas->get($grupo, 0), $total),
        ], $grupos);
    }

    /**
     * Población inscrita de los establecimientos municipales activos.
     *
     * @return array{poblacion: int, establecimientos: int}
     */
    private function redMunicipal(): array
    {
        $red = Establecimiento::query()
            ->where('is_active', true)
            ->selectRaw('count(*) as cantidad, coalesce(sum(poblacion_inscrita), 0) as poblacion')
            ->toBase()
            ->first();

        return [
            'poblacion' => (int) ($red->poblacion ?? 0),
            'establecimientos' => (int) ($red->cantidad ?? 0),
        ];
    }

    /**
     * Años con datos, del más reciente al más antiguo.
     *
     * @param  Builder<covariant Model>  $query
     * @return list<int>
     */
    private function anios(Builder $query): array
    {
        return $query->distinct()->orderByDesc('anio')->pluck('anio')->map(fn ($anio) => (int) $anio)->values()->all();
    }

    /**
     * Origen de una importación para mostrar: el nombre del archivo y, si es una URL web, el enlace.
     *
     * @return array{archivo: string, url: ?string}
     */
    private function origenLegible(string $origen): array
    {
        $esUrl = Str::startsWith(Str::lower($origen), ['https://', 'http://']);
        $ruta = $esUrl ? (string) parse_url($origen, PHP_URL_PATH) : str_replace('\\', '/', $origen);

        return [
            'archivo' => Str::afterLast($ruta, '/') ?: $origen,
            'url' => $esUrl ? $origen : null,
        ];
    }

    /**
     * Texto apto para los ejes y tooltips de ApexCharts (que pueden interpretar HTML).
     */
    private function etiquetaGrafico(string $texto): string
    {
        return Str::squish(strip_tags($texto));
    }

    /**
     * @param  list<int>  $permitidos
     */
    private function opcion(?int $valor, array $permitidos, int $predeterminado): int
    {
        return in_array($valor, $permitidos, true) ? $valor : $predeterminado;
    }

    private function entero(?string $valor): ?int
    {
        $entero = filter_var($valor, FILTER_VALIDATE_INT);

        return $entero === false ? null : $entero;
    }

    private function porcentaje(int $parte, int $total): ?float
    {
        return $total > 0 ? $parte * 100 / $total : null;
    }

    /**
     * Casos por cada 1.000 inscritos, o null si no hay población (nunca se divide por cero).
     */
    private function tasaPorMil(int $casos, int $poblacion): ?float
    {
        return $poblacion > 0 ? $casos * 1000 / $poblacion : null;
    }
}
