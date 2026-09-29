<?php

use App\Models\Indicador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;

uses(RefreshDatabase::class);

/**
 * Datos válidos del formulario de indicador, con los cambios indicados.
 *
 * @param  array<string, mixed>  $cambios
 * @return array<string, mixed>
 */
function datosDeIndicador(array $cambios = []): array
{
    return array_merge([
        'codigo' => 'IAAPS-99',
        'nombre' => 'Cobertura de prueba',
        'tipo' => 'IAAPS',
        'numerador_description' => 'Numerador de prueba',
        'denominador_description' => 'Denominador de prueba',
        'target_value' => '85',
        'periodicidad' => 'Mensual',
        'fu' => 'REM-A',
        'is_active' => '1',
    ], $cambios);
}

test('lista solo los indicadores activos por defecto, de a 10 por página', function () {
    Indicador::factory()->count(12)->create();
    Indicador::factory()->inactivo()->create(['codigo' => 'INA-01']);

    $this->get(route('indicadores.index'))
        ->assertOk()
        ->assertViewHas('indicadores', fn (LengthAwarePaginator $pagina) => $pagina->total() === 12 && $pagina->count() === 10)
        ->assertDontSee('INA-01');

    $this->get(route('indicadores.index', ['page' => 2]))
        ->assertOk()
        ->assertViewHas('indicadores', fn (LengthAwarePaginator $pagina) => $pagina->count() === 2);
});

test('una página que quedó vacía redirige a la última con resultados', function () {
    Indicador::factory()->count(3)->create();

    $this->get(route('indicadores.index', ['page' => 5]))
        ->assertRedirect(route('indicadores.index', ['page' => 1]));

    $this->get('/indicadores?page=9223372036854775807')
        ->assertRedirect(route('indicadores.index', ['page' => 1]));
});

test('"Estado: Todos" se conserva en la paginación, en Exportar y al redirigir', function () {
    Indicador::factory()->count(10)->create();
    Indicador::factory()->inactivo()->count(2)->create();

    $html = $this->get(route('indicadores.index', ['estado' => 'todos']))
        ->assertViewHas('indicadores', fn (LengthAwarePaginator $pagina) => $pagina->total() === 12)
        ->getContent();

    expect($html)
        ->toContain('href="'.e(route('indicadores.index', ['estado' => 'todos', 'page' => 2])).'"')
        ->toContain('href="'.e(route('indicadores.exportar', ['estado' => 'todos'])).'"');

    $this->get(route('indicadores.index', ['estado' => 'todos', 'page' => 9]))
        ->assertRedirect(route('indicadores.index', ['estado' => 'todos', 'page' => 2]));
});

test('la paginación y Exportar conservan los filtros activos, sin arrastrar la página', function () {
    Indicador::factory()->count(12)->create(['tipo' => 'IAAPS']);

    $html = $this->get(route('indicadores.index', ['tipo' => 'IAAPS', 'page' => 1]))->getContent();

    expect($html)
        ->toContain('href="'.e(route('indicadores.index', ['tipo' => 'IAAPS', 'page' => 2])).'"')
        ->toContain('href="'.e(route('indicadores.exportar', ['tipo' => 'IAAPS'])).'"');
});

test('el buscador de la barra superior busca en el catálogo desde cualquier página', function () {
    expect($this->get(route('centro-mando'))->getContent())
        ->toContain('action="'.route('indicadores.index').'"')
        ->toContain('name="q"')
        ->toContain('name="estado" value="todos"');
});

test('el buscador encuentra por código o nombre, incluidos los inactivos', function () {
    Indicador::factory()->create(['codigo' => 'MS-04', 'nombre' => 'Cobertura efectiva de DM2']);
    Indicador::factory()->inactivo()->create(['codigo' => 'MIN-05', 'nombre' => 'Visita domiciliaria integral']);
    Indicador::factory()->create(['codigo' => 'IAAPS-04', 'nombre' => 'Examen de medicina preventiva']);

    $this->get(route('indicadores.index', ['q' => 'dm2', 'estado' => 'todos']))
        ->assertOk()
        ->assertSee('MS-04')
        ->assertDontSee('IAAPS-04');

    $this->get(route('indicadores.index', ['q' => 'min-05', 'estado' => 'todos']))
        ->assertSee('MIN-05')
        ->assertDontSee('MS-04');
});

test('la búsqueda trata % y _ como texto literal', function () {
    Indicador::factory()->create(['codigo' => 'MS_01', 'nombre' => 'Cobertura del 100% garantizada']);
    Indicador::factory()->create(['codigo' => 'MSX01', 'nombre' => 'Cobertura del 1000 garantizada']);

    $this->get(route('indicadores.index', ['q' => 'MS_01']))
        ->assertSee('MS_01')
        ->assertDontSee('MSX01');

    $this->get(route('indicadores.index', ['q' => '100%']))
        ->assertSee('MS_01')
        ->assertDontSee('MSX01');
});

