<?php

namespace App\Services;

use App\Models\DeisAtencionUrgencia;
use App\Models\DeisDefuncion;
use App\Models\DeisEgreso;
use App\Models\DeisImportacion;
use Generator;
use HashContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Throwable;
use ZipArchive;

/**
 * Importa los datasets abiertos del DEIS-MINSAL (urgencias, defunciones y egresos)
 * filtrados a la comuna configurada en `deis.comuna`, agregándolos en memoria
 * mientras se lee el CSV en streaming.
 */
class DeisImportService
{
    /** Filas por sentencia INSERT. */
    private const TAMANO_LOTE = 500;

    /** Largo máximo del mensaje guardado en la bitácora. */
    private const LARGO_MENSAJE = 2000;

    /** Bytes máximos de una fila del CSV; las filas reales miden menos de 2 KB, así que una mayor indica un archivo dañado. */
    private const LARGO_MAXIMO_FILA = 65536;

    /** Segundos que dura el candado de importación de un dataset (igual al tiempo límite del job). */
    private const DURACION_CANDADO = 1800;

    /** Valor usado cuando un campo categórico viene vacío o no es reconocible. */
    public const SIN_INFORMACION = 'Sin información';

    /** Capítulo asignado a los diagnósticos sin código CIE-10 reconocible. */
    public const SIN_CLASIFICAR = ['codigo' => 'S/C', 'glosa' => 'Sin clasificar'];

    /**
     * Capítulos CIE-10: [desde, hasta, glosa]. Se comparan contra la letra y los dos primeros dígitos del diagnóstico.
     *
     * @var list<array{0: string, 1: string, 2: string}>
     */
    public const CAPITULOS_CIE10 = [
        ['A00', 'B99', 'Ciertas enfermedades infecciosas y parasitarias'],
        ['C00', 'D48', 'Tumores [Neoplasias]'],
        ['D50', 'D89', 'Enfermedades de la sangre y de los órganos hematopoyéticos'],
        ['E00', 'E90', 'Enfermedades endocrinas, nutricionales y metabólicas'],
        ['F00', 'F99', 'Trastornos mentales y del comportamiento'],
        ['G00', 'G99', 'Enfermedades del sistema nervioso'],
        ['H00', 'H59', 'Enfermedades del ojo y sus anexos'],
        ['H60', 'H95', 'Enfermedades del oído y de la apófisis mastoides'],
        ['I00', 'I99', 'Enfermedades del sistema circulatorio'],
        ['J00', 'J99', 'Enfermedades del sistema respiratorio'],
        ['K00', 'K93', 'Enfermedades del sistema digestivo'],
        ['L00', 'L99', 'Enfermedades de la piel y del tejido subcutáneo'],
        ['M00', 'M99', 'Enfermedades del sistema osteomuscular y del tejido conjuntivo'],
        ['N00', 'N99', 'Enfermedades del sistema genitourinario'],
        ['O00', 'O99', 'Embarazo, parto y puerperio'],
        ['P00', 'P96', 'Ciertas afecciones originadas en el período perinatal'],
        ['Q00', 'Q99', 'Malformaciones congénitas, deformidades y anomalías cromosómicas'],
        ['R00', 'R99', 'Síntomas, signos y hallazgos anormales clínicos y de laboratorio'],
        ['S00', 'T98', 'Traumatismos, envenenamientos y otras consecuencias de causas externas'],
        ['V01', 'Y98', 'Causas externas de morbilidad y de mortalidad'],
        ['Z00', 'Z99', 'Factores que influyen en el estado de salud y contacto con los servicios de salud'],
        ['U00', 'U99', 'Códigos para propósitos especiales'],
    ];

    /**
     * Columnas obligatorias de cada dataset (cabeceras normalizadas a mayúsculas).
     *
     * @var array<string, list<string>>
     */
    private const COLUMNAS = [
        'urgencias' => [
            'IDESTABLECIMIENTO', 'NESTABLECIMIENTO', 'IDCAUSA', 'GLOSACAUSA', 'TOTAL', 'MENORES_1', 'DE_1_A_4',
            'DE_5_A_14', 'DE_15_A_64', 'DE_65_Y_MAS', 'FECHA', 'SEMANA', 'GLOSATIPOESTABLECIMIENTO', 'CODIGOCOMUNA',
        ],
        'defunciones' => [
            'AÑO', 'FECHA_DEF', 'SEXO_NOMBRE', 'EDAD_TIPO', 'EDAD_CANT', 'COD_COMUNA', 'CAPITULO_DIAG1', 'GLOSA_CAPITULO_DIAG1',
        ],
        'egresos' => [
            'PERTENENCIA_ESTABLECIMIENTO_SALU', 'SEXO', 'GRUPO_EDAD', 'COMUNA_RESIDENCIA', 'ANO_EGRESO', 'DIAG1',
            'DIAS_ESTADA', 'CONDICION_EGRESO',
        ],
    ];

