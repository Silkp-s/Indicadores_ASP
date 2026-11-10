<?php

use App\Jobs\ImportarDatasetDeis;
use App\Models\DeisAtencionUrgencia;
use App\Models\DeisDefuncion;
use App\Models\DeisEgreso;
use App\Models\DeisImportacion;
use App\Models\Establecimiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Usuario']);
    Role::firstOrCreate(['name' => 'Admin']);
    // Abrir el panel encola la carga inicial de los datasets vacíos; en los tests nunca se ejecuta la importación real.
    Queue::fake([ImportarDatasetDeis::class]);
});

/** Términos técnicos que ningún usuario debe ver en el panel. */
const TERMINOS_TECNICOS = [
    'composer', 'artisan', 'queue', 'worker', 'cola de trabajos', 'En cola', 'CSV', 'ZIP', '.zip', 'DEIS_URL', 'HTTP 404', 'CRC', 'streaming', 'seeder', 'deis:importar',
];

/**
 * Comprueba que la respuesta no contiene ningún término técnico.
 */
function sinJergaTecnica(TestResponse $respuesta): TestResponse
{
    foreach (TERMINOS_TECNICOS as $termino) {
        $respuesta->assertDontSee($termino, false);
    }

    return $respuesta;
}

/**
 * Usuario autenticado con el rol indicado.
 */
function usuarioDeis(string $rol = 'Usuario'): User
{
    return User::factory()->create()->assignRole($rol);
}

/**
 * Atenciones de urgencia 2025: SAR San Vicente (19-802, par del CESFAM 119302) en las semanas 1 y 2
 * y Hospital Las Higueras (19-100, sin par municipal) en la semana 1, más causas respiratorias del SAR.
 */
function crearAtencionesUrgencia(): void
{
    Establecimiento::factory()->create(['nombre' => 'CESFAM San Vicente', 'codigo_deis' => '119302', 'tipo' => 'CESFAM', 'poblacion_inscrita' => 28500]);

    DeisAtencionUrgencia::factory()->establecimiento('19-802')->create(['semana' => 1, 'total' => 1200]);
    DeisAtencionUrgencia::factory()->establecimiento('19-802')->create(['semana' => 2, 'total' => 1500]);
    DeisAtencionUrgencia::factory()->establecimiento('19-100')->create(['semana' => 1, 'total' => 200]);
    DeisAtencionUrgencia::factory()->establecimiento('19-802')->causa(DeisAtencionUrgencia::CAUSA_RESPIRATORIA)->create(['semana' => 1, 'total' => 290]);
}

test('un invitado es redirigido al login', function (string $metodo, string $ruta) {
    $this->call($metodo, route($ruta))
        ->assertRedirect(route('login'));
})->with([
    'ver el módulo' => ['GET', 'inteligencia-deis.index'],
    'actualizar los datos' => ['POST', 'inteligencia-deis.actualizar'],
]);

test('un usuario ve la pestaña de tableros con el primer tablero del SST incrustado', function () {
    $this->actingAs(usuarioDeis())
        ->get(route('inteligencia-deis.index'))
        ->assertSee('src="'.e(config('deis.tableros.0.url')).'"', false)
        ->assertSee('title="Tablero Power BI: Anuario de Producción 2020-2025"', false)
        ->assertSee('loading="lazy"', false)
        ->assertSee('allowfullscreen', false)
        ->assertSeeInOrder([
            'Tableros disponibles del SST',
            'Anuario de Producción 2020-2025',
            'Consolidador REM',
            'Poblaciones Servicio de Salud Talcahuano',
            'ENO 2021-2025',
            'Defunciones y Tasas de Mortalidad SST',
            'Nacimientos y Tasas de Natalidad SST',
            'Vacunas Campañas',
            'Consultas de Urgencia (2014-2025)',
        ]);
});

test('el parámetro tablero muestra el tablero pedido y su enlace a Power BI', function () {
    $this->actingAs(usuarioDeis())
        ->get(route('inteligencia-deis.index', ['tab' => 'tableros', 'tablero' => 'eno']))
        ->assertSee('src="'.e(config('deis.tableros.3.url')).'"', false)
        ->assertSee('href="'.e(config('deis.tableros.3.url')).'"', false)
        ->assertDontSee(config('deis.tableros.0.url'));
});

