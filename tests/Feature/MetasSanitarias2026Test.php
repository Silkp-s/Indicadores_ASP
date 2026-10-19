<?php

use App\Models\Establecimiento;
use App\Models\Indicador;
use App\Models\IndicadorMedida;
use App\Models\User;
use App\Services\RemReaderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
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

    // Crear metas 2026 en BD
    $this->meta1 = Indicador::create([
        'codigo' => 'MS-01',
        'nombre' => 'Recuperación del desarrollo psicomotor en niños y niñas de 12 a 23 meses',
        'tipo' => 'Meta Sanitaria',
        'numerador_description' => 'Niños recuperados DSM',
        'denominador_description' => 'Niños con riesgo primera evaluación',
        'target_value' => 90.00,
        'periodicidad' => 'Mensual',
        'fu' => 'REM-A',
        'is_active' => true,
    ]);

    $this->meta3a = Indicador::create([
        'codigo' => 'MS-03.A',
        'nombre' => 'Control con enfoque de riesgo odontológico en población de 0 a 9 años',
        'tipo' => 'Meta Sanitaria',
        'numerador_description' => 'Pauta CERO aplicada',
        'denominador_description' => 'Población 0 a 9 años FONASA',
        'target_value' => 60.00,
        'periodicidad' => 'Mensual',
        'fu' => 'REM-A',
        'is_active' => true,
    ]);

    $this->meta3b = Indicador::create([
        'codigo' => 'MS-03.B',
        'nombre' => 'Niños y niñas de 6 años libres de caries',
        'tipo' => 'Meta Sanitaria',
        'numerador_description' => 'Niños de 6 años con CEOD = 0',
        'denominador_description' => 'Población 6 años FONASA',
        'target_value' => 50.00,
        'periodicidad' => 'Mensual',
        'fu' => 'REM-A',
        'is_active' => true,
    ]);

    $this->meta4 = Indicador::create([
        'codigo' => 'MS-04',
        'nombre' => 'Cobertura efectiva de diabetes mellitus tipo 2 (DM2) en personas de 15 años y más',
        'tipo' => 'Meta Sanitaria',
        'numerador_description' => 'DM2 compensada HbA1c < 7% o < 8%',
        'denominador_description' => 'Prevalencia DM2 ENS',
        'target_value' => 29.00,
        'periodicidad' => 'Semestral',
        'fu' => 'REM-P',
        'is_active' => true,
    ]);

    $this->meta4b = Indicador::create([
        'codigo' => 'MS-04.B',
        'nombre' => 'Evaluación anual de los pies en personas con diabetes mellitus 2 bajo control de 15 y más años',
        'tipo' => 'Meta Sanitaria',
        'numerador_description' => 'Evaluación de pie diabético vigente',
        'denominador_description' => 'Personas con DM2 bajo control',
        'target_value' => 90.00,
        'periodicidad' => 'Semestral',
        'fu' => 'REM-P',
        'is_active' => true,
    ]);
});

test('evaluateCellExpression soporta operaciones complejas de suma, resta, parentesis y rangos', function () {
    $service = app(RemReaderService::class);
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('A03');

    // Celdas para denominador de MS-01: (J23+K23) - (J37+K37 + J40+K40)
    $sheet->setCellValue('J23', 50);
    $sheet->setCellValue('K23', 50);
    $sheet->setCellValue('J37', 10);
    $sheet->setCellValue('K37', 5);
    $sheet->setCellValue('J40', 3);
    $sheet->setCellValue('K40', 2);

    // Expresión textual con '(menos)' como viene en la especificación ministerial
    $formula1 = '(J23+ K23) (menos) (J37+ K37+ J40+ K40)';
    $resultado1 = $service->evaluateCellExpression($sheet, $formula1);
    // (50+50) - (10+5+3+2) = 100 - 20 = 80
    expect($resultado1)->toBe(80.0);

    // Celdas para rango F206:Y207 (Meta III.A)
    $sheet->setCellValue('F206', 15);
    $sheet->setCellValue('G206', 10);
    $sheet->setCellValue('F207', 5);
    $sheet->setCellValue('Y207', 20);
    $resultado2 = $service->evaluateCellExpression($sheet, 'F206:Y207');
    // 15 + 10 + 5 + 20 = 50
    expect($resultado2)->toBe(50.0);
});