    /** @var array<string, string> */
    private const COLUMNA_COMUNA = [
        'urgencias' => 'CODIGOCOMUNA',
        'defunciones' => 'COD_COMUNA',
        'egresos' => 'COMUNA_RESIDENCIA',
    ];

    /** @var array<string, class-string<Model>> */
    private const MODELOS = [
        'urgencias' => DeisAtencionUrgencia::class,
        'defunciones' => DeisDefuncion::class,
        'egresos' => DeisEgreso::class,
    ];

    /**
     * Importa un dataset desde un archivo local (ZIP o CSV) o lo descarga desde la URL indicada o configurada.
     *
     * Reemplaza los registros de los años presentes en el archivo y deja la bitácora en
     * `completado` o, si algo falla, en `fallido` antes de relanzar la excepción.
     *
     * @throws InvalidArgumentException si el dataset no existe.
     * @throws Throwable si la descarga, la lectura o el guardado fallan.
     */
    public function importar(string $dataset, ?string $archivo = null, ?string $url = null, ?DeisImportacion $importacion = null): DeisImportacion
    {
        if (! in_array($dataset, DeisImportacion::DATASETS, true)) {
            throw new InvalidArgumentException("Dataset DEIS no válido: «{$dataset}». Use urgencias, defunciones o egresos.");
        }

        $url = filled($url) ? $url : config("deis.datasets.{$dataset}.url");
        $origen = $archivo ?? (string) $url;

        $importacion ??= new DeisImportacion;
        $importacion->fill([
            'dataset' => $dataset,
            'origen' => Str::limit($origen, 500, ''),
            'estado' => DeisImportacion::ESTADO_PROCESANDO,
            'filas_leidas' => 0,
            'filas_comuna' => 0,
            'registros_guardados' => 0,
            'mensaje' => null,
            'iniciado_en' => now(),
            'finalizado_en' => null,
        ])->save();

        $candado = Cache::lock("deis-importar-{$dataset}", self::DURACION_CANDADO);

        try {
            if (! $candado->get()) {
                throw new RuntimeException("Ya hay otra importación del dataset «{$dataset}» en curso; espere a que termine.");
            }

            if ($archivo === null && blank($url)) {
                throw new RuntimeException("No hay una URL configurada para el dataset «{$dataset}».");
            }

            $ruta = $archivo ?? $this->descargar($dataset, $url);

            $resultado = match ($dataset) {
                'urgencias' => $this->agregarUrgencias($ruta),
                'defunciones' => $this->agregarDefunciones($ruta),
                'egresos' => $this->agregarEgresos($ruta),
            };

            $this->reemplazarRegistros(self::MODELOS[$dataset], $resultado['registros'], $resultado['anios']);
        } catch (Throwable $exception) {
            $importacion->update([
                'estado' => DeisImportacion::ESTADO_FALLIDO,
                'mensaje' => Str::limit($exception->getMessage(), self::LARGO_MENSAJE),
                'finalizado_en' => now(),
            ]);

            throw $exception;
        } finally {
            $candado->release();
        }

        $importacion->update([
            'estado' => DeisImportacion::ESTADO_COMPLETADO,
            'filas_leidas' => $resultado['filas_leidas'],
            'filas_comuna' => $resultado['filas_comuna'],
            'registros_guardados' => count($resultado['registros']),
            'mensaje' => $this->mensajeCompletado($resultado),
            'finalizado_en' => now(),
        ]);

        return $importacion;
    }

