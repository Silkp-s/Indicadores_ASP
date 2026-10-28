<?php

use App\Jobs\ImportarDatasetDeis;
use App\Models\DeisAtencionUrgencia;
use App\Models\DeisDefuncion;
use App\Models\DeisEgreso;
use App\Models\DeisImportacion;
use App\Models\User;
use App\Services\DeisImportService;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->directorio = sys_get_temp_dir().DIRECTORY_SEPARATOR.'deis-tests-'.getmypid();
    File::ensureDirectoryExists($this->directorio);
    config(['deis.descargas.directorio' => $this->directorio.DIRECTORY_SEPARATOR.'descargas']);
});

afterEach(function () {
    File::deleteDirectory($this->directorio);
});

/**
 * Fila del CSV de atenciones de urgencia (SAR San Vicente, Talcahuano) con los cambios indicados.
 *
 * @param  array<string, string|int>  $cambios
 * @return array<string, string|int>
 */
function deisFilaUrgencia(array $cambios = []): array
{
    return array_merge([
        'IdEstablecimiento' => '19-802',
        'NEstablecimiento' => 'SAR San Vicente',
        'IdCausa' => 1,
        'GlosaCausa' => 'SECCIÓN 1. TOTAL ATENCIONES DE URGENCIA ',
        'Total' => 10,
        'Menores_1' => 1,
        'De_1_a_4' => 2,
        'De_5_a_14' => 2,
        'De_15_a_64' => 4,
        'De_65_y_mas' => 1,
        'fecha' => '01/01/2025',
        'semana' => 1,
        'GLOSATIPOESTABLECIMIENTO' => 'SAR',
        'GLOSATIPOATENCION' => 'Indiferenciado',
        'GlosaTipoCampana' => 'Ninguna',
        'CodigoRegion' => 8,
        'NombreRegion' => 'Del Bíobío',
        'CodigoDependencia' => 19,
        'NombreDependencia' => 'Servicio de Salud Talcahuano',
        'CodigoComuna' => 8110,
        'NombreComuna' => 'Talcahuano',
    ], $cambios);
}

/**
 * Fila del CSV de defunciones (Talcahuano) con los cambios indicados.
 *
 * @param  array<string, string|int>  $cambios
 * @return array<string, string|int>
 */
function deisFilaDefuncion(array $cambios = []): array
{
    return array_merge([
        'AÑO' => 2024,
        'FECHA_DEF' => '2024-03-05',
        'SEXO_NOMBRE' => 'Mujer',
        'EDAD_TIPO' => 1,
        'EDAD_CANT' => 70,
        'COD_COMUNA' => 8110,
        'COMUNA' => 'Talcahuano',
        'NOMBRE_REGION' => 'Del Biobío',
        'DIAG1' => 'I219',
        'CAPITULO_DIAG1' => 'I00-I99',
        'GLOSA_CAPITULO_DIAG1' => 'Enfermedades del sistema circulatorio ',
        'CODIGO_GRUPO_DIAG1' => 'I20-I25',
        'GLOSA_GRUPO_DIAG1' => 'Enfermedades isquémicas del corazón',
        'CODIGO_CATEGORIA_DIAG1' => 'I21',
        'GLOSA_CATEGORIA_DIAG1' => 'Infarto agudo del miocardio',
        'CODIGO_SUBCATEGORIA_DIAG1' => 'I219',
        'GLOSA_SUBCATEGORIA_DIAG1' => 'INFARTO AGUDO DEL MIOCARDIO, SIN OTRA ESPECIFICACIÓN',
        'DIAG2' => '',
        'CAPITULO_DIAG2' => '',
        'GLOSA_CAPITULO_DIAG2' => '',
        'CODIGO_GRUPO_DIAG2' => '',
        'GLOSA_GRUPO_DIAG2' => '',
        'CODIGO_CATEGORIA_DIAG2' => '',
        'GLOSA_CATEGORIA_DIAG2' => '',
        'CODIGO_SUBCATEGORIA_DIAG2' => '',
        'GLOSA_SUBCATEGORIA_DIAG2' => '',
        'LUGAR_DEFUNCION' => 'Hospital o Clínica',
    ], $cambios);
}

