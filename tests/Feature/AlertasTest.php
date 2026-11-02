<?php

use App\Models\Alerta;
use App\Models\AlertaRegla;
use App\Models\Establecimiento;
use App\Models\Indicador;
use App\Models\IndicadorMedida;
use App\Models\User;
use App\Services\AlertaEngine;
use Database\Seeders\AlertaReglaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Usuario']);
    Role::firstOrCreate(['name' => 'Admin']);

    $this->user = User::factory()->create();
    $this->user->assignRole('Usuario');

    $this->seed(AlertaReglaSeeder::class);
});

function nuevoIndicador(array $attrs = []): Indicador
{
    return Indicador::factory()->create($attrs + ['tipo' => 'Meta Sanitaria', 'target_value' => 90, 'is_active' => true]);
}

function nuevoCesfam(array $attrs = []): Establecimiento
{
    return Establecimiento::factory()->create($attrs + ['tipo' => 'CESFAM', 'is_active' => true]);
}

function medir(Indicador $indicador, Establecimiento $est, int $mes, float $pct, string $status, float $num = 50, float $den = 100): IndicadorMedida
{
    return IndicadorMedida::create([
        'indicadores_id' => $indicador->id,
        'establecimientos_id' => $est->id,
        'ano' => 2026,
        'mes' => $mes,
        'numerador_value' => $num,
        'denominador_value' => $den,
        'porcentaje_cumplimiento' => $pct,
        'status' => $status,
    ]);
}

function evaluar(int $mes = 3): array
{
    return app(AlertaEngine::class)->evaluar(2026, $mes);
}

function alertasDe(string $codigo)
{
    return Alerta::whereHas('regla', fn ($q) => $q->where('codigo', $codigo))->get();
}

test('el seeder carga las 10 reglas activas con su severidad', function () {
    expect(AlertaRegla::count())->toBe(10)
        ->and(AlertaRegla::where('is_active', true)->count())->toBe(10)
        ->and(AlertaRegla::where('codigo', 'REG-01')->value('severidad'))->toBe('Crítica')
        ->and(AlertaRegla::where('codigo', 'REG-07')->value('severidad'))->toBe('Informativa');

    // Ejecutarlo de nuevo no duplica
    $this->seed(AlertaReglaSeeder::class);
    expect(AlertaRegla::count())->toBe(10);
});

test('un usuario no autenticado es redirigido al login', function () {
    $this->get(route('alertas.index'))->assertRedirect(route('login'));
});

test('REG-01 detecta un indicador crítico con su brecha', function () {
    $ind = nuevoIndicador(['codigo' => 'MS-01']);
    $est = nuevoCesfam();
    medir($ind, $est, 3, 45, 'Crítico'); // 45 / 90 = 50 % de la meta

    evaluar();

    $alerta = alertasDe('REG-01')->first();
    expect($alerta)->not->toBeNull()
        ->and($alerta->estado)->toBe('Pendiente')
        ->and($alerta->establecimientos_id)->toBe($est->id)
        ->and($alerta->indicadores_id)->toBe($ind->id)
        ->and((float) $alerta->brecha_detectada)->toBe(50.0);
});

test('REG-02 y REG-07 se reparten los indicadores en riesgo según su cercanía a la meta', function () {
    $lejos = nuevoIndicador(['codigo' => 'MS-02']);
    $cerca = nuevoIndicador(['codigo' => 'MS-03']);
    $est = nuevoCesfam();

    medir($lejos, $est, 3, 80, 'En Riesgo'); // 88,9 % de la meta
    medir($cerca, $est, 3, 87, 'En Riesgo'); // 96,7 % de la meta

    evaluar();

    expect(alertasDe('REG-02'))->toHaveCount(1)
        ->and(alertasDe('REG-02')->first()->indicadores_id)->toBe($lejos->id)
        ->and(alertasDe('REG-07'))->toHaveCount(1)
        ->and(alertasDe('REG-07')->first()->indicadores_id)->toBe($cerca->id);
});

