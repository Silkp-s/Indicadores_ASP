<?php

use App\Models\Notificacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Usuario']);
    Role::firstOrCreate(['name' => 'Admin']);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('Admin');
});

test('una accion del admin registra una sola notificacion persistente', function () {
    $victima = User::factory()->create();
    $victima->assignRole('Usuario');

    $this->actingAs($this->admin)
        ->delete(route('admin.users.destroy', $victima))
        ->assertRedirect(route('admin.dashboard', ['tab' => 'usuarios']));

    expect(Notificacion::count())->toBe(1);

    // La página de destino (GET) muestra el flash pero no duplica la notificación.
    $this->actingAs($this->admin)
        ->get(route('admin.dashboard', ['tab' => 'usuarios']))
        ->assertOk();

    expect(Notificacion::count())->toBe(1)
        ->and($this->admin->notificaciones()->first()->mensaje)
        ->toBe("Usuario {$victima->name} eliminado correctamente.");
});

test('la campana muestra las notificaciones y el contador de no leidas', function () {
    $this->admin->notificaciones()->create([
        'tipo' => 'success',
        'mensaje' => 'Registro eliminado exitosamente',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('id="notificaciones-badge"', false)
        ->assertSee('Registro eliminado exitosamente');
});

test('se pueden marcar todas las notificaciones como leidas', function () {
    $this->admin->notificaciones()->create(['tipo' => 'success', 'mensaje' => 'Una']);
    $this->admin->notificaciones()->create(['tipo' => 'warning', 'mensaje' => 'Dos']);

    $this->actingAs($this->admin)
        ->postJson(route('notificaciones.leer'))
        ->assertOk();

    expect($this->admin->notificaciones()->noLeidas()->count())->toBe(0)
        ->and($this->admin->notificaciones()->whereNotNull('leida_en')->count())->toBe(2);
});

test('al marcar como leidas desaparece el contador de la campana', function () {
    $this->admin->notificaciones()->create(['tipo' => 'success', 'mensaje' => 'Hola']);

    $this->actingAs($this->admin)
        ->postJson(route('notificaciones.leer'))
        ->assertOk();

    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertDontSee('id="notificaciones-badge"', false)
        ->assertSee('Hola');
});

test('las notificaciones son privadas de cada usuario', function () {
    $otro = User::factory()->create();
    $otro->assignRole('Admin');
    $otro->notificaciones()->create(['tipo' => 'success', 'mensaje' => 'Solo para el otro']);

    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertDontSee('Solo para el otro');
});

test('una peticion GET no genera notificaciones', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertOk();

    expect(Notificacion::count())->toBe(0);
});