    /**
     * Capítulo CIE-10 de un diagnóstico sin punto (p. ej. `O829`, `C61X`).
     *
     * @return array{codigo: string, glosa: string}
     */
    public static function capituloCie10(string $diagnostico): array
    {
        $codigo = strtoupper(str_replace(['.', ' '], '', trim($diagnostico)));

        if (preg_match('/^[A-Z]\d{2}/', $codigo) !== 1) {
            return self::SIN_CLASIFICAR;
        }

        $categoria = substr($codigo, 0, 3);

        foreach (self::CAPITULOS_CIE10 as [$desde, $hasta, $glosa]) {
            if (strcmp($categoria, $desde) >= 0 && strcmp($categoria, $hasta) <= 0) {
                return ['codigo' => "{$desde}-{$hasta}", 'glosa' => $glosa];
            }
        }

        return self::SIN_CLASIFICAR;
    }

    /**
     * Grupo de edad de una defunción según EDAD_TIPO (1 = años; 2, 3 y 4 = meses, días y horas) y EDAD_CANT.
     */
    public static function grupoEdadDefuncion(string $tipo, string $cantidad): string
    {
        $tipo = (int) trim($tipo);
        $cantidad = trim($cantidad);

        if (in_array($tipo, [2, 3, 4], true)) {
            return 'Menor de 1 año';
        }

        if ($tipo !== 1 || ! ctype_digit($cantidad) || (int) $cantidad > 125) {
            return self::SIN_INFORMACION;
        }

        return match (true) {
            (int) $cantidad < 1 => 'Menor de 1 año',
            (int) $cantidad <= 14 => '1 a 14',
            (int) $cantidad <= 44 => '15 a 44',
            (int) $cantidad <= 64 => '45 a 64',
            (int) $cantidad <= 79 => '65 a 79',
            default => '80 y más',
        };
    }

    /**
     * Descarga el ZIP del dataset al directorio de descargas y valida que sea un ZIP.
     */
    private function descargar(string $dataset, string $url): string
    {
        $directorio = config('deis.descargas.directorio');
        File::ensureDirectoryExists($directorio);

        $destino = $directorio.DIRECTORY_SEPARATOR."{$dataset}.zip";
        $temporal = "{$destino}.descarga";

        try {
            $respuesta = Http::connectTimeout(30)
                ->timeout((int) config('deis.descargas.timeout', 600))
                ->withOptions(['sink' => $temporal])
                ->get($url);
        } catch (ConnectionException $exception) {
            File::delete($temporal);

            throw new RuntimeException("No se pudo conectar con {$url}: {$exception->getMessage()}", previous: $exception);
        }

        $respuesta->close();

        if ($respuesta->failed()) {
            File::delete($temporal);

            throw new RuntimeException("La descarga desde {$url} falló (HTTP {$respuesta->status()}).");
        }

        if (! $this->esZip($temporal)) {
            File::delete($temporal);

            throw new RuntimeException("El archivo descargado desde {$url} no es un ZIP válido.");
        }

        File::move($temporal, $destino);

        return $destino;
    }

    /**
     * Suma las atenciones de urgencia por establecimiento, causa, año y semana estadística.
     *
     * @return array{registros: list<array<string, mixed>>, anios: list<int>, filas_leidas: int, filas_comuna: int, omitidas: int}
     */
    private function agregarUrgencias(string $ruta): array
    {
        $agregado = [];
        $omitidas = 0;
        $filas = $this->filasDeLaComuna($ruta, 'urgencias');

        foreach ($filas as $fila) {
            $fecha = $this->partesFecha($fila['FECHA']);
            $codigo = $this->texto($fila['IDESTABLECIMIENTO'], 20);
            $causa = (int) $fila['IDCAUSA'];
            $semana = (int) $fila['SEMANA'];

            if ($fecha === null || $codigo === '' || $causa < 1 || $semana < 1 || $semana > 53) {
                $omitidas++;

                continue;
            }

            $anio = $fecha['anio'];
            $clave = "{$codigo}|{$causa}|{$anio}|{$semana}";

            $agregado[$clave] ??= [
                'codigo_establecimiento' => $codigo,
                'nombre_establecimiento' => $this->texto($fila['NESTABLECIMIENTO'], 255),
                'tipo_establecimiento' => $this->texto($fila['GLOSATIPOESTABLECIMIENTO'], 50),
                'id_causa' => $causa,
                'glosa_causa' => $this->texto($fila['GLOSACAUSA'], 255),
                'anio' => $anio,
                'semana' => $semana,
                'total' => 0,
                'menores_1' => 0,
                'de_1_a_4' => 0,
                'de_5_a_14' => 0,
                'de_15_a_64' => 0,
                'de_65_y_mas' => 0,
            ];

            $agregado[$clave]['total'] += $this->entero($fila['TOTAL']);
            $agregado[$clave]['menores_1'] += $this->entero($fila['MENORES_1']);
            $agregado[$clave]['de_1_a_4'] += $this->entero($fila['DE_1_A_4']);
            $agregado[$clave]['de_5_a_14'] += $this->entero($fila['DE_5_A_14']);
            $agregado[$clave]['de_15_a_64'] += $this->entero($fila['DE_15_A_64']);
            $agregado[$clave]['de_65_y_mas'] += $this->entero($fila['DE_65_Y_MAS']);
        }

        return $this->resultado($agregado, $filas->getReturn(), $omitidas);
    }

