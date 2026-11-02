<?php

use App\Models\Indicador;
use App\Models\RemMapping;
use App\Models\User;
use App\Services\RemReaderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Usuario']);
    Role::firstOrCreate(['name' => 'Admin']);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('Admin');

    $this->regularUser = User::factory()->create();
    $this->regularUser->assignRole('Usuario');
});

test('un usuario no autenticado no puede acceder al panel de admin', function () {
    $this->get(route('admin.dashboard'))
        ->assertRedirect(route('login'));
});

test('un usuario regular no puede acceder al panel de admin y es redirigido al centro de mando', function () {
    $this->actingAs($this->regularUser)
        ->get(route('admin.dashboard'))
        ->assertRedirect(route('centro-mando'));
});

test('un administrador puede ver el panel con las 3 secciones', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Panel de Administración')
        ->assertSee('Sección 1: Usuarios')
        ->assertSee('Sección 2: Metas Sanitarias e IAAPS')
        ->assertSee('Sección 3: Mapeo de Celdas REM');
});

test('seccion 1: el admin puede crear, editar y eliminar usuarios', function () {
    // 1. Crear usuario
    $response = $this->actingAs($this->admin)
        ->post(route('admin.users.store'), [
            'name' => 'Dr. Fernando',
            'apellido' => 'González',
            'email' => 'fgonzalez@salud.cl',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'roles' => ['Usuario'],
        ]);

    $response->assertRedirect(route('admin.dashboard', ['tab' => 'usuarios']));
    $this->assertDatabaseHas('users', ['email' => 'fgonzalez@salud.cl']);

    $newUser = User::where('email', 'fgonzalez@salud.cl')->first();

    // 2. Editar usuario
    $this->actingAs($this->admin)
        ->put(route('admin.users.update', $newUser), [
            'name' => 'Dr. Fernando Modificado',
            'apellido' => 'González',
            'email' => 'fgonzalez@salud.cl',
            'roles' => ['Admin'],
        ])
        ->assertRedirect(route('admin.dashboard', ['tab' => 'usuarios']));

    expect($newUser->fresh()->name)->toBe('Dr. Fernando Modificado')
        ->and($newUser->fresh()->hasRole('Admin'))->toBeTrue();

    // 3. No puede eliminarse a sí mismo
    $this->actingAs($this->admin)
        ->delete(route('admin.users.destroy', $this->admin))
        ->assertSessionHasErrors(['user']);

    // 4. Puede eliminar a otro usuario
    $this->actingAs($this->admin)
        ->delete(route('admin.users.destroy', $newUser))
        ->assertRedirect(route('admin.dashboard', ['tab' => 'usuarios']));

    $this->assertDatabaseMissing('users', ['id' => $newUser->id]);
});

