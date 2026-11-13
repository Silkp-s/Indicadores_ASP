<?php

use App\Models\Alerta;
use App\Models\AlertaRegla;
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

test('un usuario no autenticado no puede acceder a reportes', function () {
    $this->get(route('reportes.index'))
        ->assertRedirect(route('login'));
});

test('un usuario autenticado puede ver el selector de reportes', function () {
    $this->actingAs($this->user)
        ->get(route('reportes.index'))
        ->assertOk()
        ->assertSee('Reportes')
        ->assertSee('Exportar Excel')
        ->assertSee('Exportar PDF');
});

test('exporta el reporte mensual en Excel con los datos del período', function () {
    $est = Establecimiento::factory()->create(['nombre' => 'CESFAM San Vicente']);
    $indicador = Indicador::factory()->create([
        'codigo' => 'MS-01',
        'nombre' => 'Recuperación del desarrollo psicomotor',
        'tipo' => 'Meta Sanitaria',
        'target_value' => 90,
    ]);

    IndicadorMedida::create([
        'indicadores_id' => $indicador->id,
        'establecimientos_id' => $est->id,
        'ano' => 2026,
        'mes' => 10,
        'numerador_value' => 80,
        'denominador_value' => 100,
        'porcentaje_cumplimiento' => 80,
        'status' => 'En Riesgo',
    ]);

    $this->actingAs($this->user)
        ->get(route('reportes.exportar', [
            'tipo' => 'mensual',
            'formato' => 'xlsx',
            'ano' => 2026,
            'mes' => 10,
        ]))
        ->assertOk()
        ->assertDownload('Reporte_Mensual_2026_10.xlsx')
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

test('exporta el reporte de alertas en PDF con la bandeja del período', function () {
    $est = Establecimiento::factory()->create(['nombre' => 'CESFAM Los Cerros']);
    $indicador = Indicador::factory()->create(['codigo' => 'MS-02', 'tipo' => 'Meta Sanitaria']);

    $regla = AlertaRegla::create([
        'codigo' => 'REG-01',
        'nombre' => 'Incumplimiento crítico de meta',
        'descripcion' => 'El avance es inferior al 85 % de la meta.',
        'severidad' => 'Crítica',
        'is_active' => true,
    ]);

    Alerta::create([
        'alerta_regla_id' => $regla->id,
        'indicadores_id' => $indicador->id,
        'establecimientos_id' => $est->id,
        'ano' => 2026,
        'mes' => 10,
        'mensaje' => 'CESFAM Los Cerros · MS-02: avance por debajo del 85 % tolerable.',
        'brecha_detectada' => 20,
        'estado' => 'Pendiente',
    ]);

    $this->actingAs($this->user)
        ->get(route('reportes.exportar', [
            'tipo' => 'alertas',
            'formato' => 'pdf',
            'ano' => 2026,
            'mes' => 10,
        ]))
        ->assertOk()
        ->assertDownload('Reporte_Alertas_2026_10.pdf')
        ->assertHeader('content-type', 'application/pdf');
});

test('exporta el reporte trimestral en Excel aun sin mediciones', function () {
    $this->actingAs($this->user)
        ->get(route('reportes.exportar', [
            'tipo' => 'trimestral',
            'formato' => 'xlsx',
            'ano' => 2026,
            'trimestre' => 3,
        ]))
        ->assertOk()
        ->assertDownload('Reporte_Trimestral_2026_T3.xlsx');
});