    /**
     * Cuenta las defunciones por año, mes, sexo, grupo de edad y capítulo CIE-10.
     *
     * @return array{registros: list<array<string, mixed>>, anios: list<int>, filas_leidas: int, filas_comuna: int, omitidas: int}
     */
    private function agregarDefunciones(string $ruta): array
    {
        $agregado = [];
        $omitidas = 0;
        $filas = $this->filasDeLaComuna($ruta, 'defunciones');

        foreach ($filas as $fila) {
            $fecha = $this->partesFecha($fila['FECHA_DEF']);

            if ($fecha === null) {
                $omitidas++;

                continue;
            }

            $anio = (int) $fila['AÑO'] >= 1900 ? (int) $fila['AÑO'] : $fecha['anio'];
            $sexo = $this->sexo($fila['SEXO_NOMBRE']);
            $grupoEdad = self::grupoEdadDefuncion($fila['EDAD_TIPO'], $fila['EDAD_CANT']);
            $capituloCodigo = mb_strtoupper($this->texto($fila['CAPITULO_DIAG1'], 15));
            $capituloGlosa = $this->texto($fila['GLOSA_CAPITULO_DIAG1'], 255);

            if ($capituloCodigo === '') {
                ['codigo' => $capituloCodigo, 'glosa' => $capituloGlosa] = self::SIN_CLASIFICAR;
            }

            $clave = implode('|', [$anio, $fecha['mes'], $sexo, $grupoEdad, $capituloCodigo]);

            $agregado[$clave] ??= [
                'anio' => $anio,
                'mes' => $fecha['mes'],
                'sexo' => $sexo,
                'grupo_edad' => $grupoEdad,
                'capitulo_codigo' => $capituloCodigo,
                'capitulo_glosa' => $capituloGlosa !== '' ? $capituloGlosa : $capituloCodigo,
                'total' => 0,
            ];

            $agregado[$clave]['total']++;
        }

        return $this->resultado($agregado, $filas->getReturn(), $omitidas);
    }

    /**
     * Cuenta los egresos y suma los días de estada por año, sector, sexo, grupo de edad, capítulo CIE-10 y condición.
     *
     * @return array{registros: list<array<string, mixed>>, anios: list<int>, filas_leidas: int, filas_comuna: int, omitidas: int}
     */
    private function agregarEgresos(string $ruta): array
    {
        $agregado = [];
        $omitidas = 0;
        $filas = $this->filasDeLaComuna($ruta, 'egresos');

        foreach ($filas as $fila) {
            $anio = (int) $fila['ANO_EGRESO'];

            if ($anio < 1900) {
                $omitidas++;

                continue;
            }

            $pertenencia = $this->pertenencia($fila['PERTENENCIA_ESTABLECIMIENTO_SALU']);
            $sexo = $this->sexo($fila['SEXO']);
            $grupoEdad = $this->grupoEdadEgreso($fila['GRUPO_EDAD']);
            $capitulo = self::capituloCie10($fila['DIAG1']);
            $condicion = match (trim($fila['CONDICION_EGRESO'])) {
                '1' => DeisEgreso::CONDICION_VIVO,
                '2' => DeisEgreso::CONDICION_FALLECIDO,
                default => self::SIN_INFORMACION,
            };

            $clave = implode('|', [$anio, $pertenencia, $sexo, $grupoEdad, $capitulo['codigo'], $condicion]);

            $agregado[$clave] ??= [
                'anio' => $anio,
                'pertenencia' => $pertenencia,
                'sexo' => $sexo,
                'grupo_edad' => $grupoEdad,
                'capitulo_codigo' => $capitulo['codigo'],
                'capitulo_glosa' => $capitulo['glosa'],
                'condicion' => $condicion,
                'egresos' => 0,
                'dias_estada' => 0,
            ];

            $agregado[$clave]['egresos']++;
            $agregado[$clave]['dias_estada'] += $this->entero($fila['DIAS_ESTADA']);
        }

        return $this->resultado($agregado, $filas->getReturn(), $omitidas);
    }

