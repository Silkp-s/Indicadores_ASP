<?php

use App\Models\Establecimiento;
use App\Models\Indicador;
use App\Models\User;
use App\Services\RemReaderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Usuario']);
    Role::firstOrCreate(['name' => 'Admin']);

    $this->user = User::factory()->create();
    $this->user->assignRole('Usuario');

    $this->establecimiento = Establecimiento::create([
        'nombre' => 'CESFAM Los Cerros',
        'codigo_deis' => '119308',
        'tipo' => 'CESFAM',
        'poblacion_inscrita' => 35000,
        'is_active' => true,
    ]);

    $this->indicadores = [
        Indicador::create([
            'codigo' => 'MS-01',
            'nombre' => 'Recuperación del desarrollo psicomotor en niños y niñas de 12 a 23 meses',
            'tipo' => 'Meta Sanitaria',
            'numerador_description' => 'Niños recuperados',
            'denominador_description' => 'Niños diagnosticados con riesgo',
            'target_value' => 90.00,
            'periodicidad' => 'Mensual',
            'fu' => 'REM-A',
            'is_active' => true,
        ]),
        Indicador::create([
            'codigo' => 'MS-02',
            'nombre' => 'Cobertura de PAP vigente en mujeres de 25 a 64 años',
            'tipo' => 'Meta Sanitaria',
            'numerador_description' => 'Mujeres con PAP vigente',
            'denominador_description' => 'Mujeres 25-64 años FONASA',
            'target_value' => 80.00,
            'periodicidad' => 'Semestral',
            'fu' => 'REM-P',
            'is_active' => true,
        ]),
        Indicador::create([
            'codigo' => 'MS-03',
            'nombre' => 'Salud Bucal Pauta CERO 0-9 años',
            'tipo' => 'Meta Sanitaria',
            'numerador_description' => 'Controles CERO',
            'denominador_description' => 'Población 0-9 FONASA',
            'target_value' => 75.00,
            'periodicidad' => 'Mensual',
            'fu' => 'REM-A',
            'is_active' => true,
        ]),
        Indicador::create([
            'codigo' => 'IAAPS-03',
            'nombre' => 'Tasa de consultas médicas de morbilidad',
            'tipo' => 'IAAPS',
            'numerador_description' => 'Consultas de morbilidad realizadas por profesional médico',
            'denominador_description' => 'Población inscrita validada',
            'target_value' => 1.25,
            'periodicidad' => 'Mensual',
            'fu' => 'REM-A',
            'is_active' => true,
        ]),
        Indicador::create([
            'codigo' => 'IAAPS-04',
            'nombre' => 'Porcentaje de derivación al nivel secundario',
            'tipo' => 'IAAPS',
            'numerador_description' => 'N° SIC de controles y consultas médicas generadas en APS',
            'denominador_description' => 'N° total de controles y consultas médicas realizadas en APS',
            'target_value' => 10.00,
            'periodicidad' => 'Mensual',
            'fu' => 'REM-A',
            'is_active' => true,
        ]),
        Indicador::create([
            'codigo' => 'IAAPS-05',
            'nombre' => 'Tasa de Visita Domiciliaria Integral',
            'tipo' => 'IAAPS',
            'numerador_description' => 'N° visitas domiciliarias integrales realizadas',
            'denominador_description' => 'N° de familias (población inscrita validada / 3,3)',
            'target_value' => 0.15,
            'periodicidad' => 'Mensual',
            'fu' => 'REM-A',
            'is_active' => true,
        ]),
        Indicador::create([
            'codigo' => 'IAAPS-06.1A',
            'nombre' => 'Cobertura examen de medicina preventiva en mujeres de 20 a 64 años',
            'tipo' => 'IAAPS',
            'numerador_description' => 'N° de EMP realizado en población de mujeres de 20 a 64 años',
            'denominador_description' => 'Población mujeres 20-64 menos embarazadas',
            'target_value' => 30.00,
            'periodicidad' => 'Mensual',
            'fu' => 'REM-A',
            'is_active' => true,
        ]),
        Indicador::create([
            'codigo' => 'IAAPS-12',
            'nombre' => 'Ingreso precoz de mujeres a control de embarazo antes de las 14 semanas',
            'tipo' => 'IAAPS',
            'numerador_description' => 'N° de mujeres embarazadas ingresadas antes de las 14 semanas a control',
            'denominador_description' => 'Total de mujeres embarazadas ingresadas a control',
            'target_value' => 85.00,
            'periodicidad' => 'Mensual',
            'fu' => 'REM-A',
            'is_active' => true,
        ]),
    ];
});

