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

    $this->establecimiento = Establecimiento::factory()->create([
        'nombre' => 'CESFAM Los Cerros',
        'is_active' => true,
    ]);

    $indicador = Indicador::factory()->create([
        'codigo' => 'MS-01',
        'nombre' => 'Desarrollo Psicomotor',
        'tipo' => 'Meta Sanitaria',
        'target_value' => 90,
    ]);

    IndicadorMedida::create([
        'indicadores_id' => $indicador->id,
        'establecimientos_id' => $this->establecimiento->id,
        'ano' => 2026,
        'mes' => 6,
        'numerador_value' => 80,
        'denominador_value' => 100,
        'porcentaje_cumplimiento' => 80,
        'status' => 'En Riesgo',
    ]);
});

test('el centro de mando exporta un archivo excel con los datos del periodo', function () {
    $this->actingAs($this->user)
        ->get(route('centro-mando.exportar.excel', ['periodo' => '2026-06']))
        ->assertOk()
        ->assertDownload('centro-mando-2026-06.xlsx');
});

test('el centro de mando muestra la vista imprimible con el detalle de predicciones', function () {
    $this->actingAs($this->user)
        ->get(route('centro-mando.exportar.pdf', ['periodo' => '2026-06']))
        ->assertOk()
        ->assertSee('Centro de Mando Ejecutivo')
        ->assertSee('Detalle de predicciones')
        ->assertSee('Desarrollo Psicomotor')
        ->assertSee('CESFAM Los Cerros');
});

test('un invitado no puede exportar el centro de mando', function () {
    $this->get(route('centro-mando.exportar.excel'))->assertRedirect(route('login'));
    $this->get(route('centro-mando.exportar.pdf'))->assertRedirect(route('login'));
});