    /**
     * Recorre el CSV en streaming y entrega, como arreglo columna => valor crudo, solo las filas de la comuna.
     *
     * Las líneas que no contienen el código de la comuna se descartan sin parsearlas. Al terminar
     * devuelve los conteos de filas leídas y filas de la comuna.
     *
     * @return Generator<int, array<string, string>, mixed, array{filas_leidas: int, filas_comuna: int}>
     */
    private function filasDeLaComuna(string $ruta, string $dataset): Generator
    {
        ['stream' => $stream, 'zip' => $zip, 'tamano' => $tamano, 'crc' => $crcEsperado] = $this->abrirCsv($ruta);

        try {
            $crc = $crcEsperado !== null ? hash_init('crc32b') : null;
            $indices = $this->indicesDeColumnas($this->leerLinea($stream, $crc), $dataset);
            $indiceComuna = $indices[self::COLUMNA_COMUNA[$dataset]];
            $codigoComuna = (int) config('deis.comuna.codigo');
            $codigoTexto = (string) $codigoComuna;
            $filasLeidas = 0;
            $filasComuna = 0;

            while (($linea = $this->leerLinea($stream, $crc)) !== false) {
                if (str_contains($linea, '"') && preg_match('/(?:^|;)"/', $linea) === 1) {
                    while (substr_count($linea, '"') % 2 === 1 && ($continuacion = $this->leerLinea($stream, $crc)) !== false) {
                        $linea .= $continuacion;

                        if (strlen($linea) > self::LARGO_MAXIMO_FILA) {
                            throw new RuntimeException('El CSV tiene una fila anormalmente larga (comillas sin cerrar): el archivo está dañado.');
                        }
                    }
                }

                if (strspn($linea, " \t\r\n") === strlen($linea)) {
                    continue;
                }

                $filasLeidas++;

                if (! str_contains($linea, $codigoTexto)) {
                    continue;
                }

                $valores = str_getcsv(rtrim($linea, "\r\n"), ';', '"', '');

                if ((int) ($valores[$indiceComuna] ?? 0) !== $codigoComuna) {
                    continue;
                }

                $filasComuna++;
                $fila = [];

                foreach ($indices as $columna => $indice) {
                    $fila[$columna] = (string) ($valores[$indice] ?? '');
                }

                yield $fila;
            }

            if ($tamano !== null && ftell($stream) !== $tamano) {
                throw new RuntimeException('El CSV dentro del ZIP está incompleto o dañado: se leyeron '.number_format((int) ftell($stream), 0, ',', '.').' de '.number_format($tamano, 0, ',', '.').' bytes.');
            }

            if ($crc !== null && hash_final($crc) !== $crcEsperado) {
                throw new RuntimeException('El CSV dentro del ZIP está dañado: su CRC no coincide con el declarado en el ZIP.');
            }
        } finally {
            fclose($stream);
            $zip?->close();
        }

        return ['filas_leidas' => $filasLeidas, 'filas_comuna' => $filasComuna];
    }

    /**
     * Lee una línea del CSV, la suma al CRC de control y falla si supera el largo máximo de una fila.
     *
     * @param  resource  $stream
     */
    private function leerLinea($stream, ?HashContext $crc): string|false
    {
        $linea = fgets($stream, self::LARGO_MAXIMO_FILA + 1);

        if ($linea === false) {
            return false;
        }

        if ($crc !== null) {
            hash_update($crc, $linea);
        }

        if (strlen($linea) === self::LARGO_MAXIMO_FILA && ! str_ends_with($linea, "\n") && ! feof($stream)) {
            throw new RuntimeException('El CSV tiene una fila de más de '.number_format(self::LARGO_MAXIMO_FILA, 0, ',', '.').' bytes: el archivo está dañado.');
        }

        return $linea;
    }

