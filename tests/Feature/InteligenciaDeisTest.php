<?php

use App\Jobs\ImportarDatasetDeis;
use App\Models\DeisAtencionUrgencia;
use App\Models\DeisDefuncion;
use App\Models\DeisEgreso;
use App\Models\DeisImportacion;
use App\Models\Establecimiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Usuario']);
    Role::firstOrCreate(['name' => 'Admin']);
});

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

test('una pestaña de datos sin importar muestra el estado vacío', function (string $pestana) {
    $this->actingAs(usuarioDeis())
        ->get(route('inteligencia-deis.index', ['tab' => $pestana]))
        ->assertSee("php artisan deis:importar {$pestana}")
        ->assertSee('Actualizar datos DEIS')
        ->assertSee('Sin importar');
})->with(['urgencias', 'defunciones', 'egresos']);

test('la tarjeta de fuente muestra la última importación completada y el tablero del SST relacionado', function () {
    DeisEgreso::factory()->create();
    DeisImportacion::factory()->delDataset('egresos')->create([
        'origen' => 'https://repositoriodeis.minsal.cl/DatosAbiertos/EGRESOS/EGRESOS_2024.zip',
        'finalizado_en' => '2026-10-20 10:30:00',
    ]);
    DeisImportacion::factory()->delDataset('egresos')->fallido()->create(['finalizado_en' => '2026-10-25 08:00:00']);

    $this->actingAs(usuarioDeis())
        ->get(route('inteligencia-deis.index', ['tab' => 'egresos']))
        ->assertSeeInOrder(['Fuente', 'DEIS-MINSAL · Egresos hospitalarios', 'Última importación:', '20/10/2026 10:30', 'EGRESOS_2024.zip'])
        ->assertSee('href="https://repositoriodeis.minsal.cl/DatosAbiertos/EGRESOS/EGRESOS_2024.zip"', false)
        ->assertSee(route('inteligencia-deis.index', ['tab' => 'tableros', 'tablero' => 'anuario-produccion']).'#tablero-activo');
});

test('la tarjeta de fuente muestra solo el nombre del archivo cuando se importó desde un archivo local', function () {
    DeisDefuncion::factory()->create();
    DeisImportacion::factory()->delDataset('defunciones')->create(['origen' => 'C:\\datos\\deis\\DEFUNCIONES_FUENTE_DEIS_2024_2026.zip']);

    $this->actingAs(usuarioDeis())
        ->get(route('inteligencia-deis.index', ['tab' => 'defunciones']))
        ->assertSee('DEFUNCIONES_FUENTE_DEIS_2024_2026.zip (archivo local)')
        ->assertDontSee('C:\\datos\\deis');
});

test('un administrador ve el botón de actualización y el estado de la última importación', function () {
    DeisImportacion::factory()->delDataset('egresos')->fallido()->create(['mensaje' => 'La descarga desde URL falló (HTTP 404).']);

    $this->actingAs(usuarioDeis('Admin'))
        ->get(route('inteligencia-deis.index'))
        ->assertSee('action="'.route('inteligencia-deis.actualizar').'"', false)
        ->assertSee('data-confirmar=', false)
        ->assertSeeInOrder(['Estado de la última importación', 'Egresos hospitalarios', 'Fallido', 'La descarga desde URL falló (HTTP 404).']);
});

test('un usuario sin rol Admin no ve el botón de actualización ni el estado de las importaciones', function () {
    DeisImportacion::factory()->delDataset('egresos')->fallido()->create(['mensaje' => 'La descarga desde URL falló (HTTP 404).']);

    $this->actingAs(usuarioDeis())
        ->get(route('inteligencia-deis.index'))
        ->assertDontSee(route('inteligencia-deis.actualizar'))
        ->assertDontSee('Estado de la última importación')
        ->assertDontSee('La descarga desde URL falló (HTTP 404).');
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
        ->assertSessionHas('status', fn (string $mensaje) => str_starts_with($mensaje, 'Actualización de datos DEIS en cola (Atenciones de urgencia, Defunciones y Egresos hospitalarios).')
            && str_contains($mensaje, 'php artisan queue:work'));

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
    $this->assertDatabaseHas('deis_importaciones', ['dataset' => 'defunciones', 'origen' => config('deis.datasets.defunciones.url')]);
});

test('no se encola un dataset que ya tiene una importación en curso reciente', function (string $estado) {
    Queue::fake([ImportarDatasetDeis::class]);
    $enCurso = DeisImportacion::factory()->delDataset('urgencias')->{$estado}()->create(['created_at' => now()->subMinutes(90)]);

    $this->actingAs(usuarioDeis('Admin'))
        ->post(route('inteligencia-deis.actualizar'))
        ->assertSessionHas('status', fn (string $mensaje) => str_contains($mensaje, 'Se omitió Atenciones de urgencia porque ya tiene una importación en curso.'));

    Queue::assertNotPushed(ImportarDatasetDeis::class, fn (ImportarDatasetDeis $job) => $job->dataset === 'urgencias');
    Queue::assertPushedTimes(ImportarDatasetDeis::class, 2);
    expect(DeisImportacion::query()->delDataset('urgencias')->pluck('id')->all())->toBe([$enCurso->id]);
})->with(['pendiente', 'procesando']);

test('una importación en curso de hace más de 2 horas no impide encolar otra', function () {
    Queue::fake([ImportarDatasetDeis::class]);
    DeisImportacion::factory()->delDataset('urgencias')->pendiente()->create(['created_at' => now()->subHours(3)]);

    $this->actingAs(usuarioDeis('Admin'))
        ->post(route('inteligencia-deis.actualizar'));

    Queue::assertPushedTimes(ImportarDatasetDeis::class, 3);
});

test('si todos los datasets tienen una importación en curso no se encola nada y se avisa', function () {
    Queue::fake([ImportarDatasetDeis::class]);
    foreach (DeisImportacion::DATASETS as $dataset) {
        DeisImportacion::factory()->delDataset($dataset)->procesando()->create();
    }

    $this->actingAs(usuarioDeis('Admin'))
        ->post(route('inteligencia-deis.actualizar'))
        ->assertRedirect(route('inteligencia-deis.index', ['tab' => 'tableros']))
        ->assertSessionHas('warning', 'No se encoló ninguna actualización: los tres datasets DEIS ya tienen una importación pendiente o en proceso solicitada hace menos de 2 horas.');

    Queue::assertNothingPushed();
    $this->assertDatabaseCount('deis_importaciones', 3);
});