test('un tablero o una pestaña desconocidos muestran el primer tablero', function (array $parametros) {
    $this->actingAs(usuarioDeis())
        ->get(route('inteligencia-deis.index', $parametros))
        ->assertSee('src="'.e(config('deis.tableros.0.url')).'"', false);
})->with([
    'tablero inexistente' => [['tab' => 'tableros', 'tablero' => 'https://evil.example.com']],
    'pestaña inexistente' => [['tab' => 'otra']],
    'tablero como arreglo' => [['tablero' => ['eno']]],
]);

test('la pestaña de urgencias muestra los KPIs del año más reciente para el total de atenciones', function () {
    crearAtencionesUrgencia();

    $this->actingAs(usuarioDeis())
        ->get(route('inteligencia-deis.index', ['tab' => 'urgencias']))
        ->assertSeeInOrder([
            'Total de atenciones', '2.900',
            'Promedio semanal', '1.450',
            'Semana con más atenciones', 'Semana 2', '1.500 atenciones en la semana',
            '% respiratorio', '10,0%',
        ]);
});

test('la pestaña de urgencias contrasta cada establecimiento con su CESFAM y la tasa por 1.000 inscritos', function () {
    crearAtencionesUrgencia();

    $this->actingAs(usuarioDeis())
        ->get(route('inteligencia-deis.index', ['tab' => 'urgencias']))
        ->assertSeeInOrder([
            'Contraste con la red municipal',
            'Hospital Las Higueras (Talcahuano)', 'Red SST', '—', '200', '—',
            'SAR San Vicente', 'CESFAM San Vicente', '119302', '28.500', '2.700', '94,7',
            'Tasa aprox., sobre población inscrita local',
        ]);
});

test('la pestaña de urgencias muestra «—» cuando el CESFAM asociado no tiene población inscrita', function () {
    Establecimiento::factory()->create(['codigo_deis' => '119302', 'poblacion_inscrita' => 0]);
    DeisAtencionUrgencia::factory()->establecimiento('19-802')->create(['semana' => 1, 'total' => 1200]);

    $this->actingAs(usuarioDeis())
        ->get(route('inteligencia-deis.index', ['tab' => 'urgencias']))
        ->assertSeeInOrder(['Contraste con la red municipal', 'SAR San Vicente', '119302', '—', '1.200', '—', 'Tasa aprox.']);
});

test('la pestaña de urgencias filtra por establecimiento y causa', function () {
    crearAtencionesUrgencia();

    $this->actingAs(usuarioDeis())
        ->get(route('inteligencia-deis.index', ['tab' => 'urgencias', 'establecimiento' => '19-802', 'causa' => DeisAtencionUrgencia::CAUSA_RESPIRATORIA]))
        ->assertSeeInOrder(['Total de atenciones', '290', '% respiratorio', '10,7%'])
        ->assertSeeInOrder(['Contraste con la red municipal', 'SAR San Vicente', '290', '10,2'])
        ->assertDontSee('Red SST');
});

test('la pestaña de urgencias ignora filtros no válidos y usa los predeterminados', function () {
    crearAtencionesUrgencia();
    DeisAtencionUrgencia::factory()->establecimiento('19-802')->create(['anio' => 2024, 'semana' => 1, 'total' => 9999]);

    $this->actingAs(usuarioDeis())
        ->get(route('inteligencia-deis.index', ['tab' => 'urgencias', 'anio' => '1990', 'causa' => '2abc', 'establecimiento' => 'XX']))
        ->assertSeeInOrder(['Total de atenciones', '2.900', 'Causa 1 · año 2025 · Todos los establecimientos']);
});

test('la pestaña de urgencias escapa los textos que vienen del CSV del DEIS', function () {
    DeisAtencionUrgencia::factory()->establecimiento('19-100')->create([
        'semana' => 1,
        'nombre_establecimiento' => "<script>alert('xss')</script>",
    ]);

    $this->actingAs(usuarioDeis())
        ->get(route('inteligencia-deis.index', ['tab' => 'urgencias']))
        ->assertSee("<script>alert('xss')</script>")
        ->assertDontSee("<script>alert('xss')</script>", false);
});