test('la ruta /importacion carga la vista correctamente para usuarios autenticados', function () {
    $this->actingAs($this->user)
        ->get(route('importacion'))
        ->assertOk()
        ->assertSee('Módulo de Importación REM')
        ->assertSee('CESFAM Los Cerros');
});

test('la ruta /importacion redirige a login si el usuario no está autenticado', function () {
    $this->get(route('importacion'))
        ->assertRedirect(route('login'));
});

test('el servicio calcula correctamente el porcentaje y estado sin división por cero', function () {
    $service = new RemReaderService;

    // Cumpliendo: 90 / 100 = 90% >= 90%
    $res1 = $service->calculateCompliance(90, 100, 90.00);
    expect($res1['porcentaje'])->toBe(90.0)
        ->and($res1['estado'])->toBe('Cumpliendo');

    // En Riesgo: 80 / 100 = 80% (umbral 90 * 0.85 = 76.5%)
    $res2 = $service->calculateCompliance(80, 100, 90.00);
    expect($res2['porcentaje'])->toBe(80.0)
        ->and($res2['estado'])->toBe('En Riesgo');

    // Crítico: 60 / 100 = 60% < 76.5%
    $res3 = $service->calculateCompliance(60, 100, 90.00);
    expect($res3['porcentaje'])->toBe(60.0)
        ->and($res3['estado'])->toBe('Crítico');

    // Denominador cero: debe ser 0% sin lanzar error fatal
    $res4 = $service->calculateCompliance(10, 0, 90.00);
    expect($res4['porcentaje'])->toBe(0.0)
        ->and($res4['estado'])->toBe('Crítico');
});

test('previsualizar con un archivo real o temporal detecta metadatos y solicita denominadores faltantes', function () {
    $realPath = 'D:/DAS/jkm/119308A06.xlsm';

    if (! file_exists($realPath)) {
        $this->markTestSkipped('Archivo real 119308A06.xlsm no disponible para este entorno');
    }

    $uploadedFile = new UploadedFile(
        $realPath,
        '119308A06.xlsm',
        'application/vnd.ms-excel.sheet.macroEnabled.12',
        null,
        true
    );

    $response = $this->actingAs($this->user)
        ->post(route('importacion.previsualizar'), [
            'archivo' => $uploadedFile,
        ]);

    $response->assertOk()
        ->assertJsonStructure([
            'status',
            'temp_token',
            'establecimiento' => ['id', 'nombre', 'codigo_deis'],
            'periodo' => ['año', 'mes'],
            'pending_inputs',
        ]);

    $data = $response->json();
    expect($data['establecimiento']['nombre'])->toBe('CESFAM Los Cerros')
        ->and($data['establecimiento']['codigo_deis'])->toBe('119308')
        ->and($data['periodo']['año'])->toBe(2026)
        ->and($data['periodo']['mes'])->toBe(6)
        ->and($data['status'])->toBe('requires_input')
        ->and($data['pending_inputs'])->not->toBeEmpty();
});

test('previsualizar responde establishment_not_found si el archivo tiene un establecimiento no registrado', function () {
    // Si eliminamos el establecimiento, debe retornar establishment_not_found
    $this->establecimiento->delete();

    $realPath = 'D:/DAS/jkm/119308A06.xlsm';
    if (! file_exists($realPath)) {
        $this->markTestSkipped('Archivo real 119308A06.xlsm no disponible para este entorno');
    }

    $uploadedFile = new UploadedFile(
        $realPath,
        '119308A06.xlsm',
        'application/vnd.ms-excel.sheet.macroEnabled.12',
        null,
        true
    );

    $response = $this->actingAs($this->user)
        ->post(route('importacion.previsualizar'), [
            'archivo' => $uploadedFile,
        ]);

    $response->assertOk()
        ->assertJson([
            'status' => 'establishment_not_found',
        ])
        ->assertJsonStructure([
            'detected' => ['codigo_deis', 'nombre'],
            'establecimientos_disponibles',
        ]);
});

