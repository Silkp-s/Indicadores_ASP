<?php

use App\Models\Establecimiento;
use App\Models\Indicador;
use App\Models\IndicadorMedida;
use App\Models\User;
use App\Services\ProyeccionEstadisticaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Usuario']);
    Role::firstOrCreate(['name' => 'Admin']);

    $this->user = User::factory()->create();
    $this->user->assignRole('Usuario');

    $this->service = app(ProyeccionEstadisticaService::class);

    $this->cesfam = Establecimiento::create([
        'nombre' => 'CESFAM Los Cerros',
        'codigo_deis' => '119308',
        'tipo' => 'CESFAM',
        'poblacion_inscrita' => 35000,
        'is_active' => true,
    ]);

    $this->indicadorPositivo = Indicador::create([
        'codigo' => 'MS-01',
        'nombre' => 'Recuperación del desarrollo psicomotor en niños de 12 a 23 meses',
        'tipo' => 'Meta Sanitaria',
        'target_value' => 80.00,
        'periodicidad' => 'Mensual',
        'fu' => 'REM-A',
        'is_active' => true,
    ]);

    $this->indicadorNegativo = Indicador::create([
        'codigo' => 'MS-03',
        'nombre' => 'Cobertura de vacunación escolar',
        'tipo' => 'Meta Sanitaria',
        'target_value' => 90.00,
        'periodicidad' => 'Mensual',
        'fu' => 'REM-P',
        'is_active' => true,
    ]);
});

test('calcula regresión lineal simple con tendencia positiva correctamente', function () {
    // 6 meses con incremento constante de 10% por mes (10% a 60%)
    $puntos = [
        1 => 10.0,
        2 => 20.0,
        3 => 30.0,
        4 => 40.0,
        5 => 50.0,
        6 => 60.0,
    ];

    $resultado = $this->service->calcularRegresionLineal($puntos);

    expect($resultado['pendiente'])->toBe(10.0)
        ->and($resultado['intercepto'])->toBe(0.0)
        ->and($resultado['r2'])->toBe(1.0)
        ->and($resultado['tendencia'])->toBe('Positiva')
        ->and($resultado['proyeccion_mes_12'])->toBe(120.0)
        ->and($resultado['curva_proyectada'][12])->toBe(120.0);
});

test('calcula regresión lineal simple con tendencia negativa correctamente', function () {
    // 3 meses con caída
    $puntos = [
        1 => 60.0,
        2 => 50.0,
        3 => 40.0,
    ];

    $resultado = $this->service->calcularRegresionLineal($puntos);

    expect($resultado['pendiente'])->toBe(-10.0)
        ->and($resultado['intercepto'])->toBe(70.0)
        ->and($resultado['tendencia'])->toBe('Negativa')
        ->and($resultado['proyeccion_mes_12'])->toBe(0.0); // Clamped a 0
});

test('calcula media móvil simple SMA-3 y velocidad de cambio', function () {
    $puntos = [
        1 => 20.0,
        2 => 30.0,
        3 => 40.0,
        4 => 50.0,
        5 => 60.0,
        6 => 70.0,
    ];

    $resultado = $this->service->calcularMediaMovil($puntos, 3);

    // Ventana de los últimos 3 meses: meses 4, 5, 6 (valores 50, 60, 70)
    // SMA = (50 + 60 + 70) / 3 = 60.0
    // Velocidad mensual = (70 - 50) / (6 - 4) = 10.0 por mes
    // Meses restantes = 12 - 6 = 6
    // Proyección mes 12 = 70 + (10.0 * 6) = 130.0
    expect($resultado['sma_reciente'])->toBe(60.0)
        ->and($resultado['velocidad_mensual'])->toBe(10.0)
        ->and($resultado['proyeccion_mes_12'])->toBe(130.0)
        ->and($resultado['ventana_usada'])->toBe(3);
});

test('proyectarIndicador genera estado Cumplirá cuando la tendencia supera la meta a diciembre', function () {
    $puntos = [
        1 => 40.0,
        2 => 50.0,
        3 => 60.0,
        4 => 70.0,
    ];

    $proyeccion = $this->service->proyectarIndicador($this->indicadorPositivo, $puntos, 4);

    expect($proyeccion['estado_anticipado'])->toBe('Cumplirá')
        ->and($proyeccion['proyeccion_anual'])->toBeGreaterThanOrEqual(80.0)
        ->and($proyeccion['alerta_ejecutiva'])->toContain('Cumplimiento anticipado')
        ->and($proyeccion['mes_logro_estimado'])->toBeLessThanOrEqual(12);
});

test('proyectarIndicador genera estado Crítico cuando la proyección a diciembre está bajo el 85% de la meta', function () {
    $puntos = [
        1 => 15.0,
        2 => 14.0,
        3 => 13.0,
    ];

    $proyeccion = $this->service->proyectarIndicador($this->indicadorNegativo, $puntos, 3);

    expect($proyeccion['estado_anticipado'])->toBe('Crítico')
        ->and($proyeccion['proyeccion_anual'])->toBeLessThan(90.0 * 0.85)
        ->and($proyeccion['alerta_ejecutiva'])->toContain('Alerta crítica');
});

test('la vista /centro-mando carga el motor de proyección estadística con KPIs y gráficos', function () {
    // Crear mediciones en varios meses para 2026
    for ($m = 1; $m <= 6; $m++) {
        IndicadorMedida::create([
            'indicadores_id' => $this->indicadorPositivo->id,
            'establecimientos_id' => $this->cesfam->id,
            'ano' => 2026,
            'mes' => $m,
            'numerador_value' => 50 + ($m * 5),
            'denominador_value' => 100,
            'porcentaje_cumplimiento' => 50.0 + ($m * 5),
            'status' => 'Cumpliendo',
        ]);
    }

    $response = $this->actingAs($this->user)
        ->get(route('centro-mando', ['periodo' => '2026-06']));

    $response->assertOk()
        ->assertSee('Motor de Proyección Estadística · Estimación de Cierre Anual')
        ->assertSee('Regresión Lineal Simple + Media Móvil (SMA-3)')
        ->assertSee('Horizonte: Mes 12 (Diciembre 2026)')
        ->assertSee('chart-proyeccion-anual')
        ->assertSee('Éxito Proyectado (Meta Lograda)')
        ->assertSee('Cumplirán Meta')
        ->assertSee('En Riesgo al Cierre')
        ->assertSee('Críticos al Cierre')
        ->assertSee('MS-01')
        ->assertSee('tabulados de 5 en 5')
        ->assertSee('proyecciones-paginacion-container')
        ->assertSee('btn-proy-prev')
        ->assertSee('btn-proy-next');
});