test('la pestaña de defunciones muestra los KPIs del año más reciente y la tasa sobre la red municipal activa', function () {
    $this->travelTo('2026-10-27 12:00:00');
    Establecimiento::factory()->create(['poblacion_inscrita' => 28500]);
    Establecimiento::factory()->create(['poblacion_inscrita' => 26000]);
    Establecimiento::factory()->inactivo()->create(['poblacion_inscrita' => 45600]);
    DeisDefuncion::factory()->create(['anio' => 2025, 'mes' => 3, 'sexo' => 'Mujer', 'grupo_edad' => '65 a 79', 'capitulo_codigo' => 'I00-I99', 'capitulo_glosa' => 'Enfermedades del sistema circulatorio', 'total' => 30]);
    DeisDefuncion::factory()->create(['anio' => 2025, 'mes' => 3, 'sexo' => 'Hombre', 'grupo_edad' => '45 a 64', 'capitulo_codigo' => 'C00-D48', 'capitulo_glosa' => 'Tumores [Neoplasias]', 'total' => 20]);
    DeisDefuncion::factory()->create(['anio' => 2025, 'mes' => 4, 'sexo' => 'Mujer', 'grupo_edad' => '80 y más', 'capitulo_codigo' => 'J00-J99', 'capitulo_glosa' => 'Enfermedades del sistema respiratorio', 'total' => 10]);
    DeisDefuncion::factory()->create(['anio' => 2024, 'mes' => 1, 'sexo' => 'Hombre', 'grupo_edad' => '80 y más', 'capitulo_codigo' => 'I00-I99', 'capitulo_glosa' => 'Enfermedades del sistema circulatorio', 'total' => 99]);

    $this->actingAs(usuarioDeis())
        ->get(route('inteligencia-deis.index', ['tab' => 'defunciones']))
        ->assertSeeInOrder([
            'Total de defunciones', '60',
            'Mujeres / Hombres', '40 / 20',
            '65 años y más', '66,7%',
            'Tasa por 1.000 inscritos', '1,1',
        ])
        ->assertSeeInOrder(['Contraste con la red municipal', 'Defunciones DEIS 2025', '60', 'Población inscrita local', '2 establecimientos activos', '54.500', 'Defunciones por 1.000 inscritos', '1,1'])
        ->assertSeeInOrder(['Detalle de las principales causas', 'I00-I99', 'Enfermedades del sistema circulatorio', '30', '50,0%', 'C00-D48', 'J00-J99'])
        ->assertDontSee('Cifras preliminares');
});

test('la pestaña de defunciones marca como preliminares las cifras de 2026', function () {
    $this->travelTo('2026-10-27 12:00:00');
    DeisDefuncion::factory()->create(['anio' => 2026, 'mes' => 10]);

    $this->actingAs(usuarioDeis())
        ->get(route('inteligencia-deis.index', ['tab' => 'defunciones']))
        ->assertSee('Cifras preliminares 2026')
        ->assertSee('(enero a octubre)');
});

test('la pestaña de defunciones muestra «—» en la tasa si no hay población inscrita activa', function () {
    DeisDefuncion::factory()->create(['anio' => 2025, 'total' => 5]);

    $this->actingAs(usuarioDeis())
        ->get(route('inteligencia-deis.index', ['tab' => 'defunciones']))
        ->assertSeeInOrder(['Tasa por 1.000 inscritos', '—', 'Aprox., sobre población inscrita local']);
});