test('REG-03 detecta tres meses consecutivos a la baja', function () {
    $ind = nuevoIndicador();
    $est = nuevoCesfam();
    medir($ind, $est, 1, 85, 'En Riesgo');
    medir($ind, $est, 2, 80, 'En Riesgo');
    medir($ind, $est, 3, 75, 'Crítico');

    evaluar();

    expect(alertasDe('REG-03'))->toHaveCount(1);
});

test('REG-03 no se dispara si el indicador se recupera', function () {
    $ind = nuevoIndicador();
    $est = nuevoCesfam();
    medir($ind, $est, 1, 85, 'En Riesgo');
    medir($ind, $est, 2, 80, 'En Riesgo');
    medir($ind, $est, 3, 95, 'Cumpliendo');

    evaluar();

    expect(alertasDe('REG-03'))->toHaveCount(0);
});

test('REG-04 avisa del establecimiento que no cargó ninguna medición', function () {
    $ind = nuevoIndicador();
    $conDatos = nuevoCesfam();
    $sinDatos = nuevoCesfam();
    medir($ind, $conDatos, 3, 95, 'Cumpliendo');

    evaluar();

    $alertas = alertasDe('REG-04');
    expect($alertas)->toHaveCount(1)
        ->and($alertas->first()->establecimientos_id)->toBe($sinDatos->id)
        ->and($alertas->first()->indicadores_id)->toBeNull();
});

test('REG-04 avisa del indicador que se reportaba el mes anterior y dejó de reportarse', function () {
    $siempre = nuevoIndicador(['codigo' => 'MS-10']);
    $dejoDeReportar = nuevoIndicador(['codigo' => 'MS-11']);
    $est = nuevoCesfam();

    medir($siempre, $est, 2, 95, 'Cumpliendo');
    medir($dejoDeReportar, $est, 2, 95, 'Cumpliendo');
    medir($siempre, $est, 3, 95, 'Cumpliendo');

    evaluar();

    $alertas = alertasDe('REG-04');
    expect($alertas)->toHaveCount(1)
        ->and($alertas->first()->indicadores_id)->toBe($dejoDeReportar->id);
});

test('REG-05 marca como crítica una proyección anual muy por debajo de la meta', function () {
    $ind = nuevoIndicador();
    $est = nuevoCesfam();
    medir($ind, $est, 1, 54, 'Crítico');
    medir($ind, $est, 2, 45, 'Crítico');
    medir($ind, $est, 3, 36, 'Crítico');
    medir($ind, $est, 4, 27, 'Crítico');

    evaluar(4);

    expect(alertasDe('REG-05'))->toHaveCount(1);
});

test('REG-06 detecta una caída de 20 puntos o más frente al mes anterior', function () {
    $ind = nuevoIndicador();
    $est = nuevoCesfam();
    medir($ind, $est, 2, 90, 'Cumpliendo'); // 100 % de la meta
    medir($ind, $est, 3, 63, 'Crítico');    //  70 % de la meta

    evaluar();

    $alerta = alertasDe('REG-06')->first();
    expect($alerta)->not->toBeNull()
        ->and((float) $alerta->brecha_detectada)->toBe(30.0);
});

test('REG-08 marca el denominador en cero y no lo cuenta además como incumplimiento crítico', function () {
    $ind = nuevoIndicador();
    $est = nuevoCesfam();
    medir($ind, $est, 3, 0, 'Crítico', num: 0, den: 0);

    evaluar();

    expect(alertasDe('REG-08'))->toHaveCount(1)
        ->and(alertasDe('REG-01'))->toHaveCount(0);
});

test('REG-09 detecta un numerador mayor que el denominador', function () {
    $ind = nuevoIndicador();
    $est = nuevoCesfam();
    medir($ind, $est, 3, 100, 'Cumpliendo', num: 120, den: 100);

    evaluar();

    $alerta = alertasDe('REG-09')->first();
    expect($alerta)->not->toBeNull()
        ->and((float) $alerta->brecha_detectada)->toBe(20.0);
});

