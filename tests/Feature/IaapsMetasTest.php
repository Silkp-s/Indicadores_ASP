<?php

use App\Models\Establecimiento;
use App\Models\Indicador;
use App\Models\IndicadorMedida;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Usuario']);
    Role::firstOrCreate(['name' => 'Admin']);

    $this->user = User::factory()->create();
    $this->user->assignRole('Usuario');
});

test('un usuario no autenticado es redirigido al login', function () {
    $this->get(route('iaaps-metas.index'))
        ->assertRedirect(route('login'));
});

test('un usuario autenticado puede ver el mantenedor con metas sanitarias e IAAPS', function () {
    $meta = Indicador::factory()->create([
        'codigo' => 'MS-01',
        'nombre' => 'Desarrollo Psicomotor',
        'tipo' => 'Meta Sanitaria',
        'target_value' => 90.00,
        'is_active' => true,
    ]);

    $iaaps = Indicador::factory()->create([
        'codigo' => 'IAAPS-01',
        'nombre' => 'Autoevaluación MAIS',
        'tipo' => 'IAAPS',
        'target_value' => 100.00,
        'is_active' => true,
    ]);

    $otro = Indicador::factory()->create([
        'codigo' => 'MIN-99',
        'nombre' => 'Indicador Ministerial General',
        'tipo' => 'Ministerial',
    ]);

    $this->actingAs($this->user)
        ->get(route('iaaps-metas.index'))
        ->assertOk()
        ->assertSee('IAAPS y Metas Sanitarias')
        ->assertSee('MS-01')
        ->assertSee('Desarrollo Psicomotor')
        ->assertSee('IAAPS-01')
        ->assertSee('Autoevaluación MAIS')
        ->assertDontSee('MIN-99');
});

test('el listado esta tabulado de 10 en 10', function () {
    // Crear 15 metas
    for ($i = 1; $i <= 15; $i++) {
        Indicador::factory()->create([
            'codigo' => sprintf('MS-%02d', $i),
            'nombre' => "Meta Sanitaria {$i}",
            'tipo' => 'Meta Sanitaria',
            'is_active' => true,
        ]);
    }

    $responsePage1 = $this->actingAs($this->user)
        ->get(route('iaaps-metas.index', ['page' => 1]))
        ->assertOk();

    $responsePage1->assertSee('10 por página');
    $responsePage1->assertSee('MS-01');
    $responsePage1->assertSee('MS-10');
    $responsePage1->assertDontSee('MS-11');

    $responsePage2 = $this->actingAs($this->user)
        ->get(route('iaaps-metas.index', ['page' => 2]))
        ->assertOk();

    $responsePage2->assertSee('MS-11');
    $responsePage2->assertSee('MS-15');
    $responsePage2->assertDontSee('MS-01');
});

