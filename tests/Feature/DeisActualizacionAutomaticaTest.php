<?php

use App\Jobs\ImportarDatasetDeis;
use App\Models\DeisAtencionUrgencia;
use App\Models\DeisImportacion;
use App\Services\DeisActualizacionService;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake([ImportarDatasetDeis::class]);
});

/**
 * Tarea programada registrada con la descripción indicada.
 */
function tareaProgramadaDeis(string $descripcion): CallbackEvent
{
    $evento = collect(app(Schedule::class)->events())->first(fn ($evento) => $evento->description === $descripcion);

    expect($evento)->toBeInstanceOf(CallbackEvent::class);

    return $evento;
}

test('la actualización semanal de los datasets DEIS está programada el miércoles a las 03:00 de Chile', function () {
    $tarea = tareaProgramadaDeis('Actualización semanal de los datasets DEIS');

    expect($tarea->expression)->toBe('0 3 * * 3')
        ->and($tarea->timezone)->toBe('America/Santiago');

    $this->travelTo('2026-11-04 06:00:00');
    expect($tarea->isDue(app()))->toBeTrue();

    $this->travelTo('2026-11-04 03:00:00');
    expect($tarea->isDue(app()))->toBeFalse();
});

test('la carga inicial de los datasets DEIS está programada cada hora', function () {
    expect(tareaProgramadaDeis('Carga inicial de los datasets DEIS sin datos')->expression)->toBe('0 * * * *');
});

test('las tareas programadas aparecen en schedule:list', function () {
    $this->artisan('schedule:list')
        ->expectsOutputToContain('Actualización semanal de los datasets DEIS')
        ->expectsOutputToContain('Carga inicial de los datasets DEIS sin datos')
        ->assertSuccessful();
});

test('la actualización semanal encola los tres datasets sin usuario', function () {
    tareaProgramadaDeis('Actualización semanal de los datasets DEIS')->run(app());

    Queue::assertPushedTimes(ImportarDatasetDeis::class, 3);
    expect(DeisImportacion::query()->pluck('user_id')->unique()->all())->toBe([null])
        ->and(DeisImportacion::query()->orderBy('dataset')->pluck('dataset')->all())->toBe(['defunciones', 'egresos', 'urgencias']);
});

test('la actualización semanal no duplica un dataset con una importación en curso reciente', function () {
    DeisImportacion::factory()->delDataset('defunciones')->procesando()->create(['created_at' => now()->subMinutes(30)]);

    tareaProgramadaDeis('Actualización semanal de los datasets DEIS')->run(app());

    Queue::assertPushedTimes(ImportarDatasetDeis::class, 2);
    Queue::assertNotPushed(ImportarDatasetDeis::class, fn (ImportarDatasetDeis $job) => $job->dataset === 'defunciones');
});

test('la carga inicial programada encola solo los datasets sin datos ni intento reciente', function () {
    DeisAtencionUrgencia::factory()->create();
    DeisImportacion::factory()->delDataset('defunciones')->fallido()->create(['created_at' => now()->subHours(5)]);

    tareaProgramadaDeis('Carga inicial de los datasets DEIS sin datos')->run(app());

    Queue::assertPushedTimes(ImportarDatasetDeis::class, 1);
    Queue::assertPushed(ImportarDatasetDeis::class, fn (ImportarDatasetDeis $job) => $job->dataset === 'egresos');
    $this->assertDatabaseHas('deis_importaciones', ['dataset' => 'egresos', 'estado' => DeisImportacion::ESTADO_PENDIENTE, 'user_id' => null]);
});

test('la carga inicial reintenta un dataset fallido solo después de la ventana de reintento', function () {
    $servicio = app(DeisActualizacionService::class);
    DeisImportacion::factory()->delDataset('egresos')->fallido()->create(['created_at' => now()]);
    DeisImportacion::factory()->delDataset('urgencias')->create();
    DeisImportacion::factory()->delDataset('defunciones')->create();

    $this->travel(DeisActualizacionService::HORAS_REINTENTO_CARGA_INICIAL)->hours();
    $this->travel(-1)->minutes();
    expect($servicio->cargarFaltantes())->toBe([]);

    $this->travel(2)->minutes();
    expect($servicio->cargarFaltantes())->toBe(['egresos']);
    expect($servicio->cargarFaltantes())->toBe([]);

    Queue::assertPushedTimes(ImportarDatasetDeis::class, 1);
});

test('la carga inicial no vuelve a encolar un dataset con una importación pendiente sin procesar, aunque sea antigua', function () {
    DeisImportacion::factory()->delDataset('urgencias')->pendiente()->create(['created_at' => now()->subDays(2)]);
    DeisImportacion::factory()->delDataset('defunciones')->procesando()->create(['created_at' => now()->subDays(2)]);
    $servicio = app(DeisActualizacionService::class);

    expect($servicio->necesitaCargaInicial('urgencias'))->toBeFalse()
        ->and($servicio->enCurso('urgencias'))->toBeTrue()
        ->and($servicio->enCurso('defunciones'))->toBeFalse()
        ->and($servicio->cargarFaltantes())->toBe(['defunciones', 'egresos']);

    Queue::assertPushedTimes(ImportarDatasetDeis::class, 2);
    expect(DeisImportacion::query()->delDataset('urgencias')->count())->toBe(1);
});

test('la carga inicial no encola un dataset que ya tiene una importación completada', function () {
    foreach (DeisImportacion::DATASETS as $dataset) {
        DeisImportacion::factory()->delDataset($dataset)->create(['created_at' => now()->subWeeks(2)]);
    }

    expect(app(DeisActualizacionService::class)->cargarFaltantes())->toBe([]);

    Queue::assertNothingPushed();
});

test('la carga inicial no espera ni encola si otra solicitud tiene el candado', function () {
    $candado = Cache::lock('deis-actualizar', 10);
    $candado->get();

    expect(app(DeisActualizacionService::class)->cargarFaltantes())->toBe([]);

    Queue::assertNothingPushed();
    $candado->release();

    expect(app(DeisActualizacionService::class)->cargarFaltantes())->toBe(['urgencias', 'defunciones', 'egresos']);
});