test('la pestaña de egresos muestra los KPIs del año y la tasa por 1.000 inscritos', function () {
    Establecimiento::factory()->create(['poblacion_inscrita' => 20000]);
    DeisEgreso::factory()->create(['anio' => 2024, 'pertenencia' => DeisEgreso::PERTENENCIA_PUBLICO, 'sexo' => 'Mujer', 'grupo_edad' => '30 a 39', 'capitulo_codigo' => 'K00-K93', 'capitulo_glosa' => 'Enfermedades del sistema digestivo', 'condicion' => DeisEgreso::CONDICION_VIVO, 'egresos' => 30, 'dias_estada' => 150]);
    DeisEgreso::factory()->create(['anio' => 2024, 'pertenencia' => DeisEgreso::PERTENENCIA_PRIVADO, 'sexo' => 'Hombre', 'grupo_edad' => '70 a 79', 'capitulo_codigo' => 'I00-I99', 'capitulo_glosa' => 'Enfermedades del sistema circulatorio', 'condicion' => DeisEgreso::CONDICION_FALLECIDO, 'egresos' => 10, 'dias_estada' => 100]);

    $this->actingAs(usuarioDeis())
        ->get(route('inteligencia-deis.index', ['tab' => 'egresos']))
        ->assertSeeInOrder([
            'Total de egresos', '40',
            'Promedio de días de estada', '6,3',
            '% fallecidos', '25,0%',
            '% sector público (SNSS)', '75,0%',
        ])
        ->assertSeeInOrder(['Contraste con la red municipal', 'Egresos DEIS 2024', '40', '20.000', 'Egresos por 1.000 inscritos', '2,0'])
        ->assertSeeInOrder(['Detalle de los principales diagnósticos', 'K00-K93', '30', '75,0%', '5,0', '0', 'I00-I99', '10', '25,0%', '10,0', '10']);
});

test('una pestaña sin datos encola la carga inicial y avisa que se están cargando', function (string $pestana) {
    sinJergaTecnica($this->actingAs(usuarioDeis())
        ->get(route('inteligencia-deis.index', ['tab' => $pestana]))
        ->assertOk()
        ->assertSee('Estamos cargando los datos del DEIS.')
        ->assertSee('Vuelva a revisar en unos minutos.')
        ->assertDontSee('Actualizar datos DEIS'));

    Queue::assertPushedTimes(ImportarDatasetDeis::class, 3);
    expect(DeisImportacion::query()->orderBy('dataset')->get(['dataset', 'estado', 'user_id'])->toArray())->toBe([
        ['dataset' => 'defunciones', 'estado' => DeisImportacion::ESTADO_PENDIENTE, 'user_id' => null],
        ['dataset' => 'egresos', 'estado' => DeisImportacion::ESTADO_PENDIENTE, 'user_id' => null],
        ['dataset' => 'urgencias', 'estado' => DeisImportacion::ESTADO_PENDIENTE, 'user_id' => null],
    ]);
})->with(['urgencias', 'defunciones', 'egresos']);

test('abrir el panel varias veces no duplica la carga inicial', function () {
    $usuario = usuarioDeis();

    $this->actingAs($usuario)->get(route('inteligencia-deis.index'))->assertOk();
    $this->actingAs($usuario)->get(route('inteligencia-deis.index', ['tab' => 'egresos']))->assertOk();
    $this->actingAs($usuario)->get(route('inteligencia-deis.index', ['tab' => 'egresos']))->assertOk();

    Queue::assertPushedTimes(ImportarDatasetDeis::class, 3);
    $this->assertDatabaseCount('deis_importaciones', 3);
});

test('el panel no encola la carga inicial de un dataset que ya tiene datos o una importación completada', function () {
    crearAtencionesUrgencia();
    DeisDefuncion::factory()->create();
    DeisImportacion::factory()->delDataset('egresos')->create(['created_at' => now()->subDays(10)]);

    $this->actingAs(usuarioDeis())
        ->get(route('inteligencia-deis.index', ['tab' => 'urgencias']))
        ->assertOk();

    Queue::assertNothingPushed();
});

test('si la carga inicial falló hace poco no se reintenta al abrir el panel y se avisa que se cargará después', function () {
    DeisImportacion::factory()->delDataset('egresos')->fallido()->create(['created_at' => now()->subHours(2)]);

    sinJergaTecnica($this->actingAs(usuarioDeis())
        ->get(route('inteligencia-deis.index', ['tab' => 'egresos']))
        ->assertSee('Aún no hay datos de egresos hospitalarios')
        ->assertSee('Los datos se cargarán automáticamente en la próxima actualización.')
        ->assertDontSee('Estamos cargando los datos del DEIS.'));

    Queue::assertNotPushed(ImportarDatasetDeis::class, fn (ImportarDatasetDeis $job) => $job->dataset === 'egresos');
    Queue::assertPushedTimes(ImportarDatasetDeis::class, 2);
});

