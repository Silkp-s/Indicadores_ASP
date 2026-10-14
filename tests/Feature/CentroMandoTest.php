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

    $this->establecimiento1 = Establecimiento::create([
        'nombre' => 'CESFAM Los Cerros',
        'codigo_deis' => '119308',
        'tipo' => 'CESFAM',
        'poblacion_inscrita' => 35000,
        'is_active' => true,
    ]);

    $this->establecimiento2 = Establecimiento::create([
        'nombre' => 'CESFAM San Vicente',
        'codigo_deis' => '119301',
        'tipo' => 'CESFAM',
        'poblacion_inscrita' => 28000,
        'is_active' => true,
    ]);

    $this->indicadorMeta = Indicador::create([
        'codigo' => 'MS-02',
        'nombre' => 'Cobertura de PAP vigente en mujeres de 25 a 64 años',
        'tipo' => 'Meta Sanitaria',
        'numerador_description' => 'Mujeres con PAP vigente',
        'denominador_description' => 'Mujeres inscritas',
        'target_value' => 80.00,
        'periodicidad' => 'Semestral',
        'fu' => 'REM-P',
        'is_active' => true,
    ]);

    $this->indicadorIaaps = Indicador::create([
        'codigo' => 'IAAPS-12',
        'nombre' => 'Ingreso precoz de mujeres a control de embarazo antes de 14 sem',
        'tipo' => 'IAAPS',
        'numerador_description' => 'Gestantes ingresadas antes de 14 sem',
        'denominador_description' => 'Total gestantes ingresadas',
        'target_value' => 85.00,
        'periodicidad' => 'Mensual',
        'fu' => 'REM-A',
        'is_active' => true,
    ]);

    $this->indicadorCritico = Indicador::create([
        'codigo' => 'MS-04',
        'nombre' => 'Compensación de Diabetes Mellitus 2',
        'tipo' => 'Meta Sanitaria',
        'numerador_description' => 'Personas DM2 compensadas',
        'denominador_description' => 'Población estimada DM2',
        'target_value' => 70.00,
        'periodicidad' => 'Semestral',
        'fu' => 'REM-P',
        'is_active' => true,
    ]);

    // Crear mediciones para periodo 2026-06
    // 1. MS-02: Crítico (3.28%)
    IndicadorMedida::create([
        'indicadores_id' => $this->indicadorMeta->id,
        'establecimientos_id' => $this->establecimiento1->id,
        'ano' => 2026,
        'mes' => 6,
        'numerador_value' => 328,
        'denominador_value' => 10000,
        'porcentaje_cumplimiento' => 3.28,
        'status' => 'Crítico',
    ]);

    // 2. IAAPS-12: Cumpliendo (100.0%)
    IndicadorMedida::create([
        'indicadores_id' => $this->indicadorIaaps->id,
        'establecimientos_id' => $this->establecimiento1->id,
        'ano' => 2026,
        'mes' => 6,
        'numerador_value' => 9,
        'denominador_value' => 9,
        'porcentaje_cumplimiento' => 100.00,
        'status' => 'Cumpliendo',
    ]);

    // 3. MS-04: En Riesgo (60.0% vs meta 70.0%)
    IndicadorMedida::create([
        'indicadores_id' => $this->indicadorCritico->id,
        'establecimientos_id' => $this->establecimiento1->id,
        'ano' => 2026,
        'mes' => 6,
        'numerador_value' => 60,
        'denominador_value' => 100,
        'porcentaje_cumplimiento' => 60.00,
        'status' => 'En Riesgo',
    ]);
});

test('la ruta /centro-mando redirige al login si no está autenticado', function () {
    $this->get(route('centro-mando'))
        ->assertRedirect(route('login'));
});

test('la ruta /centro-mando carga con éxito para usuarios autenticados', function () {
    $response = $this->actingAs($this->user)
        ->get(route('centro-mando'));

    $response->assertOk()
        ->assertSee('Centro de Mando Ejecutivo')
        ->assertSee('Supervisión comunal de cumplimiento');
});

test('el centro de mando muestra KPIs ejecutivos calculados', function () {
    $response = $this->actingAs($this->user)
        ->get(route('centro-mando'));

    $response->assertOk()
        ->assertSee('Cumplimiento Promedio')
        // Promedio de 3.28, 100.00 y 60.00 = 54.4%
        ->assertSee('54.4%')
        // Semáforo: 1 cumpliendo, 1 en riesgo, 1 crítico
        ->assertSee('Semáforo Comunal')
        ->assertSee('Población Beneficiaria')
        ->assertSee('CESFAM Los Cerros')
        ->assertSee('Metas Ley 19.813')
        ->assertSee('IAAPS 2025');
});

test('el centro de mando destaca los indicadores críticos y en riesgo del momento', function () {
    $response = $this->actingAs($this->user)
        ->get(route('centro-mando'));

    $response->assertOk()
        ->assertSee('Indicadores Críticos y en Riesgo del Momento')
        ->assertSee('MS-02')
        ->assertSee('MS-04')
        ->assertSee('3.3%')
        ->assertSee('60.0%')
        ->assertSee('Brecha: -76.7%')
        ->assertSee('Brecha: -10%');
});

test('los filtros por tipo de indicador funcionan en el centro de mando', function () {
    // Filtrar solo IAAPS
    $responseIaaps = $this->actingAs($this->user)
        ->get(route('centro-mando', ['tipo' => 'IAAPS']));

    $responseIaaps->assertOk()
        ->assertSee('IAAPS-12')
        ->assertDontSee('MS-02');

    // Filtrar solo Metas Sanitarias
    $responseMetas = $this->actingAs($this->user)
        ->get(route('centro-mando', ['tipo' => 'Meta Sanitaria']));

    $responseMetas->assertOk()
        ->assertSee('MS-02')
        ->assertSee('MS-04')
        ->assertDontSee('IAAPS-12');
});

test('el filtro de estado criticos muestra solo indicadores en riesgo o criticos', function () {
    $response = $this->actingAs($this->user)
        ->get(route('centro-mando', ['estado' => 'criticos']));

    $response->assertOk()
        ->assertSee('MS-02')
        ->assertSee('MS-04')
        ->assertDontSee('IAAPS-12'); // IAAPS-12 está Cumpliendo
});

test('el filtro por establecimiento funciona en el centro de mando', function () {
    // Para establecimiento2 no hay mediciones
    $response = $this->actingAs($this->user)
        ->get(route('centro-mando', ['establecimiento_id' => $this->establecimiento2->id]));

    $response->assertOk()
        ->assertSee('No hay mediciones registradas para este periodo y filtros')
        ->assertSee('CESFAM San Vicente');
});