    /**
     * Abre el CSV plano o la primera entrada `.csv` del ZIP.
     *
     * @return array{stream: resource, zip: ?ZipArchive, tamano: ?int, crc: ?string}
     */
    private function abrirCsv(string $ruta): array
    {
        if (! is_file($ruta)) {
            throw new RuntimeException("No se encontró el archivo {$ruta}.");
        }

        if (strtolower(pathinfo($ruta, PATHINFO_EXTENSION)) === 'csv') {
            $stream = fopen($ruta, 'rb');

            if ($stream === false) {
                throw new RuntimeException("No se pudo abrir el archivo {$ruta}.");
            }

            return ['stream' => $stream, 'zip' => null, 'tamano' => null, 'crc' => null];
        }

        $zip = new ZipArchive;

        if (! $this->esZip($ruta) || $zip->open($ruta, ZipArchive::RDONLY) !== true) {
            throw new RuntimeException("El archivo {$ruta} no es un ZIP válido ni un CSV.");
        }

        $entrada = null;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $nombre = (string) $zip->getNameIndex($i);

            if (stripos($nombre, '.csv') !== false && ! str_ends_with($nombre, '/') && ! str_starts_with($nombre, '__MACOSX/')) {
                $entrada = $nombre;

                break;
            }
        }

        $stream = $entrada !== null ? $zip->getStream($entrada) : false;

        if ($stream === false) {
            $zip->close();

            throw new RuntimeException('El ZIP no contiene ningún archivo CSV.');
        }

        $estadistica = $zip->statName($entrada);

