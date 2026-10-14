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

test('un usuario no autenticado no puede acceder a establecimientos', function () {
    $this->get(route('establecimientos.index'))
        ->assertRedirect(route('login'));
});

test('un usuario autenticado puede ver el listado de establecimientos', function () {
    $est1 = Establecimiento::factory()->create(['nombre' => 'CESFAM San Vicente', 'codigo_deis' => '119302', 'tipo' => 'CESFAM']);
    $est2 = Establecimiento::factory()->create(['nombre' => 'CECOSF Los Lobos', 'codigo_deis' => '119711', 'tipo' => 'CECOSF']);

    $this->actingAs($this->user)
        ->get(route('establecimientos.index'))
        ->assertOk()
        ->assertSee('Establecimientos de Salud')
        ->assertSee('CESFAM San Vicente')
        ->assertSee('119302')
        ->assertSee('CECOSF Los Lobos')
        ->assertSee('119711');
});

test('se pueden filtrar los establecimientos por búsqueda de texto y código DEIS', function () {
    Establecimiento::factory()->create(['nombre' => 'CESFAM Paulina Avendaño', 'codigo_deis' => '119307']);
    Establecimiento::factory()->create(['nombre' => 'CESFAM Los Cerros', 'codigo_deis' => '119308']);

    // Buscar por nombre
    $this->actingAs($this->user)
        ->get(route('establecimientos.index', ['q' => 'Paulina']))
        ->assertOk()
        ->assertSee('CESFAM Paulina Avendaño')
        ->assertDontSee('CESFAM Los Cerros');

    // Buscar por DEIS
    $this->actingAs($this->user)
        ->get(route('establecimientos.index', ['q' => '119308']))
        ->assertOk()
        ->assertSee('CESFAM Los Cerros')
        ->assertDontSee('CESFAM Paulina Avendaño');
});

test('se pueden filtrar los establecimientos por tipo y estado', function () {
    Establecimiento::factory()->create(['nombre' => 'CESFAM Uno', 'tipo' => 'CESFAM', 'is_active' => true]);
    Establecimiento::factory()->create(['nombre' => 'SAR Dos', 'tipo' => 'SAR', 'is_active' => true]);
    Establecimiento::factory()->create(['nombre' => 'CECOSF Tres', 'tipo' => 'CECOSF', 'is_active' => false]);

    // Filtrar por tipo SAR
    $this->actingAs($this->user)
        ->get(route('establecimientos.index', ['tipo' => 'SAR']))
        ->assertOk()
        ->assertSee('SAR Dos')
        ->assertDontSee('CESFAM Uno')
        ->assertDontSee('CECOSF Tres');

    // Filtrar por inactivos
    $this->actingAs($this->user)
        ->get(route('establecimientos.index', ['estado' => 'inactivo']))
        ->assertOk()
        ->assertSee('CECOSF Tres')
        ->assertDontSee('SAR Dos')
        ->assertDontSee('CESFAM Uno');
});

test('un usuario puede crear un nuevo establecimiento con datos válidos', function () {
    $datos = [
        'nombre' => 'CESFAM Talcahuano Centro',
        'codigo_deis' => '119999',
        'tipo' => 'CESFAM',
        'poblacion_inscrita' => 32000,
        'is_active' => 1,
    ];

    $response = $this->actingAs($this->user)
        ->post(route('establecimientos.store'), $datos);

    $response->assertRedirect(route('establecimientos.index', ['q' => '119999']));
    $response->assertSessionHas('status');

    $this->assertDatabaseHas('establecimientos', [
        'nombre' => 'CESFAM Talcahuano Centro',
        'codigo_deis' => '119999',
        'tipo' => 'CESFAM',
        'poblacion_inscrita' => 32000,
        'is_active' => true,
    ]);
});

test('falla la validación al crear un establecimiento con datos inválidos o incompletos', function () {
    $response = $this->actingAs($this->user)
        ->post(route('establecimientos.store'), [
            'nombre' => '',
            'tipo' => 'INEXISTENTE',
            'poblacion_inscrita' => -50,
        ]);

    $response->assertSessionHasErrors(['nombre', 'tipo', 'poblacion_inscrita']);
});

test('falla la creación si el código DEIS ya pertenece a otro establecimiento', function () {
    Establecimiento::factory()->create(['codigo_deis' => '119302']);

    $response = $this->actingAs($this->user)
        ->post(route('establecimientos.store'), [
            'nombre' => 'Nuevo Centro Repetido',
            'codigo_deis' => '119302',
            'tipo' => 'CECOSF',
            'poblacion_inscrita' => 5000,
            'is_active' => 1,
        ]);

    $response->assertSessionHasErrors(['codigo_deis']);
});

test('un usuario puede editar un establecimiento existente', function () {
    $est = Establecimiento::factory()->create([
        'nombre' => 'CESFAM Original',
        'codigo_deis' => '119100',
        'tipo' => 'CESFAM',
        'poblacion_inscrita' => 20000,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->user)
        ->put(route('establecimientos.update', $est), [
            'nombre' => 'CESFAM Renombrado',
            'codigo_deis' => '119100',
            'tipo' => 'CESFAM',
            'poblacion_inscrita' => 25000,
            'is_active' => 1,
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('status');

    $this->assertDatabaseHas('establecimientos', [
        'id' => $est->id,
        'nombre' => 'CESFAM Renombrado',
        'poblacion_inscrita' => 25000,
    ]);
});

test('se puede cambiar rápidamente el estado activo o inactivo de un establecimiento', function () {
    $est = Establecimiento::factory()->create(['is_active' => true]);

    $response = $this->actingAs($this->user)
        ->patch(route('establecimientos.estado', $est), ['is_active' => 0]);

    $response->assertRedirect();
    $response->assertSessionHas('status');
    expect($est->fresh()->is_active)->toBeFalse();

    $this->actingAs($this->user)
        ->patch(route('establecimientos.estado', $est), ['is_active' => 1]);
    expect($est->fresh()->is_active)->toBeTrue();
});

test('un usuario puede eliminar un establecimiento que no tiene mediciones vinculadas', function () {
    $est = Establecimiento::factory()->create(['nombre' => 'CECOSF Temporal']);

    $response = $this->actingAs($this->user)
        ->delete(route('establecimientos.destroy', $est));

    $response->assertRedirect(route('establecimientos.index'));
    $response->assertSessionHas('status');

    $this->assertDatabaseMissing('establecimientos', ['id' => $est->id]);
});

test('no se puede eliminar un establecimiento si cuenta con mediciones registradas', function () {
    $est = Establecimiento::factory()->create(['nombre' => 'CESFAM Con Mediciones']);
    $ind = Indicador::factory()->create();

    IndicadorMedida::create([
        'indicadores_id' => $ind->id,
        'establecimientos_id' => $est->id,
        'ano' => 2026,
        'mes' => 6,
        'numerador_value' => 100,
        'denominador_value' => 200,
        'porcentaje_cumplimiento' => 50,
        'status' => 'En Riesgo',
    ]);

    $response = $this->actingAs($this->user)
        ->delete(route('establecimientos.destroy', $est));

    $response->assertRedirect();
    $response->assertSessionHasErrors(['establecimiento']);

    // Verifica que el establecimiento no fue eliminado
    $this->assertDatabaseHas('establecimientos', ['id' => $est->id]);
});