test('guardar persiste correctamente las mediciones en la base de datos', function () {
    $tempToken = 'test-token-uuid-1234';

    Cache::put("rem_pending_{$tempToken}", [
        'metadata' => ['año' => 2026, 'mes' => 6],
        'extracted' => [
            'MS-01' => [
                'numerador' => 9,
                'denominador' => 10,
                'fuente' => 'REM-A03',
            ],
        ],
    ], now()->addHour());

    $response = $this->actingAs($this->user)
        ->postJson(route('importacion.guardar'), [
            'temp_token' => $tempToken,
            'establecimiento_id' => $this->establecimiento->id,
            'año' => 2026,
            'mes' => 6,
            'inputs' => [
                'poblacion_fonasa_25_64' => 10000,
                'numerador_pap_vigente' => 8500,
                'poblacion_fonasa_0_9' => 4000,
                'numerador_ms_03' => 3200,
            ],
        ]);

    $response->assertOk()
        ->assertJson([
            'status' => 'success',
            'periodo' => ['año' => 2026, 'mes' => 6],
        ])
        ->assertJsonStructure([
            'medidas',
            'totales' => ['cumpliendo', 'en_riesgo', 'critico', 'total'],
            'chart' => ['categories', 'cumplimiento', 'objetivos'],
        ]);

    // Verificar en BD para MS-01: 9 / 10 = 90% -> Cumpliendo
    $this->assertDatabaseHas('indicador_medidas', [
        'establecimientos_id' => $this->establecimiento->id,
        'ano' => 2026,
        'mes' => 6,
        'porcentaje_cumplimiento' => 90.00,
        'status' => 'Cumpliendo',
    ]);

    // Verificar en BD para MS-02: 8500 / 10000 = 85% -> Cumpliendo (meta 80%)
    $this->assertDatabaseHas('indicador_medidas', [
        'establecimientos_id' => $this->establecimiento->id,
        'ano' => 2026,
        'mes' => 6,
        'porcentaje_cumplimiento' => 85.00,
        'status' => 'Cumpliendo',
    ]);
});

test('el servicio calcula correctamente cumplimiento para tasas y fórmulas inversas', function () {
    $service = new RemReaderService;

    // IAAPS-04 (Inverso, meta <= 10%): 8% alcanzado -> Cumpliendo
    $resInverso1 = $service->calculateCompliance(8, 100, 10.00, 'Inverso');
    expect($resInverso1['porcentaje'])->toBe(8.0)
        ->and($resInverso1['estado'])->toBe('Cumpliendo');

    // IAAPS-04 (Inverso, meta <= 10%): 11% alcanzado (entre 10% y 11.5%) -> En Riesgo
    $resInverso2 = $service->calculateCompliance(11, 100, 10.00, 'Inverso');
    expect($resInverso2['porcentaje'])->toBe(11.0)
        ->and($resInverso2['estado'])->toBe('En Riesgo');

    // IAAPS-04 (Inverso, meta <= 10%): 15% alcanzado (> 11.5%) -> Crítico
    $resInverso3 = $service->calculateCompliance(15, 100, 10.00, 'Inverso');
    expect($resInverso3['porcentaje'])->toBe(15.0)
        ->and($resInverso3['estado'])->toBe('Crítico');

    // IAAPS-03 (Tasa, meta 1.25): Si logra 1.25 -> 100% cumplimiento -> Cumpliendo
    $resTasa1 = $service->calculateCompliance(1250, 1000, 1.25, 'Tasa');
    expect($resTasa1['porcentaje'])->toBe(100.0)
        ->and($resTasa1['estado'])->toBe('Cumpliendo');

    // IAAPS-03 (Tasa, meta 1.25): Si logra 1.10 (88% de la meta) -> En Riesgo
    $resTasa2 = $service->calculateCompliance(1100, 1000, 1.25, 'Tasa');
    expect($resTasa2['porcentaje'])->toBe(88.0)
        ->and($resTasa2['estado'])->toBe('En Riesgo');

    // IAAPS-03 (Tasa, meta 1.25): Si logra 0.50 (40% de la meta) -> Crítico
    $resTasa3 = $service->calculateCompliance(500, 1000, 1.25, 'Tasa');
    expect($resTasa3['porcentaje'])->toBe(40.0)
        ->and($resTasa3['estado'])->toBe('Crítico');
});

