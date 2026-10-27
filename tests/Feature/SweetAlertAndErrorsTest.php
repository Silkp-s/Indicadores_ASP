<?php

use App\Models\Establecimiento;
use App\Models\Indicador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Usuario']);
    Role::firstOrCreate(['name' => 'Admin']);

    $this->usuario = User::factory()->create();
    $this->usuario->assignRole('Usuario');

    $this->admin = User::factory()->create();
    $this->admin->assignRole('Admin');
});

test('ruta inexistente responde 404 y renderiza la vista de error con SweetAlert2', function () {
    $response = $this->actingAs($this->usuario)->get('/ruta-totalmente-inexistente-404');

    $response->assertStatus(404)
        ->assertSee('Código HTTP 404')
        ->assertSee('Página o Registro No Encontrado')
        ->assertSee('Swal.fire', false)
        ->assertSee('sweetalert2@11', false);
});

test('usuario sin rol admin es redirigido con mensaje flash de error 403 al intentar acceder a /admin', function () {
    $response = $this->actingAs($this->usuario)->get(route('admin.dashboard'));

    $response->assertRedirect(route('centro-mando'))
        ->assertSessionHas('error', 'Acceso denegado');
});

test('peticion ajax sin permisos responde 403 y vista 403 contiene SweetAlert2', function () {
    $response = $this->actingAs($this->usuario)
        ->getJson(route('admin.dashboard'));

    $response->assertStatus(403);
});

test('la vista 403 de error contiene el template personalizado y disparador SweetAlert2', function () {
    $response = $this->actingAs($this->usuario)
        ->view('errors.403');

    $response->assertSee('Código HTTP 403')
        ->assertSee('Acceso Restringido o Denegado')
        ->assertSee('Swal.fire', false)
        ->assertSee('sweetalert2@11', false);
});

test('el layout principal incluye el CDN de SweetAlert2 y el interceptor global de eliminacion', function () {
    $response = $this->actingAs($this->usuario)->get(route('centro-mando'));

    $response->assertOk()
        ->assertSee('sweetalert2@11', false)
        ->assertSee('form-eliminar', false)
        ->assertSee('Swal.fire', false)
        ->assertSee('¿Confirmas que deseas eliminar este registro?', false);
});

test('formulario de eliminacion en establecimientos incluye atributos de confirmacion SweetAlert', function () {
    $est = Establecimiento::create([
        'nombre' => 'CESFAM Prueba SweetAlert',
        'codigo_deis' => '998877',
        'tipo' => 'CESFAM',
        'poblacion_inscrita' => 12000,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->usuario)->get(route('establecimientos.index'));

    $response->assertOk()
        ->assertSee('form-eliminar')
        ->assertSee('data-swal-title="¿Eliminar establecimiento?"', false)
        ->assertSee('data-swal-confirm="Sí, eliminar"', false);
});

test('modulo iaaps-metas cuenta con boton de eliminacion protegido con confirmacion SweetAlert', function () {
    $ind = Indicador::create([
        'codigo' => 'MS-TEST',
        'nombre' => 'Meta de Prueba SweetAlert',
        'tipo' => 'Meta Sanitaria',
        'target_value' => 85,
        'periodicidad' => 'Mensual',
        'fu' => 'REM-A',
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->admin)->get(route('iaaps-metas.index'));

    $response->assertOk()
        ->assertSee('form-eliminar')
        ->assertSee('data-swal-title="¿Eliminar indicador MS-TEST?"', false);
});
