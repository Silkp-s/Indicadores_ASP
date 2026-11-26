<?php

use App\Models\Configuracion;
use App\Models\User;
use App\Services\RemReaderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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

test('un usuario no autenticado no puede acceder a configuración', function () {
    $this->get(route('configuracion'))->assertRedirect(route('login'));
});

test('un usuario ve su configuración personal pero no los parámetros del sistema', function () {
    $this->actingAs($this->usuario)
        ->get(route('configuracion'))
        ->assertOk()
        ->assertSee('Mi perfil')
        ->assertSee('Cambiar contraseña')
        ->assertDontSee('Parámetros del sistema');
});

test('un administrador ve también los parámetros del sistema', function () {
    $this->actingAs($this->admin)
        ->get(route('configuracion'))
        ->assertOk()
        ->assertSee('Parámetros del sistema');
});

test('el usuario puede actualizar sus datos personales', function () {
    $this->actingAs($this->usuario)
        ->put(route('configuracion.perfil'), [
            'name' => 'Nuevo',
            'apellido' => 'Apellido',
            'email' => 'nuevo@das.cl',
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    $this->usuario->refresh();
    expect($this->usuario->name)->toBe('Nuevo')
        ->and($this->usuario->email)->toBe('nuevo@das.cl');
});

test('el usuario puede cambiar su contraseña validando la actual', function () {
    $this->usuario->update(['password' => Hash::make('actual123')]);

    $this->actingAs($this->usuario)
        ->put(route('configuracion.password'), [
            'current_password' => 'actual123',
            'password' => 'nueva1234',
            'password_confirmation' => 'nueva1234',
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    expect(Hash::check('nueva1234', $this->usuario->fresh()->password))->toBeTrue();
});

test('no se cambia la contraseña si la actual es incorrecta', function () {
    $this->usuario->update(['password' => Hash::make('actual123')]);

    $this->actingAs($this->usuario)
        ->put(route('configuracion.password'), [
            'current_password' => 'incorrecta',
            'password' => 'nueva1234',
            'password_confirmation' => 'nueva1234',
        ])
        ->assertSessionHasErrors('current_password');
});

test('un usuario regular no puede actualizar los parámetros del sistema', function () {
    $this->actingAs($this->usuario)
        ->put(route('configuracion.sistema'), [
            'umbral_critico' => 90,
            'ano_trabajo' => 2026,
        ])
        ->assertForbidden();
});

test('un administrador puede actualizar los parámetros del sistema', function () {
    $this->actingAs($this->admin)
        ->put(route('configuracion.sistema'), [
            'umbral_critico' => 90,
            'ano_trabajo' => 2025,
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    expect(Configuracion::umbralCritico())->toBe(90.0)
        ->and(Configuracion::anoTrabajo())->toBe(2025);
});

test('el umbral crítico configurable afecta el semáforo de cumplimiento', function () {
    $service = app(RemReaderService::class);

    // Con el umbral por defecto (85 %): 88 % de la meta queda En Riesgo.
    expect($service->calculateCompliance(88, 100, 100, 'Porcentaje')['estado'])->toBe('En Riesgo');

    // Con umbral 90 %: 88 % queda Crítico.
    Configuracion::guardar('umbral_critico', 90);

    expect($service->calculateCompliance(88, 100, 100, 'Porcentaje')['estado'])->toBe('Crítico');
});