test('previsualizar extrae indicadores IAAPS y genera preguntas de denominadores pendientes', function () {
    $realPath = 'D:/DAS/jkm/119308A06.xlsm';
    if (! file_exists($realPath)) {
        $this->markTestSkipped('Archivo real 119308A06.xlsm no disponible para este entorno');
    }

    $uploadedFile = new UploadedFile(
        $realPath,
        '119308A06.xlsm',
        'application/vnd.ms-excel.sheet.macroEnabled.12',
        null,
        true
    );

    $response = $this->actingAs($this->user)
        ->post(route('importacion.previsualizar'), [
            'archivo' => $uploadedFile,
            'tipo_indicadores' => 'todas',
        ]);

    $response->assertOk();
    $data = $response->json();

    // Validar extracción de IAAPS-12 (Ingreso precoz embarazo hoja A05: C13 = 9, C11 = 9)
    expect($data['extracted_metas'])->toHaveKey('IAAPS-12')
        ->and($data['extracted_metas']['IAAPS-12']['numerador'])->toEqual(9)
        ->and($data['extracted_metas']['IAAPS-12']['denominador'])->toEqual(9);

    // Validar extracción de IAAPS-03 (Consultas morbilidad médica hoja A04: B12 = 1296)
    expect($data['extracted_metas'])->toHaveKey('IAAPS-03')
        ->and($data['extracted_metas']['IAAPS-03']['numerador'])->toEqual(1296)
        ->and($data['extracted_metas']['IAAPS-03']['denominador'])->toEqual(35000);

    // Validar extracción de IAAPS-06.1A (EMP mujeres hoja A02: D11 = 133) y 06.1B (EMP hombres: C11 = 100)
    expect($data['extracted_metas'])->toHaveKey('IAAPS-06.1A')
        ->and($data['extracted_metas']['IAAPS-06.1A']['numerador'])->toEqual(133)
        ->and($data['extracted_metas'])->toHaveKey('IAAPS-06.1B')
        ->and($data['extracted_metas']['IAAPS-06.1B']['numerador'])->toEqual(100);

    // Validar que se generaron pending_inputs para IAAPS
    $metasEnPending = collect($data['pending_inputs'])->pluck('meta_codigo')->unique()->toArray();
    expect($metasEnPending)->toContain('IAAPS-04')
        ->and($metasEnPending)->toContain('IAAPS-05');
});

test('guardar persiste mediciones de Metas Sanitarias e IAAPS 2025 simultáneamente', function () {
    $tempToken = 'test-token-uuid-iaaps-9999';

    Cache::put("rem_pending_{$tempToken}", [
        'metadata' => ['año' => 2026, 'mes' => 6],
        'extracted' => [
            'MS-01' => [
                'numerador' => 9,
                'denominador' => 10,
                'fuente' => 'REM-A03',
            ],
            'IAAPS-12' => [
                'numerador' => 9,
                'denominador' => 9,
                'fuente' => 'REM-A05',
            ],
            'IAAPS-03' => [
                'numerador' => 43750,
                'denominador' => 35000,
                'fuente' => 'REM-A04',
            ],
        ],
    ], now()->addHour());

    $response = $this->actingAs($this->user)
        ->postJson(route('importacion.guardar'), [
            'temp_token' => $tempToken,
            'establecimiento_id' => $this->establecimiento->id,
            'año' => 2026,
            'mes' => 6,
            'inputs' => [
                'iaaps_04_sic' => 50,
                'iaaps_04_total_consultas' => 1000,
            ],
        ]);

    $response->assertOk()
        ->assertJson([
            'status' => 'success',
            'periodo' => ['año' => 2026, 'mes' => 6],
        ]);

    $data = $response->json();
    expect($data['totales']['metas_count'])->toBeGreaterThanOrEqual(1)
        ->and($data['totales']['iaaps_count'])->toBeGreaterThanOrEqual(2);

    // MS-01: 9 / 10 = 90%
    $this->assertDatabaseHas('indicador_medidas', [
        'establecimientos_id' => $this->establecimiento->id,
        'ano' => 2026,
        'mes' => 6,
        'porcentaje_cumplimiento' => 90.00,
        'status' => 'Cumpliendo',
    ]);

    // IAAPS-12: 9 / 9 = 100% (meta 85%) -> Cumpliendo
    $this->assertDatabaseHas('indicador_medidas', [
        'establecimientos_id' => $this->establecimiento->id,
        'ano' => 2026,
        'mes' => 6,
        'porcentaje_cumplimiento' => 100.00,
        'status' => 'Cumpliendo',
    ]);

    // IAAPS-04: 50 / 1000 = 5% <= 10% -> Cumpliendo
    $this->assertDatabaseHas('indicador_medidas', [
        'establecimientos_id' => $this->establecimiento->id,
        'ano' => 2026,
        'mes' => 6,
        'porcentaje_cumplimiento' => 5.00,
        'status' => 'Cumpliendo',
    ]);
});