test('seccion 2: el admin puede crear, actualizar, cambiar estado y eliminar metas e IAAPS', function () {
    // 1. Crear indicador
    $response = $this->actingAs($this->admin)
        ->post(route('admin.indicadores.store'), [
            'codigo' => 'MS-99',
            'nombre' => 'Meta de Prueba Admin',
            'tipo' => 'Meta Sanitaria',
            'target_value' => 88.0,
            'periodicidad' => 'Mensual',
            'fu' => 'REM-A',
            'is_active' => true,
        ]);

    $response->assertRedirect(route('admin.dashboard', ['tab' => 'iaaps', 'q_iaaps' => 'MS-99']));
    $this->assertDatabaseHas('indicadores', ['codigo' => 'MS-99']);

    $indicador = Indicador::where('codigo', 'MS-99')->first();

    // 2. Actualizar indicador
    $this->actingAs($this->admin)
        ->put(route('admin.indicadores.update', $indicador), [
            'codigo' => 'MS-99',
            'nombre' => 'Meta Modificada Admin',
            'tipo' => 'Meta Sanitaria',
            'target_value' => 92.5,
            'periodicidad' => 'Semestral',
            'fu' => 'REM-P',
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.dashboard', ['tab' => 'iaaps']));

    expect($indicador->fresh()->nombre)->toBe('Meta Modificada Admin')
        ->and((float) $indicador->fresh()->target_value)->toBe(92.5);

    // 3. Cambiar estado
    $this->actingAs($this->admin)
        ->patch(route('admin.indicadores.estado', $indicador), [
            'is_active' => false,
        ])
        ->assertRedirect(route('admin.dashboard', ['tab' => 'iaaps']));

    expect($indicador->fresh()->is_active)->toBeFalse();

    // 4. Eliminar indicador
    $this->actingAs($this->admin)
        ->delete(route('admin.indicadores.destroy', $indicador))
        ->assertRedirect(route('admin.dashboard', ['tab' => 'iaaps']));

    $this->assertDatabaseMissing('indicadores', ['id' => $indicador->id]);
});

test('seccion 3: el admin puede crear, actualizar, cambiar estado y eliminar mapeos de celdas REM', function () {
    $indicador = Indicador::factory()->create([
        'codigo' => 'MS-02',
        'nombre' => 'Cobertura PAP',
        'tipo' => 'Meta Sanitaria',
    ]);

    // 1. Crear mapeo
    $response = $this->actingAs($this->admin)
        ->post(route('admin.rem-mappings.store'), [
            'indicadores_id' => $indicador->id,
            'hoja' => 'REM-P06',
            'celda_numerador' => 'G21',
            'celda_denominador' => null,
            'seccion' => 'Sección B.1',
            'descripcion' => 'Mujeres de 25 a 64 años con PAP vigente',
            'is_active' => true,
        ]);

    $response->assertRedirect(route('admin.dashboard', ['tab' => 'rem']));

    $this->assertDatabaseHas('rem_mappings', [
        'indicadores_id' => $indicador->id,
        'hoja' => 'P06',
        'celda_numerador' => 'G21',
    ]);

    $mapping = RemMapping::where('indicadores_id', $indicador->id)->first();

    // 2. Actualizar celda
    $this->actingAs($this->admin)
        ->put(route('admin.rem-mappings.update', $mapping), [
            'indicadores_id' => $indicador->id,
            'hoja' => 'P06',
            'celda_numerador' => 'G22',
            'celda_denominador' => 'G20',
            'seccion' => 'Sección B.1 Modificada',
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.dashboard', ['tab' => 'rem']));

    expect($mapping->fresh()->celda_numerador)->toBe('G22')
        ->and($mapping->fresh()->celda_denominador)->toBe('G20');

    // 3. Cambiar estado
    $this->actingAs($this->admin)
        ->patch(route('admin.rem-mappings.estado', $mapping), [
            'is_active' => false,
        ])
        ->assertRedirect(route('admin.dashboard', ['tab' => 'rem']));

    expect($mapping->fresh()->is_active)->toBeFalse();

    // 4. Eliminar mapeo
    $this->actingAs($this->admin)
        ->delete(route('admin.rem-mappings.destroy', $mapping))
        ->assertRedirect(route('admin.dashboard', ['tab' => 'rem']));

    $this->assertDatabaseMissing('rem_mappings', ['id' => $mapping->id]);
});

test('el modulo de importacion REM extrae desde la celda configurada por el admin', function () {
    $indicador = Indicador::factory()->create([
        'codigo' => 'IAAPS-CUSTOM',
        'nombre' => 'Indicador de prueba con mapeo',
        'tipo' => 'IAAPS',
    ]);

    // Crear mapeo personalizado en BD: Hoja A01, Celda Numerador K10, Denominador K12
    RemMapping::create([
        'indicadores_id' => $indicador->id,
        'hoja' => 'A01',
        'celda_numerador' => 'K10',
        'celda_denominador' => 'K12',
        'is_active' => true,
    ]);

    // Crear planilla en memoria con hoja A01 y valores en K10 y K12
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('A01');
    $sheet->setCellValue('K10', 175);
    $sheet->setCellValue('K12', 200);

    $service = app(RemReaderService::class);
    $extracted = $service->extractNumeradores($spreadsheet);

    expect($extracted)->toHaveKey('IAAPS-CUSTOM')
        ->and($extracted['IAAPS-CUSTOM']['numerador'])->toBe(175.0)
        ->and($extracted['IAAPS-CUSTOM']['denominador'])->toBe(200.0)
        ->and($extracted['IAAPS-CUSTOM']['detalles']['mapeo_personalizado'])->toBeTrue();
});