test('filtra por tipo, fuente y estado', function () {
    Indicador::factory()->create(['codigo' => 'MS-01', 'tipo' => 'Meta Sanitaria', 'fu' => 'REM-A']);
    Indicador::factory()->create(['codigo' => 'MS-02', 'tipo' => 'Meta Sanitaria', 'fu' => 'REM-P']);
    Indicador::factory()->create(['codigo' => 'IAAPS-01', 'tipo' => 'IAAPS', 'fu' => 'REM-A']);
    Indicador::factory()->inactivo()->create(['codigo' => 'MS-09', 'tipo' => 'Meta Sanitaria', 'fu' => 'REM-A']);

    $this->get(route('indicadores.index', ['tipo' => 'Meta Sanitaria', 'fuente' => 'REM-A']))
        ->assertSee('MS-01')
        ->assertDontSee(['MS-02', 'IAAPS-01', 'MS-09']);

    $this->get(route('indicadores.index', ['estado' => 'inactivo']))
        ->assertSee('MS-09')
        ->assertDontSee(['MS-01', 'MS-02', 'IAAPS-01']);
});

test('ignora filtros con valores desconocidos o con formato inválido', function () {
    Indicador::factory()->create(['codigo' => 'MS-01']);

    $this->get('/indicadores?tipo=Otro&fuente=XYZ&estado=cualquiera')->assertOk()->assertSee('MS-01');
    $this->get('/indicadores?q[]=a&tipo[]=b&estado[]=c&page[]=d')->assertOk();
});

test('crea un indicador y lo muestra en la lista', function () {
    $this->post(route('indicadores.store'), datosDeIndicador())
        ->assertRedirect(route('indicadores.index', ['q' => 'IAAPS-99', 'estado' => 'todos']))
        ->assertSessionHas('status', 'Indicador IAAPS-99 creado.');

    $this->assertDatabaseHas('indicadores', ['codigo' => 'IAAPS-99', 'target_value' => 85, 'is_active' => true]);
});

test('rechaza datos inválidos al guardar', function (array $cambios, string $campo) {
    $this->post(route('indicadores.store'), datosDeIndicador($cambios))
        ->assertSessionHasErrors($campo);

    expect(Indicador::count())->toBe(0);
})->with([
    'código vacío' => [['codigo' => ''], 'codigo'],
    'nombre vacío' => [['nombre' => ''], 'nombre'],
    'tipo desconocido' => [['tipo' => 'Otro'], 'tipo'],
    'meta sobre 100' => [['target_value' => '150'], 'target_value'],
    'meta no numérica' => [['target_value' => 'ochenta'], 'target_value'],
    'meta con 3 decimales' => [['target_value' => '85.555'], 'target_value'],
    'meta en notación científica' => [['target_value' => '1e2'], 'target_value'],
    'periodicidad desconocida' => [['periodicidad' => 'Diaria'], 'periodicidad'],
    'fuente desconocida' => [['fu' => 'XYZ'], 'fu'],
    'estado inválido' => [['is_active' => 'quizás'], 'is_active'],
]);

test('no permite repetir un código, pero sí conservar el propio al editar', function () {
    $indicador = Indicador::factory()->create(['codigo' => 'MS-01']);

    $this->post(route('indicadores.store'), datosDeIndicador(['codigo' => 'MS-01']))
        ->assertSessionHasErrors(['codigo' => 'Ya existe un indicador con ese código.']);

    $this->put(route('indicadores.update', $indicador), datosDeIndicador(['codigo' => 'MS-01', 'nombre' => 'Nombre corregido']))
        ->assertSessionHasNoErrors();

    expect($indicador->fresh()->nombre)->toBe('Nombre corregido');
});

test('actualiza un indicador y vuelve a la página anterior', function () {
    $indicador = Indicador::factory()->create(['codigo' => 'MS-02', 'target_value' => 80]);

    $this->from(route('indicadores.index', ['page' => 1, 'tipo' => 'IAAPS']))
        ->put(route('indicadores.update', $indicador), datosDeIndicador(['codigo' => 'MS-02', 'target_value' => '29.5', 'fu' => 'SIGGES']))
        ->assertRedirect(route('indicadores.index', ['page' => 1, 'tipo' => 'IAAPS']))
        ->assertSessionHas('status', 'Indicador MS-02 actualizado.');

    $indicador->refresh();

    expect($indicador->fu)->toBe('SIGGES')
        ->and($indicador->meta_formateada)->toBe('29,5');
});

test('si falla la validación al editar, el formulario se reabre en modo edición', function () {
    $indicador = Indicador::factory()->create(['codigo' => 'MS-05']);

    $this->from(route('indicadores.index'))
        ->followingRedirects()
        ->put(route('indicadores.update', $indicador), datosDeIndicador(['indicador_id' => $indicador->id, 'codigo' => 'MS-05', 'nombre' => '']))
        ->assertSee('data-reabrir="true"', false)
        ->assertSee('action="'.route('indicadores.update', $indicador).'"', false)
        ->assertSee('Editar indicador')
        ->assertSee('aria-describedby="error-nombre"', false);
});