/**
 * Fila del CSV de egresos hospitalarios (residente de Talcahuano) con los cambios indicados.
 *
 * @param  array<string, string|int>  $cambios
 * @return array<string, string|int>
 */
function deisFilaEgreso(array $cambios = []): array
{
    return array_merge([
        'PERTENENCIA_ESTABLECIMIENTO_SALU' => 'Pertenecientes al Sistema Nacional de Servicios de Salud, SNSS',
        'SEXO' => 'MUJER',
        'GRUPO_EDAD' => '30 a 39',
        'ETNIA' => 'No se identifica con alguna etnia',
        'GLOSA_PAIS_ORIGEN' => 'Chileno',
        'COMUNA_RESIDENCIA' => '08110',
        'GLOSA_COMUNA_RESIDENCIA' => 'Talcahuano',
        'REGION_RESIDENCIA' => '08',
        'GLOSA_REGION_RESIDENCIA' => 'Del Bíobío',
        'PREVISION' => 1,
        'GLOSA_PREVISION' => 'FONASA',
        'ANO_EGRESO' => 2024,
        'DIAG1' => 'O829',
        'DIAG2' => '',
        'DIAS_ESTADA' => 2,
        'CONDICION_EGRESO' => 1,
    ], $cambios);
}

/**
 * Contenido CSV con separador ";", fin de línea CRLF y la codificación indicada (Windows-1252 por omisión).
 *
 * @param  list<array<string, string|int>>  $filas
 */
function deisCsv(array $filas, string $codificacion = 'Windows-1252'): string
{
    $lineas = [implode(';', array_keys($filas[0]))];

    foreach ($filas as $fila) {
        $lineas[] = implode(';', $fila);
    }

    return mb_convert_encoding(implode("\r\n", $lineas)."\r\n", $codificacion, 'UTF-8');
}

/**
 * ZIP como los del DEIS: un diccionario .xlsx seguido del CSV.
 */