test('extractNumeradores extrae MS-03.A y MS-03.B correctamente desde las hojas REM A03 y A09', function () {
    $service = app(RemReaderService::class);
    $spreadsheet = new Spreadsheet;

    // Hoja A03 con Pauta CERO fila 206 y 207
    $sheetA03 = $spreadsheet->getActiveSheet();
    $sheetA03->setTitle('A03');
    $sheetA03->setCellValue('F206', 30);
    $sheetA03->setCellValue('G206', 20);
    $sheetA03->setCellValue('F207', 10);

    // Hoja A09 con S51 y T51 (libres de caries a los 6 años)
    $sheetA09 = $spreadsheet->createSheet();
    $sheetA09->setTitle('A09');
    $sheetA09->setCellValue('S51', 12);
    $sheetA09->setCellValue('T51', 18);

    $extracted = $service->extractNumeradores($spreadsheet);

    // Meta III.A (Pauta CERO)
    expect($extracted)->toHaveKey('MS-03.A')
        ->and($extracted['MS-03.A']['numerador'])->toBe(60.0);

    // Meta III.B (Libres de caries 6 años)
    expect($extracted)->toHaveKey('MS-03.B')
        ->and($extracted['MS-03.B']['numerador'])->toBe(30.0);

    // Compatibilidad hacia atrás con MS-03
    expect($extracted)->toHaveKey('MS-03')
        ->and($extracted['MS-03']['numerador'])->toBe(60.0);
});

test('determinePendingInputs solicita los denominadores pertinentes para MS-03.A y MS-03.B', function () {
    $service = app(RemReaderService::class);

    $extracted = [
        'MS-03.A' => ['numerador' => 60.0, 'denominador' => null, 'fuente' => 'REM-A03', 'detalles' => []],
        'MS-03.B' => ['numerador' => 30.0, 'denominador' => null, 'fuente' => 'REM-A09', 'detalles' => []],
    ];

    $pending = $service->determinePendingInputs($extracted, $this->establecimiento, 2026, 'metas');
    $pendingKeys = collect($pending)->pluck('input_key')->all();

    expect($pendingKeys)->toContain('poblacion_fonasa_0_9')
        ->and($pendingKeys)->toContain('poblacion_fonasa_6_anos');
});

test('guardar procesa y persiste correctamente mediciones para MS-03.A y MS-03.B', function () {
    $tempToken = 'test-token-uuid-metas-2026';

    Cache::put("rem_pending_{$tempToken}", [
        'metadata' => ['año' => 2026, 'mes' => 6],
        'extracted' => [
            'MS-03.A' => [
                'numerador' => 450,
                'denominador' => null,
                'fuente' => 'REM-A03',
                'detalles' => [],
            ],
            'MS-03.B' => [
                'numerador' => 180,
                'denominador' => null,
                'fuente' => 'REM-A09',
                'detalles' => [],
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
                'poblacion_fonasa_0_9' => 600, // 450 / 600 = 75% (Meta 60% -> Cumpliendo)
                'poblacion_fonasa_6_anos' => 400, // 180 / 400 = 45% (Meta 50% -> En Riesgo)
            ],
        ]);

    $response->assertOk()
        ->assertJsonPath('status', 'success');

    $medida3a = IndicadorMedida::where('indicadores_id', $this->meta3a->id)
        ->where('establecimientos_id', $this->establecimiento->id)
        ->first();

    expect($medida3a)->not->toBeNull()
        ->and((float) $medida3a->numerador_value)->toBe(450.0)
        ->and((float) $medida3a->denominador_value)->toBe(600.0)
        ->and((float) $medida3a->porcentaje_cumplimiento)->toBe(75.0)
        ->and($medida3a->status)->toBe('Cumpliendo');

    $medida3b = IndicadorMedida::where('indicadores_id', $this->meta3b->id)
        ->where('establecimientos_id', $this->establecimiento->id)
        ->first();

    expect($medida3b)->not->toBeNull()
        ->and((float) $medida3b->numerador_value)->toBe(180.0)
        ->and((float) $medida3b->denominador_value)->toBe(400.0)
        ->and((float) $medida3b->porcentaje_cumplimiento)->toBe(45.0)
        ->and($medida3b->status)->toBe('En Riesgo');
});