        return ['stream' => $stream, 'zip' => $zip, 'tamano' => (int) $estadistica['size'], 'crc' => sprintf('%08x', $estadistica['crc'])];
    }

    /**
     * Devuelve, a partir de la cabecera, la posición de cada columna obligatoria del dataset.
     *
     * @return array<string, int>
     */
    private function indicesDeColumnas(string|false $cabecera, string $dataset): array
    {
        if ($cabecera === false || trim($cabecera) === '') {
            throw new RuntimeException('El archivo CSV está vacío.');
        }

        if (str_starts_with($cabecera, "\xEF\xBB\xBF")) {
            $cabecera = substr($cabecera, 3);
        }

        $nombres = array_map(
            fn (?string $nombre): string => mb_strtoupper($this->aUtf8((string) $nombre)),
            str_getcsv(rtrim($cabecera, "\r\n"), ';', '"', ''),
        );

        $indices = [];
        $faltantes = [];

        foreach (self::COLUMNAS[$dataset] as $columna) {
            $posicion = array_search($columna, $nombres, true);

            if ($posicion === false) {
                $faltantes[] = $columna;
            } else {
                $indices[$columna] = $posicion;
            }
        }

        if ($faltantes !== []) {
            throw new RuntimeException("El archivo no tiene las columnas esperadas para el dataset «{$dataset}». Faltan: ".implode(', ', $faltantes).'.');
        }

        return $indices;
    }

    /**
     * Borra los registros de los años importados e inserta el nuevo agregado en lotes, en una sola transacción.
     *
     * @param  class-string<Model>  $modelo
     * @param  list<array<string, mixed>>  $registros
     * @param  list<int>  $anios
     */
    private function reemplazarRegistros(string $modelo, array $registros, array $anios): void
    {
        $ahora = now();

        DB::transaction(function () use ($modelo, $registros, $anios, $ahora): void {
            if ($anios !== []) {
                $modelo::query()->whereIn('anio', $anios)->delete();
            }

            foreach (array_chunk($registros, self::TAMANO_LOTE) as $lote) {
                $modelo::query()->insert(array_map(
                    fn (array $registro): array => $registro + ['created_at' => $ahora, 'updated_at' => $ahora],
                    $lote,
                ));
            }
        });
    }

    /**
     * @param  array<string, array<string, mixed>>  $agregado
     * @param  array{filas_leidas: int, filas_comuna: int}  $conteos
     * @return array{registros: list<array<string, mixed>>, anios: list<int>, filas_leidas: int, filas_comuna: int, omitidas: int}
     */
    private function resultado(array $agregado, array $conteos, int $omitidas): array
    {
        $registros = array_values($agregado);
        $anios = array_values(array_unique(array_column($registros, 'anio')));
        sort($anios);

        return [
            'registros' => $registros,
            'anios' => $anios,
            'filas_leidas' => $conteos['filas_leidas'],
            'filas_comuna' => $conteos['filas_comuna'],
            'omitidas' => $omitidas,
        ];
    }

    /**
     * @param  array{registros: list<array<string, mixed>>, anios: list<int>, filas_comuna: int, omitidas: int}  $resultado
     */
    private function mensajeCompletado(array $resultado): string
    {
        $mensaje = sprintf(
            'Se guardaron %s registros agregados a partir de %s filas de %s',
            number_format(count($resultado['registros']), 0, ',', '.'),
            number_format($resultado['filas_comuna'], 0, ',', '.'),
            config('deis.comuna.nombre'),
        );

        $mensaje .= match (count($resultado['anios'])) {
            0 => '.',
            1 => " (año {$resultado['anios'][0]}).",
            default => ' (años '.implode(', ', $resultado['anios']).').',
        };

        if ($resultado['omitidas'] > 0) {
            $mensaje .= ' Se omitieron '.number_format($resultado['omitidas'], 0, ',', '.').' filas con fecha, año o códigos inválidos.';
        }

        return $mensaje;
    }

    /**
     * Año y mes de una fecha `dd/mm/aaaa` o `aaaa-mm-dd`, o null si no es una fecha válida.
     *
     * @return array{anio: int, mes: int}|null
     */
    private function partesFecha(string $fecha): ?array
    {
        $fecha = trim($fecha);

        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})/', $fecha, $partes) === 1) {
            [, $dia, $mes, $anio] = $partes;
        } elseif (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})/', $fecha, $partes) === 1) {
            [, $anio, $mes, $dia] = $partes;
        } else {
            return null;
        }

        return checkdate((int) $mes, (int) $dia, (int) $anio) ? ['anio' => (int) $anio, 'mes' => (int) $mes] : null;
    }

    /**
     * Normaliza el sexo a "Mujer", "Hombre", etc.
     */
    private function sexo(string $valor): string
    {
        $valor = $this->texto($valor, 20);

        return $valor === '' ? self::SIN_INFORMACION : mb_convert_case(mb_strtolower($valor), MB_CASE_TITLE);
    }

    /**
     * Sector del establecimiento de egreso: público (pertenece al SNSS) o privado.
     */
    private function pertenencia(string $valor): string
    {
        $valor = mb_strtolower($this->aUtf8($valor));

        return match (true) {
            str_starts_with($valor, 'no ') => DeisEgreso::PERTENENCIA_PRIVADO,
            str_contains($valor, 'pertenec') => DeisEgreso::PERTENENCIA_PUBLICO,
            default => self::SIN_INFORMACION,
        };
    }

    /**
     * Grupo de edad de egresos tal como lo publica el DEIS, reparando las tildes que el archivo trae dañadas.
     */
    private function grupoEdadEgreso(string $valor): string
    {
        $valor = mb_strtolower($this->texto($valor, 30));

        return match (true) {
            $valor === '' => self::SIN_INFORMACION,
            str_starts_with($valor, 'menor') => DeisEgreso::GRUPOS_EDAD[0],
            preg_match('/^(\d+)\s*y\s*m/u', $valor, $partes) === 1 => "{$partes[1]} y más",
            default => $valor,
        };
    }

    /**
     * Texto en UTF-8, sin espacios en los extremos y recortado al largo de la columna.
     */
    private function texto(string $valor, int $largo): string
    {
        return mb_substr($this->aUtf8($valor), 0, $largo);
    }

    /**
     * Convierte desde Windows-1252 a UTF-8 cuando el valor no es UTF-8 válido.
     */
    private function aUtf8(string $valor): string
    {
        if (! mb_check_encoding($valor, 'UTF-8')) {
            $valor = mb_convert_encoding($valor, 'UTF-8', 'Windows-1252');
        }

        return trim($valor);
    }

    private function entero(string $valor): int
    {
        return max(0, (int) trim($valor));
    }

    /**
     * Comprueba la firma y la estructura de un archivo ZIP.
     */
    private function esZip(string $ruta): bool
    {
        if (! is_file($ruta) || filesize($ruta) < 22) {
            return false;
        }

        $firma = (string) file_get_contents($ruta, false, null, 0, 4);

        if (! in_array($firma, ["PK\x03\x04", "PK\x05\x06"], true)) {
            return false;
        }

        $zip = new ZipArchive;

        if ($zip->open($ruta, ZipArchive::RDONLY) !== true) {
            return false;
        }

        $zip->close();

        return true;
    }
}
