<?php

use App\Models\Establecimiento;
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

test('el módulo público es de solo lectura: no ofrece crear, editar ni eliminar', function () {
    Establecimiento::factory()->create(['nombre' => 'CESFAM San Vicente']);

    $this->actingAs($this->user)
        ->get(route('establecimientos.index'))
        ->assertOk()
        ->assertDontSee('Nuevo establecimiento')
        ->assertDontSee('Editar')
        ->assertDontSee('Eliminar')
        ->assertDontSee('Acciones');
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