test('REG-10 se dispara con 3 críticos en un establecimiento y no con 2', function () {
    $est = nuevoCesfam();
    $otro = nuevoCesfam();

    foreach (range(1, 3) as $i) {
        medir(nuevoIndicador(['codigo' => "MS-2{$i}"]), $est, 3, 40, 'Crítico');
    }
    foreach (range(4, 5) as $i) {
        medir(nuevoIndicador(['codigo' => "MS-2{$i}"]), $otro, 3, 40, 'Crítico');
    }

    evaluar();

    $alertas = alertasDe('REG-10');
    expect($alertas)->toHaveCount(1)
        ->and($alertas->first()->establecimientos_id)->toBe($est->id)
        ->and($alertas->first()->indicadores_id)->toBeNull()
        ->and((float) $alertas->first()->brecha_detectada)->toBe(3.0);
});

test('el motor es idempotente y conserva la gestión de la DAS', function () {
    $ind = nuevoIndicador();
    $est = nuevoCesfam();
    medir($ind, $est, 3, 45, 'Crítico');

    evaluar();
    $alerta = alertasDe('REG-01')->first();
    $alerta->update(['estado' => 'Resuelta', 'observacion_gestion' => 'Se reforzó la agenda de controles.']);

    $total = Alerta::count();
    evaluar();

    expect(Alerta::count())->toBe($total);

    $alerta->refresh();
    expect($alerta->estado)->toBe('Resuelta')
        ->and($alerta->observacion_gestion)->toBe('Se reforzó la agenda de controles.');
});

test('al corregir el dato, la alerta pendiente sale de la bandeja pero la gestionada se conserva', function () {
    $ind = nuevoIndicador();
    $est = nuevoCesfam();
    $medida = medir($ind, $est, 3, 45, 'Crítico');

    evaluar();
    expect(alertasDe('REG-01'))->toHaveCount(1);

    $medida->update(['porcentaje_cumplimiento' => 95, 'status' => 'Cumpliendo']);
    $resultado = evaluar();

    expect(alertasDe('REG-01'))->toHaveCount(0)
        ->and($resultado['retiradas'])->toBeGreaterThanOrEqual(1);
});

test('una regla desactivada no genera alertas', function () {
    AlertaRegla::where('codigo', 'REG-01')->update(['is_active' => false]);
    $ind = nuevoIndicador();
    medir($ind, nuevoCesfam(), 3, 45, 'Crítico');

    evaluar();

    expect(alertasDe('REG-01'))->toHaveCount(0);
});

test('los establecimientos que no son CESFAM ni CECOSF se ignoran', function () {
    $ind = nuevoIndicador();
    $sapu = Establecimiento::factory()->create(['tipo' => 'SAPU', 'is_active' => true]);
    medir($ind, $sapu, 3, 45, 'Crítico');

    evaluar();

    expect(Alerta::where('establecimientos_id', $sapu->id)->count())->toBe(0);
});

test('la bandeja ordena por severidad: Crítica, Advertencia, Informativa', function () {
    $est = nuevoCesfam(['nombre' => 'CESFAM Orden']);
    $reglas = AlertaRegla::pluck('id', 'codigo');

    foreach ([['REG-07', 'Mensaje informativo'], ['REG-02', 'Mensaje advertencia'], ['REG-01', 'Mensaje critico']] as [$codigo, $texto]) {
        Alerta::create([
            'alerta_regla_id' => $reglas[$codigo],
            'establecimientos_id' => $est->id,
            'ano' => 2026,
            'mes' => 3,
            'mensaje' => $texto,
            'brecha_detectada' => 10,
        ]);
    }

    $this->actingAs($this->user)
        ->get(route('alertas.index', ['periodo' => '2026-03']))
        ->assertOk()
        ->assertSeeInOrder(['Mensaje critico', 'Mensaje advertencia', 'Mensaje informativo']);
});