test('desactiva y vuelve a activar un indicador, volviendo a la misma lista', function () {
    $indicador = Indicador::factory()->create(['codigo' => 'MS-03']);

    $this->from('/indicadores?page=2&tipo=IAAPS')
        ->patch(route('indicadores.estado', $indicador), ['is_active' => 0])
        ->assertRedirect('/indicadores?page=2&tipo=IAAPS')
        ->assertSessionHas('status', 'Indicador MS-03 desactivado.');
    expect($indicador->fresh()->is_active)->toBeFalse();

    $this->patch(route('indicadores.estado', $indicador), ['is_active' => 1])
        ->assertSessionHas('status', 'Indicador MS-03 activado.');
    expect($indicador->fresh()->is_active)->toBeTrue();
});

test('desactivar dos veces (doble clic o pestaña desactualizada) no lo vuelve a activar', function () {
    $indicador = Indicador::factory()->create();

    $this->patch(route('indicadores.estado', $indicador), ['is_active' => 0]);
    $this->patch(route('indicadores.estado', $indicador), ['is_active' => 0]);

    expect($indicador->fresh()->is_active)->toBeFalse();
});

test('al desactivar el último de la última página se ve la confirmación en la página anterior', function () {
    Indicador::factory()->count(10)->sequence(fn ($secuencia) => ['codigo' => sprintf('A-%02d', $secuencia->index)])->create();
    $ultimo = Indicador::factory()->create(['codigo' => 'Z-11']);

    $this->from(route('indicadores.index', ['page' => 2]))
        ->followingRedirects()
        ->patch(route('indicadores.estado', $ultimo), ['is_active' => 0])
        ->assertOk()
        ->assertSee('Indicador Z-11 desactivado.');
});

test('responde 404 al editar un indicador que no existe o con un id no numérico', function () {
    Indicador::factory()->create();

    $this->put(route('indicadores.update', 999), datosDeIndicador())->assertNotFound();
    $this->patch(route('indicadores.estado', 999), ['is_active' => 0])->assertNotFound();
    $this->patch('/indicadores/1abc/estado', ['is_active' => 0])->assertNotFound();
});

test('exporta a CSV los indicadores de la búsqueda y filtros actuales', function () {
    $this->freezeTime();

    Indicador::factory()->create(['codigo' => 'MS-01', 'nombre' => 'Recuperación del DSM', 'fu' => 'REM-A', 'target_value' => 90]);
    Indicador::factory()->create(['codigo' => 'MS-02', 'fu' => 'REM-P']);
    Indicador::factory()->inactivo()->create(['codigo' => 'MS-03', 'fu' => 'REM-A']);

    $respuesta = $this->get(route('indicadores.exportar', ['fuente' => 'REM-A', 'estado' => 'todos']));

    $respuesta->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
        ->assertDownload('catalogo-indicadores-'.now()->format('Y-m-d').'.csv');

    $csv = $respuesta->streamedContent();

    expect($csv)->toStartWith("\xEF\xBB\xBF")
        ->toContain('Código;Nombre;Tipo;Numerador;Denominador;"Meta (%)";Periodicidad;Fuente;Estado')
        ->toContain('MS-01;"Recuperación del DSM"')
        ->toContain(';90;')
        ->toContain('MS-03')
        ->not->toContain('MS-02');

    expect($this->get(route('indicadores.exportar', ['fuente' => 'REM-A']))->streamedContent())
        ->toContain('MS-01')
        ->not->toContain('MS-03');
});

test('la exportación neutraliza textos que Excel ejecutaría como fórmula', function () {
    Indicador::factory()->create(['codigo' => 'MS-01', 'nombre' => '=HYPERLINK("http://ejemplo.test","clic")']);

    $csv = $this->get(route('indicadores.exportar'))->streamedContent();

    expect($csv)->toContain(";\"'=HYPERLINK(")
        ->not->toContain(';"=HYPERLINK(');
});

test('el gráfico por fuente cuenta solo los indicadores activos', function () {
    Indicador::factory()->count(2)->create(['tipo' => 'IAAPS', 'fu' => 'REM-A']);
    Indicador::factory()->create(['tipo' => 'Ministerial', 'fu' => 'DEIS']);
    Indicador::factory()->inactivo()->create(['tipo' => 'IAAPS', 'fu' => 'REM-A']);

    $this->get(route('indicadores.index'))
        ->assertViewHas('porFuente', fn (array $conteo) => $conteo['REM-A']['IAAPS'] === 2
            && $conteo['DEIS']['Ministerial'] === 1
            && $conteo['SIGGES']['Meta Sanitaria'] === 0);
});

test('las demás páginas siguen cargando con el nuevo layout', function (string $ruta) {
    $this->get(route($ruta))->assertOk();
})->with(['centro-mando', 'iaaps-metas.index', 'compromisos.index', 'inteligencia-deis.index', 'alertas.index']);