test('pasada la ventana de reintento el panel vuelve a encolar la carga inicial', function () {
    DeisImportacion::factory()->delDataset('egresos')->fallido()->create(['created_at' => now()->subHours(7)]);

    $this->actingAs(usuarioDeis())
        ->get(route('inteligencia-deis.index', ['tab' => 'egresos']))
        ->assertSee('Estamos cargando los datos del DEIS.');

    Queue::assertPushed(ImportarDatasetDeis::class, fn (ImportarDatasetDeis $job) => $job->dataset === 'egresos');
});

test('el panel no muestra términos técnicos a ningún rol, con o sin datos', function (string $rol, bool $conDatos, string $pestana) {
    if ($conDatos) {
        crearAtencionesUrgencia();
        DeisDefuncion::factory()->create();
        DeisEgreso::factory()->create();

        foreach (DeisImportacion::DATASETS as $dataset) {
            DeisImportacion::factory()->delDataset($dataset)->create(['origen' => "C:\\datos\\deis\\{$dataset}.zip"]);
        }

        DeisImportacion::factory()->delDataset('egresos')->fallido()->create();
    }

    sinJergaTecnica($this->actingAs(usuarioDeis($rol))
        ->get(route('inteligencia-deis.index', ['tab' => $pestana]))
        ->assertOk());
})->with(['Admin', 'Usuario'])->with(['con datos' => true, 'sin datos' => false])->with(['tableros', 'urgencias', 'defunciones', 'egresos']);

test('la tarjeta de fuente muestra los años con datos, la última actualización, el portal DEIS y el tablero del SST relacionado', function () {
    DeisEgreso::factory()->create(['anio' => 2023]);
    DeisEgreso::factory()->create(['anio' => 2024]);
    DeisImportacion::factory()->delDataset('egresos')->create([
        'origen' => 'https://repositoriodeis.minsal.cl/DatosAbiertos/EGRESOS/EGRESOS_2024.zip',
        'finalizado_en' => '2026-10-20 10:30:00',
    ]);
    DeisImportacion::factory()->delDataset('egresos')->fallido()->create(['finalizado_en' => '2026-10-25 08:00:00']);

    $this->actingAs(usuarioDeis())
        ->get(route('inteligencia-deis.index', ['tab' => 'egresos']))
        ->assertSeeInOrder(['Fuente', 'DEIS-MINSAL · Egresos hospitalarios', 'Datos de los años', '2023, 2024', 'Última actualización:', '20/10/2026 10:30', 'Portal de datos abiertos DEIS-MINSAL'])
        ->assertSee('href="'.e(config('deis.portal.url')).'"', false)
        ->assertDontSee('EGRESOS_2024')
        ->assertSee(route('inteligencia-deis.index', ['tab' => 'tableros', 'tablero' => 'anuario-produccion']).'#tablero-activo');
});

test('la tarjeta de fuente no muestra la ruta ni el nombre del archivo cuando se importó desde un archivo local', function () {
    DeisDefuncion::factory()->create();
    DeisImportacion::factory()->delDataset('defunciones')->create(['origen' => 'C:\\datos\\deis\\DEFUNCIONES_FUENTE_DEIS_2024_2026.zip']);

    $this->actingAs(usuarioDeis())
        ->get(route('inteligencia-deis.index', ['tab' => 'defunciones']))
        ->assertSeeInOrder(['Datos del año', '2025', 'Origen:', 'Portal de datos abiertos DEIS-MINSAL'])
        ->assertDontSee('C:\\datos\\deis')
        ->assertDontSee('DEFUNCIONES_FUENTE_DEIS')
        ->assertDontSee('archivo local');
});

test('un administrador ve el botón de actualización y el estado de la última actualización', function () {
    $admin = usuarioDeis('Admin');
    DeisImportacion::factory()->delDataset('urgencias')->for($admin)->create(['finalizado_en' => '2026-10-20 10:30:00']);
    DeisImportacion::factory()->delDataset('egresos')->fallido()->create();

    sinJergaTecnica($this->actingAs($admin)
        ->get(route('inteligencia-deis.index'))
        ->assertSee('action="'.route('inteligencia-deis.actualizar').'"', false)
        ->assertSee('data-confirmar=', false)
        ->assertSee('Los datos se actualizan automáticamente cada semana.')
        ->assertSeeInOrder([
            'Estado de la última actualización',
            'Atenciones de urgencia', 'Completada', '20/10/2026 10:30', "solicitada por {$admin->name}",
            'Defunciones', 'En espera', 'actualización automática',
            'Egresos hospitalarios', 'Con error', 'actualización automática', 'No se pudo descargar la información desde el DEIS. Se volverá a intentar en la próxima actualización automática.',
        ]));
});