test('la bandeja filtra por severidad y por estado', function () {
    $est = nuevoCesfam();
    $reglas = AlertaRegla::pluck('id', 'codigo');

    Alerta::create(['alerta_regla_id' => $reglas['REG-01'], 'establecimientos_id' => $est->id, 'ano' => 2026, 'mes' => 3, 'mensaje' => 'Alerta critica visible', 'estado' => 'Pendiente']);
    Alerta::create(['alerta_regla_id' => $reglas['REG-02'], 'establecimientos_id' => $est->id, 'ano' => 2026, 'mes' => 3, 'mensaje' => 'Alerta advertencia oculta', 'estado' => 'Pendiente']);
    Alerta::create(['alerta_regla_id' => $reglas['REG-04'], 'establecimientos_id' => $est->id, 'ano' => 2026, 'mes' => 3, 'mensaje' => 'Alerta resuelta oculta', 'estado' => 'Resuelta', 'observacion_gestion' => 'ok']);

    $this->actingAs($this->user)
        ->get(route('alertas.index', ['periodo' => '2026-03', 'severidad' => 'Crítica']))
        ->assertSee('Alerta critica visible')
        ->assertDontSee('Alerta advertencia oculta')
        ->assertDontSee('Alerta resuelta oculta'); // por defecto solo se ven las abiertas

    $this->actingAs($this->user)
        ->get(route('alertas.index', ['periodo' => '2026-03', 'estado' => 'Resuelta']))
        ->assertSee('Alerta resuelta oculta')
        ->assertDontSee('Alerta critica visible');
});

test('se puede evaluar un período desde la bandeja', function () {
    $ind = nuevoIndicador(['codigo' => 'MS-30']);
    medir($ind, nuevoCesfam(), 3, 45, 'Crítico');

    $this->actingAs($this->user)
        ->post(route('alertas.evaluar'), ['periodo' => '2026-03'])
        ->assertRedirect(route('alertas.index', ['periodo' => '2026-03']))
        ->assertSessionHas('status');

    expect(alertasDe('REG-01'))->toHaveCount(1);
});

test('evaluar rechaza un período inválido', function () {
    $this->actingAs($this->user)
        ->post(route('alertas.evaluar'), ['periodo' => '2026-13'])
        ->assertSessionHasErrors('periodo');
});

test('se puede marcar una alerta como revisada sin observación', function () {
    $alerta = Alerta::create([
        'alerta_regla_id' => AlertaRegla::where('codigo', 'REG-01')->value('id'),
        'establecimientos_id' => nuevoCesfam()->id,
        'ano' => 2026, 'mes' => 3, 'mensaje' => 'x',
    ]);

    $this->actingAs($this->user)
        ->patch(route('alertas.update', $alerta), ['estado' => 'Revisada'])
        ->assertSessionHasNoErrors();

    expect($alerta->fresh()->estado)->toBe('Revisada');
});

test('resolver una alerta exige indicar la acción correctiva', function () {
    $alerta = Alerta::create([
        'alerta_regla_id' => AlertaRegla::where('codigo', 'REG-01')->value('id'),
        'establecimientos_id' => nuevoCesfam()->id,
        'ano' => 2026, 'mes' => 3, 'mensaje' => 'x',
    ]);

    $this->actingAs($this->user)
        ->patch(route('alertas.update', $alerta), ['estado' => 'Resuelta', 'observacion_gestion' => ''])
        ->assertSessionHasErrors('observacion_gestion');

    expect($alerta->fresh()->estado)->toBe('Pendiente');

    $this->actingAs($this->user)
        ->patch(route('alertas.update', $alerta), ['estado' => 'Resuelta', 'observacion_gestion' => 'Se normalizó la carga REM.'])
        ->assertSessionHasNoErrors();

    expect($alerta->fresh())
        ->estado->toBe('Resuelta')
        ->observacion_gestion->toBe('Se normalizó la carga REM.');
});

test('rechaza un estado inexistente', function () {
    $alerta = Alerta::create([
        'alerta_regla_id' => AlertaRegla::where('codigo', 'REG-01')->value('id'),
        'establecimientos_id' => nuevoCesfam()->id,
        'ano' => 2026, 'mes' => 3, 'mensaje' => 'x',
    ]);

    $this->actingAs($this->user)
        ->patch(route('alertas.update', $alerta), ['estado' => 'Archivada'])
        ->assertSessionHasErrors('estado');
});