function deisZip(string $directorio, string $nombreCsv, string $contenido): string
{
    $ruta = $directorio.DIRECTORY_SEPARATOR.Str::before($nombreCsv, '.').'.zip';

    $zip = new ZipArchive;
    $zip->open($ruta, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('Diccionario de datos.xlsx', 'diccionario');
    $zip->addFromString($nombreCsv, $contenido);
    $zip->close();

    return $ruta;
}

it('suma por semana las atenciones de urgencia de Talcahuano e ignora las de otras comunas', function () {
    $zip = deisZip($this->directorio, 'AtencionesUrgencia2025.csv', deisCsv([
        deisFilaUrgencia(['fecha' => '01/01/2025', 'Total' => 10, 'Menores_1' => 1, 'De_1_a_4' => 2, 'De_5_a_14' => 2, 'De_15_a_64' => 4, 'De_65_y_mas' => 1]),
        deisFilaUrgencia(['fecha' => '02/01/2025', 'Total' => 5, 'Menores_1' => 0, 'De_1_a_4' => 1, 'De_5_a_14' => 1, 'De_15_a_64' => 2, 'De_65_y_mas' => 1]),
        deisFilaUrgencia(['fecha' => '05/01/2025', 'semana' => 2, 'Total' => 7]),
        deisFilaUrgencia(['IdEstablecimiento' => '18-100', 'NEstablecimiento' => 'Hospital Regional de Concepción', 'Total' => 8110, 'CodigoComuna' => 8101, 'NombreComuna' => 'Concepción']),
    ]));

    app(DeisImportService::class)->importar('urgencias', $zip);

    expect(DeisAtencionUrgencia::count())->toBe(2);
    $this->assertDatabaseHas('deis_atenciones_urgencia', [
        'codigo_establecimiento' => '19-802',
        'nombre_establecimiento' => 'SAR San Vicente',
        'tipo_establecimiento' => 'SAR',
        'id_causa' => 1,
        'glosa_causa' => 'SECCIÓN 1. TOTAL ATENCIONES DE URGENCIA',
        'anio' => 2025,
        'semana' => 1,
        'total' => 15,
        'menores_1' => 1,
        'de_1_a_4' => 3,
        'de_5_a_14' => 3,
        'de_15_a_64' => 6,
        'de_65_y_mas' => 2,
    ]);
    $this->assertDatabaseHas('deis_atenciones_urgencia', ['codigo_establecimiento' => '19-802', 'semana' => 2, 'total' => 7]);
});

it('lee campos entre comillas que contienen punto y coma y saltos de línea', function () {
    $zip = deisZip($this->directorio, 'AtencionesUrgencia2025.csv', deisCsv([
        deisFilaUrgencia(['NEstablecimiento' => "\"SAR \"\"San Vicente\"\"; sector\r\nnorte\""]),
        deisFilaUrgencia(['IdEstablecimiento' => '19-803', 'NEstablecimiento' => 'SAPU Paulina Avendaño Pereda', 'GLOSATIPOESTABLECIMIENTO' => 'SAPU']),
    ]));

    $importacion = app(DeisImportService::class)->importar('urgencias', $zip);

    expect($importacion->filas_leidas)->toBe(2);
    $this->assertDatabaseHas('deis_atenciones_urgencia', ['codigo_establecimiento' => '19-802', 'nombre_establecimiento' => "SAR \"San Vicente\"; sector\r\nnorte"]);
    $this->assertDatabaseHas('deis_atenciones_urgencia', ['codigo_establecimiento' => '19-803', 'nombre_establecimiento' => 'SAPU Paulina Avendaño Pereda']);
});

it('agrupa las defunciones por año, mes de defunción, sexo, grupo de edad y capítulo', function () {
    $zip = deisZip($this->directorio, 'DEFUNCIONES_FUENTE_DEIS_2024_2026.csv', deisCsv([
        deisFilaDefuncion(['FECHA_DEF' => '2024-03-05', 'EDAD_CANT' => 70]),
        deisFilaDefuncion(['FECHA_DEF' => '2024-03-20', 'EDAD_CANT' => 75]),
        deisFilaDefuncion(['FECHA_DEF' => '2024-04-01', 'SEXO_NOMBRE' => 'Hombre', 'EDAD_TIPO' => 3, 'EDAD_CANT' => 2, 'CAPITULO_DIAG1' => 'P00-P96', 'GLOSA_CAPITULO_DIAG1' => 'Ciertas afecciones originadas en el período perinatal']),
        deisFilaDefuncion(['AÑO' => 2025, 'FECHA_DEF' => '2025-01-10', 'SEXO_NOMBRE' => 'Hombre', 'EDAD_CANT' => 85, 'CAPITULO_DIAG1' => 'C00-D48', 'GLOSA_CAPITULO_DIAG1' => 'Tumores [Neoplasias]']),
        deisFilaDefuncion(['COD_COMUNA' => 8101, 'COMUNA' => 'Concepción']),
    ]));

    app(DeisImportService::class)->importar('defunciones', $zip);

    expect(DeisDefuncion::count())->toBe(3);
    $this->assertDatabaseHas('deis_defunciones', ['anio' => 2024, 'mes' => 3, 'sexo' => 'Mujer', 'grupo_edad' => '65 a 79', 'capitulo_codigo' => 'I00-I99', 'capitulo_glosa' => 'Enfermedades del sistema circulatorio', 'total' => 2]);
    $this->assertDatabaseHas('deis_defunciones', ['anio' => 2024, 'mes' => 4, 'sexo' => 'Hombre', 'grupo_edad' => 'Menor de 1 año', 'capitulo_codigo' => 'P00-P96', 'total' => 1]);
    $this->assertDatabaseHas('deis_defunciones', ['anio' => 2025, 'mes' => 1, 'sexo' => 'Hombre', 'grupo_edad' => '80 y más', 'capitulo_codigo' => 'C00-D48', 'capitulo_glosa' => 'Tumores [Neoplasias]', 'total' => 1]);
});

it('asigna el grupo de edad de la defunción según el tipo y la cantidad de edad', function (string $tipo, string $cantidad, string $grupo) {
    expect(DeisImportService::grupoEdadDefuncion($tipo, $cantidad))->toBe($grupo);
})->with([
    'horas' => ['4', '5', 'Menor de 1 año'],
    'días' => ['3', '20', 'Menor de 1 año'],
    'meses' => ['2', '11', 'Menor de 1 año'],
    '0 años' => ['1', '0', 'Menor de 1 año'],
    '1 año' => ['1', '1', '1 a 14'],
    '14 años' => ['1', '14', '1 a 14'],
    '15 años' => ['1', '15', '15 a 44'],
    '44 años' => ['1', '44', '15 a 44'],
    '45 años' => ['1', '45', '45 a 64'],
    '64 años' => ['1', '64', '45 a 64'],
    '65 años' => ['1', '65', '65 a 79'],
    '79 años' => ['1', '79', '65 a 79'],
    '80 años' => ['1', '80', '80 y más'],
    '104 años' => ['1', '104', '80 y más'],
    'tipo desconocido' => ['9', '999', 'Sin información'],
    'edad vacía' => ['1', '', 'Sin información'],
]);

it('deriva el capítulo CIE-10, el sector, el sexo y la condición de los egresos de residentes de Talcahuano', function () {
    $zip = deisZip($this->directorio, 'EGR_DATOS_ABIERTO_2024.csv', deisCsv([
        deisFilaEgreso(['DIAG1' => 'O829', 'DIAS_ESTADA' => 2]),
        deisFilaEgreso(['DIAG1' => 'O800', 'DIAS_ESTADA' => 3]),
        deisFilaEgreso(['PERTENENCIA_ESTABLECIMIENTO_SALU' => 'No Pertenecientes al Sistema Nacional de Servicios de Salud, SNSS', 'SEXO' => 'HOMBRE', 'GRUPO_EDAD' => '90 y más', 'DIAG1' => 'C61X', 'DIAS_ESTADA' => 10, 'CONDICION_EGRESO' => 2]),
        deisFilaEgreso(['COMUNA_RESIDENCIA' => '08101', 'GLOSA_COMUNA_RESIDENCIA' => 'Concepción']),
    ]));

    app(DeisImportService::class)->importar('egresos', $zip);

    expect(DeisEgreso::count())->toBe(2);
    $this->assertDatabaseHas('deis_egresos', [
        'anio' => 2024,
        'pertenencia' => 'Público (SNSS)',
        'sexo' => 'Mujer',
        'grupo_edad' => '30 a 39',
        'capitulo_codigo' => 'O00-O99',
        'capitulo_glosa' => 'Embarazo, parto y puerperio',
        'condicion' => 'Vivo',
        'egresos' => 2,
        'dias_estada' => 5,
    ]);
    $this->assertDatabaseHas('deis_egresos', [
        'anio' => 2024,
        'pertenencia' => 'Privado',
        'sexo' => 'Hombre',
        'grupo_edad' => '90 y más',
        'capitulo_codigo' => 'C00-D48',
        'capitulo_glosa' => 'Tumores [Neoplasias]',
        'condicion' => 'Fallecido',
        'egresos' => 1,
        'dias_estada' => 10,
    ]);
});

it('repara los grupos de edad de egresos que el DEIS publica con tildes dañadas', function () {
    $zip = deisZip($this->directorio, 'EGR_DATOS_ABIERTO_2024.csv', deisCsv([
        deisFilaEgreso(['GRUPO_EDAD' => "menor de un a\u{FFFD}o"]),
        deisFilaEgreso(['GRUPO_EDAD' => "90 y m\u{FFFD}s"]),
    ], 'UTF-8'));

    app(DeisImportService::class)->importar('egresos', $zip);

    expect(DeisEgreso::query()->orderBy('grupo_edad')->pluck('grupo_edad')->all())->toBe(['90 y más', 'menor de un año']);
});

it('deriva el capítulo CIE-10 a partir del código del diagnóstico', function (string $diagnostico, string $capitulo) {
    expect(DeisImportService::capituloCie10($diagnostico)['codigo'])->toBe($capitulo);
})->with([
    'embarazo' => ['O829', 'O00-O99'],
    'tumor con X final' => ['C61X', 'C00-D48'],
    'último tumor' => ['D489', 'C00-D48'],
    'primera enfermedad de la sangre' => ['D500', 'D50-D89'],
    'último código del ojo' => ['H590', 'H00-H59'],
    'primer código del oído' => ['H600', 'H60-H95'],
    'traumatismo con T' => ['T983', 'S00-T98'],
    'causa externa' => ['X599', 'V01-Y98'],
    'propósitos especiales' => ['U071', 'U00-U99'],
    'minúsculas con punto' => ['z00.0', 'Z00-Z99'],
    'categoría fuera de capítulo' => ['D490', 'S/C'],
    'código no CIE-10' => ['123', 'S/C'],
    'vacío' => ['', 'S/C'],
]);

it('registra en la bitácora la importación completada con sus conteos desde un CSV plano', function () {
    $this->freezeSecond();
    $csv = $this->directorio.DIRECTORY_SEPARATOR.'defunciones.csv';
    File::put($csv, deisCsv([
        deisFilaDefuncion(['FECHA_DEF' => '2024-03-05']),
        deisFilaDefuncion(['FECHA_DEF' => '2024-03-06']),
        deisFilaDefuncion(['FECHA_DEF' => '2024-05-06']),
        deisFilaDefuncion(['COD_COMUNA' => 8101]),
        deisFilaDefuncion(['COD_COMUNA' => 8102]),
    ]));

    $importacion = app(DeisImportService::class)->importar('defunciones', $csv);

    expect($importacion->fresh())
        ->dataset->toBe('defunciones')
        ->origen->toBe($csv)
        ->estado->toBe(DeisImportacion::ESTADO_COMPLETADO)
        ->filas_leidas->toBe(5)
        ->filas_comuna->toBe(3)
        ->registros_guardados->toBe(2)
        ->mensaje->toBe('Se guardaron 2 registros agregados a partir de 3 filas de Talcahuano (año 2024).')
        ->iniciado_en->toEqual(now())
        ->finalizado_en->toEqual(now());
});

it('no duplica registros al importar dos veces el mismo archivo', function () {
    $zip = deisZip($this->directorio, 'EGR_DATOS_ABIERTO_2024.csv', deisCsv([
        deisFilaEgreso(),
        deisFilaEgreso(),
        deisFilaEgreso(['CONDICION_EGRESO' => 2]),
    ]));
    $servicio = app(DeisImportService::class);

    $servicio->importar('egresos', $zip);
    $servicio->importar('egresos', $zip);

    expect(DeisEgreso::count())->toBe(2)
        ->and((int) DeisEgreso::sum('egresos'))->toBe(3);
    $this->assertDatabaseCount('deis_importaciones', 2);
});

it('reemplaza solo los registros de los años presentes en el archivo', function () {
    $anterior = DeisDefuncion::factory()->create(['anio' => 2023]);
    $reemplazada = DeisDefuncion::factory()->create(['anio' => 2024]);
    $zip = deisZip($this->directorio, 'DEFUNCIONES_FUENTE_DEIS_2024_2026.csv', deisCsv([deisFilaDefuncion()]));

    app(DeisImportService::class)->importar('defunciones', $zip);

    $this->assertModelExists($anterior);
    $this->assertModelMissing($reemplazada);
    $this->assertDatabaseHas('deis_defunciones', ['anio' => 2024, 'mes' => 3, 'grupo_edad' => '65 a 79', 'total' => 1]);
});

it('marca la importación como fallida y conserva los datos previos si faltan columnas', function () {
    $existentes = DeisAtencionUrgencia::factory()->count(2)->create();
    $csv = $this->directorio.DIRECTORY_SEPARATOR.'urgencias.csv';
    File::put($csv, deisCsv([Arr::except(deisFilaUrgencia(), ['Total', 'semana'])]));

    expect(fn () => app(DeisImportService::class)->importar('urgencias', $csv))
        ->toThrow(RuntimeException::class, 'El archivo no tiene las columnas esperadas para el dataset «urgencias». Faltan: TOTAL, SEMANA.');

    expect(DeisImportacion::sole())
        ->estado->toBe(DeisImportacion::ESTADO_FALLIDO)
        ->mensaje->toBe('El archivo no tiene las columnas esperadas para el dataset «urgencias». Faltan: TOTAL, SEMANA.')
        ->finalizado_en->not->toBeNull();
    $existentes->each(fn (DeisAtencionUrgencia $atencion) => $this->assertModelExists($atencion));
});

it('marca la importación como fallida si el CSV dentro del ZIP está incompleto', function () {
    $ruta = $this->directorio.DIRECTORY_SEPARATOR.'incompleto.zip';
    $zip = new ZipArchive;
    $zip->open($ruta, ZipArchive::CREATE);
    $zip->addFromString('EGR_DATOS_ABIERTO_2024.csv', deisCsv([deisFilaEgreso(), deisFilaEgreso()]));
    $zip->close();
    $bytes = File::get($ruta);
    $local = strpos($bytes, "PK\x03\x04");
    $central = strpos($bytes, "PK\x01\x02");
    $tamanoReal = unpack('V', substr($bytes, $central + 24, 4))[1];
    $bytes = substr_replace($bytes, pack('V', $tamanoReal * 2), $local + 22, 4);
    File::put($ruta, substr_replace($bytes, pack('V', $tamanoReal * 2), $central + 24, 4));

    expect(fn () => app(DeisImportService::class)->importar('egresos', $ruta))
        ->toThrow(RuntimeException::class, 'El CSV dentro del ZIP está incompleto o dañado');

    expect(DeisImportacion::sole()->estado)->toBe(DeisImportacion::ESTADO_FALLIDO);
    $this->assertDatabaseCount('deis_egresos', 0);
});

it('marca la importación como fallida si el CSV dentro del ZIP no coincide con su CRC', function () {
    $existentes = DeisDefuncion::factory()->count(2)->create(['anio' => 2024]);
    $ruta = $this->directorio.DIRECTORY_SEPARATOR.'crc.zip';
    $zip = new ZipArchive;
    $zip->open($ruta, ZipArchive::CREATE);
    $zip->addFromString('DEFUNCIONES.csv', deisCsv([deisFilaDefuncion(), deisFilaDefuncion()]));
    $zip->setCompressionName('DEFUNCIONES.csv', ZipArchive::CM_STORE);
    $zip->close();
    File::put($ruta, Str::replaceFirst('2024-03-05', '2024-04-05', File::get($ruta)));

    expect(fn () => app(DeisImportService::class)->importar('defunciones', $ruta))
        ->toThrow(RuntimeException::class, 'El CSV dentro del ZIP está dañado: su CRC no coincide con el declarado en el ZIP.');

    expect(DeisImportacion::sole()->estado)->toBe(DeisImportacion::ESTADO_FALLIDO);
    $existentes->each(fn (DeisDefuncion $defuncion) => $this->assertModelExists($defuncion));
});

it('marca la importación como fallida si el CSV tiene una fila anormalmente larga', function (string $fila) {
    $csv = $this->directorio.DIRECTORY_SEPARATOR.'egresos.csv';
    File::put($csv, deisCsv([deisFilaEgreso()]).$fila."\r\n");

    expect(fn () => app(DeisImportService::class)->importar('egresos', $csv))
        ->toThrow(RuntimeException::class, 'el archivo está dañado');

    expect(DeisImportacion::sole()->estado)->toBe(DeisImportacion::ESTADO_FALLIDO);
    $this->assertDatabaseCount('deis_egresos', 0);
})->with([
    'sin saltos de línea' => [str_repeat('8110;', 20000)],
    'con comillas sin cerrar' => ['"8110'.str_repeat("\r\n8110;8110", 10000)],
]);

it('no importa un dataset que ya tiene otra importación en curso', function () {
    $csv = $this->directorio.DIRECTORY_SEPARATOR.'egresos.csv';
    File::put($csv, deisCsv([deisFilaEgreso()]));
    $candado = Cache::lock('deis-importar-egresos', 60);
    $candado->get();

    expect(fn () => app(DeisImportService::class)->importar('egresos', $csv))
        ->toThrow(RuntimeException::class, 'Ya hay otra importación del dataset «egresos» en curso; espere a que termine.');

    expect(DeisImportacion::sole()->estado)->toBe(DeisImportacion::ESTADO_FALLIDO);
    $this->assertDatabaseCount('deis_egresos', 0);

    $candado->release();
    app(DeisImportService::class)->importar('egresos', $csv);

    $this->assertDatabaseCount('deis_egresos', 1);
});

it('deja el retry_after de la cola por sobre el tiempo límite del job', function () {
    expect(config('queue.connections.database.retry_after'))
        ->toBeGreaterThan((new ImportarDatasetDeis('urgencias', 1))->timeout);
});

it('descarga el ZIP desde la URL configurada cuando no se indica un archivo', function () {
    Http::preventStrayRequests();
    $url = 'https://repositoriodeis.minsal.cl/DatosAbiertos/EGRESOS/EGRESOS_2024.zip';
    config(['deis.datasets.egresos.url' => $url]);
    $zip = deisZip($this->directorio, 'EGR_DATOS_ABIERTO_2024.csv', deisCsv([deisFilaEgreso()]));
    Http::fake([$url => Http::response(File::get($zip))]);

    $importacion = app(DeisImportService::class)->importar('egresos');

    Http::assertSent(fn (Request $request) => $request->url() === $url);
    expect($importacion)
        ->origen->toBe($url)
        ->estado->toBe(DeisImportacion::ESTADO_COMPLETADO)
        ->registros_guardados->toBe(1);
    expect(File::exists(config('deis.descargas.directorio').DIRECTORY_SEPARATOR.'egresos.zip'))->toBeTrue();
    $this->assertDatabaseHas('deis_egresos', ['anio' => 2024, 'capitulo_codigo' => 'O00-O99', 'egresos' => 1]);
});

it('marca como fallida una descarga que no entrega un ZIP válido', function (int $estado, string $cuerpo, string $mensaje) {
    Http::preventStrayRequests();
    $url = 'https://repositoriodeis.minsal.cl/DatosAbiertos/VITALES/DEFUNCIONES_FUENTE_DEIS_2024_2026_27102026.zip';
    config(['deis.datasets.defunciones.url' => $url]);
    Http::fake([$url => Http::response($cuerpo, $estado)]);

    expect(fn () => app(DeisImportService::class)->importar('defunciones'))
        ->toThrow(RuntimeException::class, $mensaje);

    expect(DeisImportacion::sole())
        ->estado->toBe(DeisImportacion::ESTADO_FALLIDO)
        ->mensaje->toContain($mensaje);
    expect(File::files(config('deis.descargas.directorio')))->toBeEmpty();
})->with([
    'respuesta 404' => [404, 'Not Found', 'falló (HTTP 404)'],
    'página HTML en lugar del ZIP' => [200, '<html><body>Mantención</body></html>', 'no es un ZIP válido'],
]);

it('importa un dataset con el comando usando --archivo', function () {
    $zip = deisZip($this->directorio, 'EGR_DATOS_ABIERTO_2024.csv', deisCsv([deisFilaEgreso(), deisFilaEgreso(['COMUNA_RESIDENCIA' => '08101'])]));

    $this->artisan('deis:importar', ['dataset' => 'egresos', '--archivo' => $zip])
        ->expectsOutputToContain('Filas de Talcahuano')
        ->expectsOutputToContain('Registros guardados')
        ->assertSuccessful();

    $this->assertDatabaseHas('deis_egresos', ['anio' => 2024, 'pertenencia' => 'Público (SNSS)', 'egresos' => 1]);
    $this->assertDatabaseHas('deis_importaciones', ['dataset' => 'egresos', 'origen' => $zip, 'estado' => 'completado', 'filas_leidas' => 2, 'filas_comuna' => 1]);
});

it('rechaza el comando con argumentos inválidos sin importar nada', function (array $argumentos, string $mensaje) {
    $this->artisan('deis:importar', $argumentos)
        ->expectsOutputToContain($mensaje)
        ->assertExitCode(Command::INVALID);

    $this->assertDatabaseCount('deis_importaciones', 0);
})->with([
    'dataset desconocido' => [['dataset' => 'nacimientos'], 'Dataset no válido'],
    '--archivo con todos los datasets' => [['--archivo' => 'egresos.zip'], 'solo se pueden usar con un dataset específico'],
    '--archivo y --url a la vez' => [['dataset' => 'egresos', '--archivo' => 'egresos.zip', '--url' => 'https://example.com/egresos.zip'], 'no ambas'],
]);

it('termina el comando con error cuando la importación falla', function () {
    $this->artisan('deis:importar', ['dataset' => 'defunciones', '--archivo' => $this->directorio.DIRECTORY_SEPARATOR.'no-existe.zip'])
        ->expectsOutputToContain('No se encontró el archivo')
        ->assertFailed();

    expect(DeisImportacion::sole()->estado)->toBe(DeisImportacion::ESTADO_FALLIDO);
});

it('completa la importación pendiente al ejecutar el job', function () {
    Http::preventStrayRequests();
    $url = 'https://repositoriodeis.minsal.cl/DatosAbiertos/EGRESOS/EGRESOS_2024.zip';
    config(['deis.datasets.egresos.url' => $url]);
    Http::fake([$url => Http::response(File::get(deisZip($this->directorio, 'EGR_DATOS_ABIERTO_2024.csv', deisCsv([deisFilaEgreso()]))))]);
    $usuario = User::factory()->create();
    $importacion = DeisImportacion::factory()->delDataset('egresos')->pendiente()->for($usuario)->create();

    ImportarDatasetDeis::dispatchSync('egresos', $importacion->id);

    expect($importacion->fresh())
        ->estado->toBe(DeisImportacion::ESTADO_COMPLETADO)
        ->registros_guardados->toBe(1)
        ->user_id->toBe($usuario->id);
    $this->assertDatabaseCount('deis_importaciones', 1);
});

it('marca como fallida la importación en curso cuando el job falla', function () {
    $importacion = DeisImportacion::factory()->delDataset('urgencias')->procesando()->create();

    (new ImportarDatasetDeis('urgencias', $importacion->id))->failed(new RuntimeException('Se agotó el tiempo de ejecución.'));

    expect($importacion->fresh())
        ->estado->toBe(DeisImportacion::ESTADO_FALLIDO)
        ->mensaje->toBe('Se agotó el tiempo de ejecución.')
        ->finalizado_en->not->toBeNull();
});