test('un usuario sin rol Admin no ve el botón de actualización ni el estado de las importaciones', function () {
    DeisImportacion::factory()->delDataset('egresos')->fallido()->create();

    $this->actingAs(usuarioDeis())
        ->get(route('inteligencia-deis.index'))
        ->assertDontSee(route('inteligencia-deis.actualizar'))
        ->assertDontSee('Estado de la última actualización')
        ->assertDontSee('No se pudo descargar la información desde el DEIS.');
});

test('un usuario sin rol Admin no puede encolar la actualización', function () {
    Queue::fake([ImportarDatasetDeis::class]);

    $this->actingAs(usuarioDeis())
        ->post(route('inteligencia-deis.actualizar'))
        ->assertForbidden();

    Queue::assertNothingPushed();
    $this->assertDatabaseEmpty('deis_importaciones');
});

test('un administrador encola la importación de los tres datasets DEIS', function () {
    Queue::fake([ImportarDatasetDeis::class]);
    $admin = usuarioDeis('Admin');

    $this->actingAs($admin)
        ->post(route('inteligencia-deis.actualizar'), ['tab' => 'defunciones'])
        ->assertRedirect(route('inteligencia-deis.index', ['tab' => 'defunciones']))
        ->assertSessionHas('status', 'Se inició la actualización de Atenciones de urgencia, Defunciones y Egresos hospitalarios. La actualización se realiza automáticamente en segundo plano; puede tardar unos minutos.');

    $pendientes = DeisImportacion::query()
        ->where('estado', DeisImportacion::ESTADO_PENDIENTE)
        ->where('user_id', $admin->id)
        ->orderBy('dataset')
        ->get()
        ->map(fn (DeisImportacion $importacion) => [$importacion->dataset, $importacion->id])
        ->all();
    $encolados = Queue::pushed(ImportarDatasetDeis::class)
        ->map(fn (ImportarDatasetDeis $job) => [$job->dataset, $job->importacionId])
        ->sortBy(0)
        ->values()
        ->all();

    expect(array_column($pendientes, 0))->toBe(['defunciones', 'egresos', 'urgencias']);
    expect($encolados)->toBe($pendientes);
    $this->assertDatabaseHas('deis_importaciones', ['dataset' => 'defunciones', 'origen' => config('deis.datasets.defunciones.busqueda.patron')]);
});

test('no se encola un dataset que ya tiene una importación en curso reciente', function (string $estado) {
    Queue::fake([ImportarDatasetDeis::class]);
    $enCurso = DeisImportacion::factory()->delDataset('urgencias')->{$estado}()->create(['created_at' => now()->subMinutes(90)]);

    $this->actingAs(usuarioDeis('Admin'))
        ->post(route('inteligencia-deis.actualizar'))
        ->assertSessionHas('status', 'Se inició la actualización de Defunciones y Egresos hospitalarios. La actualización se realiza automáticamente en segundo plano; puede tardar unos minutos. Atenciones de urgencia ya se estaba actualizando.');

    Queue::assertNotPushed(ImportarDatasetDeis::class, fn (ImportarDatasetDeis $job) => $job->dataset === 'urgencias');
    Queue::assertPushedTimes(ImportarDatasetDeis::class, 2);
    expect(DeisImportacion::query()->delDataset('urgencias')->pluck('id')->all())->toBe([$enCurso->id]);
})->with(['pendiente', 'procesando']);

test('una importación en proceso de hace más de 2 horas se considera interrumpida y no impide encolar otra', function () {
    Queue::fake([ImportarDatasetDeis::class]);
    DeisImportacion::factory()->delDataset('urgencias')->procesando()->create(['created_at' => now()->subHours(3)]);

    $this->actingAs(usuarioDeis('Admin'))
        ->post(route('inteligencia-deis.actualizar'));

    Queue::assertPushedTimes(ImportarDatasetDeis::class, 3);
});