test('muestra el desglose de numeradores, denominadores y cumplimiento de cada establecimiento', function () {
    $est1 = Establecimiento::factory()->create([
        'nombre' => 'CESFAM Los Cerros',
        'codigo_deis' => '119308',
        'tipo' => 'CESFAM',
        'is_active' => true,
    ]);

    $est2 = Establecimiento::factory()->create([
        'nombre' => 'CECOSF Libertad',
        'codigo_deis' => '119702',
        'tipo' => 'CECOSF',
        'is_active' => true,
    ]);

    $meta = Indicador::factory()->create([
        'codigo' => 'MS-01',
        'nombre' => 'Desarrollo Psicomotor',
        'tipo' => 'Meta Sanitaria',
        'target_value' => 90.00,
        'numerador_description' => 'Niños recuperados',
        'denominador_description' => 'Niños detectados',
        'is_active' => true,
    ]);

    // Medida solo para est1 en 2026-10
    IndicadorMedida::create([
        'indicadores_id' => $meta->id,
        'establecimientos_id' => $est1->id,
        'ano' => 2026,
        'mes' => 10,
        'numerador_value' => 45.00,
        'denominador_value' => 50.00,
        'porcentaje_cumplimiento' => 90.00,
        'status' => 'Cumpliendo',
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('iaaps-metas.index', ['periodo' => '2026-10']))
        ->assertOk();

    // Debe mostrar la fila desplegable del indicador
    $response->assertSee('detalle-indicador-'.$meta->id);
    $response->assertSee('Niños recuperados');
    $response->assertSee('Niños detectados');

    // Debe listar ambos establecimientos
    $response->assertSee('CESFAM Los Cerros');
    $response->assertSee('45');
    $response->assertSee('50');
    $response->assertSee('90,0%');
    $response->assertSee('Cumpliendo');

    $response->assertSee('CECOSF Libertad');
    $response->assertSee('Sin registro');
});

test('se pueden filtrar los indicadores por busqueda, tipo y estado', function () {
    Indicador::factory()->create([
        'codigo' => 'MS-01',
        'nombre' => 'Evaluacion Desarrollo Psicomotor',
        'tipo' => 'Meta Sanitaria',
        'is_active' => true,
    ]);

    Indicador::factory()->create([
        'codigo' => 'IAAPS-04',
        'nombre' => 'Cobertura DM2',
        'tipo' => 'IAAPS',
        'is_active' => true,
    ]);

    Indicador::factory()->create([
        'codigo' => 'IAAPS-99',
        'nombre' => 'Inactivo Test',
        'tipo' => 'IAAPS',
        'is_active' => false,
    ]);

    // Filtrar por texto
    $this->actingAs($this->user)
        ->get(route('iaaps-metas.index', ['q' => 'Psicomotor']))
        ->assertOk()
        ->assertSee('MS-01')
        ->assertDontSee('IAAPS-04');

    // Filtrar por tipo
    $this->actingAs($this->user)
        ->get(route('iaaps-metas.index', ['tipo' => 'IAAPS']))
        ->assertOk()
        ->assertSee('IAAPS-04')
        ->assertDontSee('Evaluacion Desarrollo Psicomotor');

    // Filtrar por estado inactivo
    $this->actingAs($this->user)
        ->get(route('iaaps-metas.index', ['estado' => 'inactivo']))
        ->assertOk()
        ->assertSee('IAAPS-99')
        ->assertDontSee('IAAPS-04');
});

test('se puede crear un nuevo indicador IAAPS o Meta Sanitaria', function () {
    $datos = [
        'codigo' => 'MS-10',
        'nombre' => 'Salud Oral Integral en Gestantes',
        'tipo' => 'Meta Sanitaria',
        'target_value' => 85.5,
        'periodicidad' => 'Mensual',
        'fu' => 'REM-A',
        'numerador_description' => 'Gestantes dadas de alta odontologica',
        'denominador_description' => 'Total gestantes ingresadas',
        'is_active' => true,
    ];

    $response = $this->actingAs($this->user)
        ->post(route('iaaps-metas.store'), $datos);

    $response->assertRedirect(route('iaaps-metas.index', ['q' => 'MS-10']));

    $this->assertDatabaseHas('indicadores', [
        'codigo' => 'MS-10',
        'nombre' => 'Salud Oral Integral en Gestantes',
        'tipo' => 'Meta Sanitaria',
        'target_value' => 85.50,
        'periodicidad' => 'Mensual',
        'fu' => 'REM-A',
        'is_active' => 1,
    ]);
});

test('falla la creacion si falta algun campo requerido o el codigo ya existe', function () {
    Indicador::factory()->create(['codigo' => 'MS-01']);

    $response = $this->actingAs($this->user)
        ->from(route('iaaps-metas.index'))
        ->post(route('iaaps-metas.store'), [
            'codigo' => 'MS-01',
            'nombre' => '',
            'tipo' => 'Invalido',
        ]);

    $response->assertRedirect(route('iaaps-metas.index'));
    $response->assertSessionHasErrors(['codigo', 'nombre', 'tipo', 'target_value']);
});

test('se puede actualizar un indicador existente', function () {
    $indicador = Indicador::factory()->create([
        'codigo' => 'MS-05',
        'nombre' => 'Nombre original',
        'tipo' => 'Meta Sanitaria',
        'target_value' => 70.00,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->user)
        ->put(route('iaaps-metas.update', $indicador), [
            'codigo' => 'MS-05',
            'nombre' => 'Nombre modificado',
            'tipo' => 'Meta Sanitaria',
            'target_value' => 75.00,
            'periodicidad' => 'Semestral',
            'fu' => 'REM-P',
            'is_active' => false,
        ]);

    $response->assertSessionHas('status');

    $indicador->refresh();
    expect($indicador->nombre)->toBe('Nombre modificado')
        ->and((float) $indicador->target_value)->toBe(75.00)
        ->and($indicador->periodicidad)->toBe('Semestral')
        ->and($indicador->fu)->toBe('REM-P')
        ->and($indicador->is_active)->toBeFalse();
});

test('se puede eliminar un indicador y sus medidas asociadas', function () {
    $indicador = Indicador::factory()->create([
        'codigo' => 'IAAPS-ELIMINAR',
        'nombre' => 'Indicador a eliminar',
        'tipo' => 'IAAPS',
    ]);

    $est = Establecimiento::factory()->create();

    $medida = IndicadorMedida::create([
        'indicadores_id' => $indicador->id,
        'establecimientos_id' => $est->id,
        'ano' => 2026,
        'mes' => 10,
        'numerador_value' => 10,
        'denominador_value' => 20,
        'porcentaje_cumplimiento' => 50,
        'status' => 'Cumpliendo',
    ]);

    $response = $this->actingAs($this->user)
        ->delete(route('iaaps-metas.destroy', $indicador));

    $response->assertRedirect(route('iaaps-metas.index'));
    $response->assertSessionHas('status');

    $this->assertDatabaseMissing('indicadores', ['id' => $indicador->id]);
    $this->assertDatabaseMissing('indicador_medidas', ['id' => $medida->id]);
});
