<?php

use App\Models\Establecimiento;
use App\Models\Indicador;
use App\Models\User;
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
            'numerador_description' => 'Consultas de morbilidad',
            'denominador_description' => 'Población inscrita validada',
            'target_value' => 1.25,
            'periodicidad' => 'Mensual',
            'fu' => 'REM-A',
            'is_active' => true,
        ]),
        Indicador::create([
            'codigo' => 'IAAPS-12',
            'nombre' => 'Ingreso precoz de mujeres a control de embarazo antes de las 14 semanas',
            'tipo' => 'IAAPS',
            'numerador_description' => 'Gestantes antes de 14 sem',
            'denominador_description' => 'Total gestantes',
            'target_value' => 85.00,
            'periodicidad' => 'Mensual',
            'fu' => 'REM-A',
            'is_active' => true,
        ]),
    ];
});

test('previsualizar rechaza peticion sin ningun archivo adjunto', function () {
    $response = $this->actingAs($this->user)
        ->postJson(route('importacion.previsualizar'), []);

    $response->assertStatus(422)
        ->assertJson([
            'status' => 'error',
            'message' => 'Debe adjuntar al menos una planilla REM (.xlsm / .xlsx) para procesar.',
        ]);
});

test('previsualizar permite subir multiples planillas y consolida indicadores de Serie A y Serie P', function () {
    $pathA = file_exists('D:/DAS/documentos/119308A06.xlsm') ? 'D:/DAS/documentos/119308A06.xlsm' : null;
    $pathP = file_exists('D:/DAS/documentos/119308P06.xlsm') ? 'D:/DAS/documentos/119308P06.xlsm' : null;

    if (! $pathA || ! $pathP) {
        $this->markTestSkipped('Planillas de prueba 119308A06 y 119308P06 no encontradas en D:/DAS/documentos');
    }

    $fileA = new UploadedFile($pathA, '119308A06.xlsm', 'application/vnd.ms-excel.sheet.macroEnabled.12', null, true);
    $fileP = new UploadedFile($pathP, '119308P06.xlsm', 'application/vnd.ms-excel.sheet.macroEnabled.12', null, true);

    $response = $this->actingAs($this->user)
        ->post(route('importacion.previsualizar'), [
            'archivos' => [$fileA, $fileP],
            'tipo_indicadores' => 'todas',
        ]);

    $response->assertOk();
    $data = $response->json();

    // Validar respuesta consolidada
    expect($data)->toHaveKey('temp_token')
        ->and($data['establecimiento']['id'])->toBe($this->establecimiento->id)
        ->and($data['is_multiple'])->toBeTrue()
        ->and($data['total_archivos'])->toBe(2)
        ->and($data['archivos_procesados'])->toHaveCount(2);

    // MS-01 extraído desde Serie A (119308A06.xlsm)
    expect($data['extracted_metas'])->toHaveKey('MS-01')
        ->and($data['extracted_metas']['MS-01']['numerador'])->toEqual(3);

    // MS-02 extraído desde Serie P (119308P06.xlsm - PAP vigente P12)
    expect($data['extracted_metas'])->toHaveKey('MS-02')
        ->and($data['extracted_metas']['MS-02']['numerador'])->toBeGreaterThan(0);

    // IAAPS-03 extraído desde Serie A
    expect($data['extracted_metas'])->toHaveKey('IAAPS-03')
        ->and($data['extracted_metas']['IAAPS-03']['numerador'])->toEqual(1351)
        ->and($data['extracted_metas']['IAAPS-03']['denominador'])->toEqual(35000);
});

test('guardar procesa y almacena indicadores consolidados de multiples planillas', function () {
    $tempToken = 'multi-file-test-uuid-12345';

    Cache::put("rem_pending_{$tempToken}", [
        'metadata' => ['año' => 2026, 'mes' => 6],
        'extracted' => [
            'MS-01' => [
                'numerador' => 9,
                'denominador' => 10,
                'fuente' => 'REM-A03',
            ],
            'MS-02' => [
                'numerador' => 483,
                'denominador' => null,
                'fuente' => 'REM-P12',
            ],
            'IAAPS-12' => [
                'numerador' => 9,
                'denominador' => 9,
                'fuente' => 'REM-A05',
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
                'poblacion_fonasa_25_64' => 600,
            ],
        ]);

    $response->assertOk()
        ->assertJson([
            'status' => 'success',
            'periodo' => ['año' => 2026, 'mes' => 6],
        ]);

    // Verificar en BD que ambas metas (A y P) quedaron guardadas
    $this->assertDatabaseHas('indicador_medidas', [
        'establecimientos_id' => $this->establecimiento->id,
        'ano' => 2026,
        'mes' => 6,
        'numerador_value' => 9,
        'denominador_value' => 10,
        'porcentaje_cumplimiento' => 90.00,
        'status' => 'Cumpliendo',
    ]);

    $this->assertDatabaseHas('indicador_medidas', [
        'establecimientos_id' => $this->establecimiento->id,
        'ano' => 2026,
        'mes' => 6,
        'numerador_value' => 483,
        'denominador_value' => 600,
        'porcentaje_cumplimiento' => 80.50,
        'status' => 'Cumpliendo',
    ]);
});

test('previsualizar mantiene compatibilidad con subida individual mediante campo archivo', function () {
    $pathA = file_exists('D:/DAS/documentos/119308A06.xlsm') ? 'D:/DAS/documentos/119308A06.xlsm' : null;
    if (! $pathA) {
        $this->markTestSkipped('Planilla 119308A06 no encontrada');
    }

    $fileA = new UploadedFile($pathA, '119308A06.xlsm', 'application/vnd.ms-excel.sheet.macroEnabled.12', null, true);

    $response = $this->actingAs($this->user)
        ->post(route('importacion.previsualizar'), [
            'archivo' => $fileA,
            'tipo_indicadores' => 'metas',
        ]);

    $response->assertOk();
    $data = $response->json();

    expect($data)->toHaveKey('temp_token')
        ->and($data['establecimiento']['id'])->toBe($this->establecimiento->id)
        ->and($data['is_multiple'])->toBeFalse()
        ->and($data['total_archivos'])->toBe(1);
});