test('una importación pendiente sin procesar impide encolar otra aunque tenga más de 2 horas', function () {
    Queue::fake([ImportarDatasetDeis::class]);
    $pendiente = DeisImportacion::factory()->delDataset('urgencias')->pendiente()->create(['created_at' => now()->subDays(3)]);

    $this->actingAs(usuarioDeis('Admin'))
        ->post(route('inteligencia-deis.actualizar'))
        ->assertSessionHas('status', fn (string $mensaje) => str_ends_with($mensaje, 'Atenciones de urgencia ya se estaba actualizando.'));

    Queue::assertPushedTimes(ImportarDatasetDeis::class, 2);
    Queue::assertNotPushed(ImportarDatasetDeis::class, fn (ImportarDatasetDeis $job) => $job->dataset === 'urgencias');
    expect(DeisImportacion::query()->delDataset('urgencias')->pluck('id')->all())->toBe([$pendiente->id]);
});

test('si otra solicitud está iniciando la actualización se avisa sin error y sin encolar nada', function () {
    Queue::fake([ImportarDatasetDeis::class]);
    config(['deis.actualizacion.espera_candado' => 0]);
    $candado = Cache::lock('deis-actualizar', 10);
    expect($candado->get())->toBeTrue();

    $inicio = microtime(true);

    $this->actingAs(usuarioDeis('Admin'))
        ->post(route('inteligencia-deis.actualizar'), ['tab' => 'egresos'])
        ->assertRedirect(route('inteligencia-deis.index', ['tab' => 'egresos']))
        ->assertSessionHas('warning', 'Los datos del DEIS ya se están actualizando. Espere unos minutos y vuelva a revisar esta página.');

    expect(microtime(true) - $inicio)->toBeLessThan(2);
    Queue::assertNothingPushed();
    $this->assertDatabaseEmpty('deis_importaciones');
    $candado->release();
});

test('si todos los datasets tienen una importación en curso no se encola nada y se avisa', function () {
    Queue::fake([ImportarDatasetDeis::class]);
    foreach (DeisImportacion::DATASETS as $dataset) {
        DeisImportacion::factory()->delDataset($dataset)->procesando()->create();
    }

    $this->actingAs(usuarioDeis('Admin'))
        ->post(route('inteligencia-deis.actualizar'))
        ->assertRedirect(route('inteligencia-deis.index', ['tab' => 'tableros']))
        ->assertSessionHas('warning', 'Los datos del DEIS ya se están actualizando. Espere unos minutos y vuelva a revisar esta página.');

    Queue::assertNothingPushed();
    $this->assertDatabaseCount('deis_importaciones', 3);
});

test('los avisos de la actualización no contienen términos técnicos', function (bool $todosEnCurso) {
    if ($todosEnCurso) {
        foreach (DeisImportacion::DATASETS as $dataset) {
            DeisImportacion::factory()->delDataset($dataset)->procesando()->create();
        }
    } else {
        DeisImportacion::factory()->delDataset('urgencias')->pendiente()->create();
    }

    $this->actingAs(usuarioDeis('Admin'))->post(route('inteligencia-deis.actualizar'));

    $aviso = Str::lower((string) (session('status') ?? session('warning')));

    expect($aviso)->not->toBeEmpty();
    foreach ([...TERMINOS_TECNICOS, 'cola', 'dataset', 'job'] as $termino) {
        expect($aviso)->not->toContain(Str::lower($termino));
    }
})->with(['con datasets por encolar' => false, 'con todos en curso' => true]);

test('el aviso de la actualización se muestra al volver al panel sin términos técnicos', function () {
    sinJergaTecnica($this->actingAs(usuarioDeis('Admin'))
        ->followingRedirects()
        ->post(route('inteligencia-deis.actualizar'), ['tab' => 'urgencias'])
        ->assertOk()
        ->assertSee('Se inició la actualización de Atenciones de urgencia, Defunciones y Egresos hospitalarios.')
        ->assertSee('Estamos cargando los datos del DEIS.'));
});
